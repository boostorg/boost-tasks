<?php

namespace BoostTasks;

use Nette\SmartObject;
use BoostTasks\Settings;
use BoostTasks\RepoBase;
use BoostTasks\Log;
use BoostTasks\Process;
use RuntimeException;

/*
 * Copyright 2013-2015 Daniel James <daniel@calamity.org.uk>.
 *
 * Distributed under the Boost Software License, Version 1.0. (See accompanying
 * file LICENSE_1_0.txt or copy at http://www.boost.org/LICENSE_1_0.txt)
 */

class Repo extends RepoBase {
    var $github_user; // Or org....
    var $module;
    var $branch;
    var $enable_push;
    var $url;

    function __construct($module, $branch, $path, $url = null) {
        parent::__construct($path);
        $module_parts = explode('/', $module, 2);
        if (count($module_parts) == 2) {
            $this->github_user = $module_parts[0];
            $this->module = $module_parts[1];
        } else {
            $this->github_user = 'boostorg';
            $this->module = $module;
        }
        $this->branch = $branch;
        $this->enable_push = Settings::settings('push-to-repo');
        $this->url = is_null($url) ?
            "git@github.com:{$this->github_user}/{$this->module}.git" :
            $url;
    }

    function getModuleBranchName() {
        return "{$this->module}, branch {$this->branch}";
    }

    function setupCleanCheckout() {
        // Create the repos or update them as required.

        if (!is_dir($this->path)) {
            Log::info("Clone {$this->getModuleBranchName()}.");
            $this->cloneRepo();
        }
        else {
            Log::info("Fetch {$this->getModuleBranchName()}.");
            $this->updateRepo();
        }
    }

    function cloneRepo() {
        // Use a shallow clone so it doesn't take too long, and since this
        // will never use the history.
        Process::run(
            "git clone -q --depth 1 -b {$this->branch} ".
            "{$this->url} {$this->path}");
        $this->configureRepo();
    }

    function updateRepo() {
        $this->fetchWithPrune('origin');
        $this->command("reset -q --hard origin/{$this->branch}");
        $this->command("clean -d -f");
        $this->configureRepo();
    }

    function configureRepo() {
        $this->command("config user.email \"automated@calamity.org.uk\"");
        $this->command("config user.name \"Automated Commit\"");
    }

    function getCommitDate($commit) {
        $output = $this->commandWithOutputSimple("TZ=UTC git show -s --date='format-local:%Y-%m-%dT%H:%M:%SZ' $commit  | grep Date: | tr -s ' ' | cut -d' ' -f 2 ");
        return $output;
    }

    function commitAll($message) {
        $this->command('add -u .');
        $status = $this->commandWithStatus('diff-index HEAD --quiet');

        if ($status == 0) {
            Log::info("No changes to {$this->getModuleBranchName()}.");
            return false;
        } else if ($status == 1) {
            Log::info("Committing changes to {$this->getModuleBranchName()}.");
            $this->command('commit -m "'.$message.'"');
            return true;
        } else {
            throw new RuntimeException("Unexpected status from 'git diff-index'.");
        }
    }

    function attemptAndPush($callback) {
        try {
            // Loop to retry if update fails
            for ($try = 0; $try < 2; ++$try) {
                $this->setupCleanCheckout();
                $result = call_user_func($callback);
                // Nothing to push, so a trivial success
                if (!$result) { return true; }
                if ($this->enable_push) {
                    if ($this->pushRepo()) { return true; }
                } else {
                    Log::warning("{$this->path} processed, not configured to push to repo.\n");
                    return false;
                }

                Log::warning("Attempt {$try}: push failed to {$this->getModuleBranchName()}.\n");
            }

            Log::error("Failed to push to {$this->getModuleBranchName()}.");
            return false;
        }
        catch (RuntimeException $e) {
            Log::error("{$this->getModuleBranchName()}: $e");
            return false;
        }
    }

    function pushRepo() {
        assert(!!$this->enable_push);

        // TODO: Maybe I should parse the output from git push to check exactly
        // what succeeded/failed.

        $status = $this->commandWithStatus('push -q --porcelain', $stderr);

        if ($status > 1) {
            throw new RuntimeException("Push failed: {$stderr}");
        }

        if ($status == 1) {
            Log::warning("Push failed: {$stderr}");
        }

        return $status == 0;
    }

    function setupFullCheckout() {
        // Create the repos or update them as required.

        if (!is_dir($this->path)) {
            Log::info("Clone {$this->getModuleBranchName()}.");
            $this->cloneFullRepo();
        }
        else {
            Log::info("Fetch {$this->getModuleBranchName()}.");
            $this->updateFullRepo();
        }
    }

    function cloneFullRepo() {
        Process::run(
            "git clone -b {$this->branch} ".
            "{$this->url} {$this->path}");
        $this->configureRepo();
    }

    function updateFullRepo() {
        $this->fetch('origin');
        $this->command("reset --hard origin/{$this->branch}");
        $this->command("clean -d -f");
        $this->configureRepo();
    }

}
