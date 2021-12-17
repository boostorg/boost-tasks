<?php

/*
 * Copyright 2013-2015 Daniel James <daniel@calamity.org.uk>.
 *
 * Distributed under the Boost Software License, Version 1.0. (See accompanying
 * file LICENSE_1_0.txt or copy at http://www.boost.org/LICENSE_1_0.txt)
 */

namespace BoostTasks;

use Nette;
use BoostTasks\Settings;
use BoostTasks\GitHubEventQueue;
use BoostTasks\RepoBase;
use BoostTasks\Repo;
use BoostTasks\Log;
use BoostTasks\LocalMirror;
use RuntimeException;

class SuperProject extends Repo {
    var $submodule_branch;
    var $push_warning = false;

    static function updateBranches($branches = null, $all = false) {
        if (!$branches) { $branches = Settings::branchRepos(); }
        foreach ($branches as $x) {
            $super = new SuperProject($x);
            $super->updateFromEvents($all);
        }
    }

    function __construct($settings) {
        parent::__construct(
            array_get($settings, 'module', Settings::settings('superproject-repo')),
            $this->get($settings, 'superproject-branch'),
            $this->get($settings, 'path'),
            array_get($settings, 'remote_url'));
        $this->submodule_branch = $this->get($settings, 'submodule-branch');
    }

    private function get($settings, $name) {
        if (empty($settings[$name])) {
            throw new RuntimeException("Missing super project setting: {$name}");
        }
        return $settings[$name];
    }

    function updateFromEvents($all = false) {
        $queue = new GitHubEventQueue($this->submodule_branch, 'PushEvent');
        if (!$queue->continuedFromLastRun()) {
            Log::info('Full refresh of submodules because of gap in event queue.');
            $result = $this->pushUpdatesFromAll($queue);
            if ($result) { $queue->markAllRead(); }
        } else if ($all) {
            Log::info('Refresh submodule from event queue, and sync all.');
            $this->pushUpdatesFromEventQueue($queue, true);
        } else {
            Log::info('Refresh submodules from event queue.');
            $this->pushUpdatesFromEventQueue($queue);
        };

        if ($this->push_warning) {
            Log::warning("Changes not pushed, as configured not to.");
            $this->push_warning = false;
        }

        return true;
    }

    private function pushUpdatesFromAll($queue) {
        $self = $this; // Has to work on php 5.3
        return $this->attemptAndPush(function() use($self, $queue) {
            $submodules = $self->getSubmodules();
            $self->getPendingHashesFromGithub($submodules);
            // Include any events that have arrived since starting this update.
            $github_start_id = $queue->queue_pos;
            $queue->downloadMoreEvents();

            foreach ($queue->getEvents($github_start_id) as $event) {
                if ($event->branch == $self->submodule_branch) {
                    if (array_key_exists($event->repo, $submodules)) {
                        $payload = json_decode($event->payload);
                        assert($payload);

                        $submodule = $submodules[$event->repo];

                        if ($payload->before == ($submodule->pending_hash_value ?: $submodule->current_hash_value)) {
                            $submodule->pending_hash_value = $payload->head;
                        }
                    }
                }
            }

            $self->usePendingHashes($submodules);
            return $self->commitHashes($submodules, array(
                'mark_mirror_dirty' => true,
            ));
        });
    }

    private function pushUpdatesFromEventQueue($queue, $check_all = false) {
        // TODO: Only running this once, maybe should try again if it fails?
        try {
            $this->setupCleanCheckout();
            $submodules = $this->getSubmodules();
            if ($check_all) {
                $this->getPendingHashesFromGithub($submodules);
                $queue->downloadMoreEvents();
            }
            $this->pushSubmoduleHashesFromEventQueue($queue, $submodules);
            if ($check_all) {
                // TODO: Message should indicate that this is a 'catch up'
                //       commit, because the repo is out of sync.
                $this->usePendingHashes($submodules);
                $updated = $this->commitHashes($submodules, array(
                    'mark_mirror_dirty' => true,
                ));
                if ($updated) {
                    if ($this->enable_push) {
                        if (!$this->pushRepo()) {
                            Log::error("{$this->getModuleBranchName()}: Error pushing to repo");
                            return false;
                        }
                    } else {
                        $this->push_warning = true;
                    }
                }
            }
        } catch (\RuntimeException $e) {
            Log::error("{$this->getModuleBranchName()}: $e");
            return false;
        }
    }

    // Note: Public so that it can be called in a closure in PHP 5.3
    public function pushSubmoduleHashesFromEventQueue($queue, $submodules = null) {
        foreach ($queue->getEvents() as $event) {
            if ($event->branch != $this->submodule_branch) { continue; }
            if (!array_key_exists($event->repo, $submodules)) { continue; }

            $payload = json_decode($event->payload);
            assert($payload);

            $submodule = $submodules[$event->repo];

            // If updated_hash_value isn't null, would need to add extra checks.
            assert(!$submodule->updated_hash_value);

            // This change and any previous 'ignored' changes have already been made.
            if ($submodule->current_hash_value == $payload->head) {
                $submodule->ignored_events = array();
                continue;
            }

            // This change updates the pending hash value
            if ($submodule->pending_hash_value == $payload->before) {
                $submodule->ignored_events = array();
                if ($submodule->current_hash_value == $payload->head) {
                    $submodule->pending_hash_value = null;
                } else {
                    $submodule->pending_hash_value = $payload->head;
                }
                continue;
            }

            // This change doesn't cleanly apply to the current repo, so ignore it.
            if ($submodule->current_hash_value != $payload->before) {
                $submodule->ignored_events[] = $event;
                continue;
            }

            // We've caught up with the 'pending' change, so mark it as null.
            if ($submodule->pending_hash_value == $payload->head) {
                $submodule->pending_hash_value = null;
            }

            // Apply the change.
            $submodule->updated_hash_value = $payload->head;
            if (!$this->commitHashes($submodules)) {
                throw new RuntimeException("Error updating submodules in git repo");
            }
            assert(!$submodule->updated_hash_value && $submodule->current_hash_value == $payload->head);
            if ($this->enable_push) {
                if (!$this->pushRepo()) {
                    Log::error("{$this->getModuleBranchName()}: Error pushing to repo");
                    break;
                }
                $queue->markReadUpTo($event->github_id);
            } else {
                $this->push_warning = true;
            }
        }

        foreach ($submodules as $submodule) {
            if ($submodule->ignored_events) {
                $events = count($submodule->ignored_events);
                $events .= ($events == 1) ? " PushEvent" : " PushEvents";
                Log::warning("Ignored {$events} for {$submodule->boost_name} as the hash does not match the super project's current value");
            }
        }
    }

    public function getSubmodules() {
        $submodules = array();
        $submodule_by_path = array();
        $paths = array();
        foreach (RepoBase::readSubmoduleConfig($this->path) as $name => $details) {
            $submodule = new SuperProject_Submodule($name, $details);
            if ($submodule->github_name) {
                $submodules[$submodule->github_name] = $submodule;
                $submodule_by_path[$submodule->path] = $submodule;
                $paths[] = $submodule->path;
            }
        }
        foreach ($this->currentHashes($paths) as $path => $hash) {
            $submodule_by_path[$path]->current_hash_value = $hash;
        }
        return $submodules;
    }

    // Note: Public so that it can be called in a closure in PHP 5.3
    public function getPendingHashesFromGithub($submodules) {
        foreach($submodules as $submodule) {
            // Note: Alternative would be to use branch API to get more
            //       information.
            //       https://developer.github.com/v3/repos/branches/#get-branch
            try {
                $ref = Settings::githubCache()->getJson(
                    "/repos/{$submodule->github_name}/git/refs/heads/{$this->submodule_branch}");
                if ($ref->object->sha != $submodule->current_hash_value) {
                    $submodule->pending_hash_value = $ref->object->sha;
                }
            } catch (\BoostTasks\GitHubCache_Error $e) {
                if ($e->code() == 404) {
                    Log::error("Unable to find {$this->submodule_branch} for {$submodule->github_name}");
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * Update the submodule hashes from any pending hash values.
     *
     * @param Array $submodules
     */
    function usePendingHashes($submodules) {
        foreach ($submodules as $submodule) {
            if ($submodule->pending_hash_value) {
                if ($submodule->updated_hash_value) {
                    throw new \RuntimeException("Update for {$submodule->boost_name} doesn't match event queue");
                }
                $submodule->updated_hash_value = $submodule->pending_hash_value;
                $submodule->pending_hash_value = null;
            }
        }
    }

    /**
     * Commit the submodule hashes to the repo, and optionally
     * mark submodules to be fetched in the mirror.
     *
     * Options:
     *     mark_mirror_dirty - Update the mirror of updated repos on the next run.
     *
     * @param Array $submodules
     * @param Array $options
     * @return boolean True if a change was committed.
     */
    function commitHashes($submodules, $options = Array()) {
        $options = array_merge(array(
            'mark_mirror_dirty' => false,
        ), $options);
        $updates = array();
        $names = array();
        foreach($submodules as $submodule) {
            if (!$submodule->updated_hash_value) { continue; }

            if ($submodule->current_hash_value != $submodule->updated_hash_value) {
                $updates[$submodule->path] = $submodule->updated_hash_value;
                $names[] = preg_replace('@^(libs|tools)/@', '', $submodule->boost_name);

                $submodule->current_hash_value = $submodule->updated_hash_value;
                $submodule->updated_hash_value = null;
            }
        }

        if (!$updates) return false;

        $text_updates = '';
        $message = $this->getUpdateMessage($names);
        Log::info("Commit to {$this->branch}: ".strtok($message, "\n"));

        foreach ($updates as $path => $hash) {
            $text_updates .=
                    "160000 {$hash}\t{$path}\n";
        }

        $this->commandWithInput('update-index --index-info', $text_updates);
        $this->commandWithInput("commit -F -", $message);

        // A bit of hack, tell the mirror to fetch any updated submodules.
        // The main concern is that sometimes the event queue misses a
        // push event, and the update is caught by 'getPendingHashesFromGithub'.
        if ($options['mark_mirror_dirty']) {
            $mirror = new LocalMirror;
            foreach($submodules as $submodule) {
                if (array_key_exists($submodule->path, $updates)) {
                    // TODO: Github URLs aren't a good identifier, as the same repo
                    //       can have multiple URLs.
                    $url = "https://github.com/{$submodule->github_name}.git";
                    Log::info("Schedule mirror fetch for: {$url}");
                    $mirror->update($url, true);
                }
            }
        }

        return true;
    }

    function getUpdateMessage($names) {
        natcasesort($names);

        $update = 'Update ' .implode(', ', $names);
        $message = "{$update} from {$this->submodule_branch}";

        // Git recommends that the short message is 50 character or less,
        // which seems unreasonably short to me, but there you go.
        if (strlen($message) > 50) {
            $message = "Update ".count($names).
                (count($names) == 1 ? " submodule" : " submodules").
                " from {$this->submodule_branch}";
            $message .= "\n\n";
            $message .= wordwrap("{$update}.", 72);
            $message .= "\n";
        }

        return $message;
    }
}

/**
 * A submodule.
 */
class SuperProject_Submodule {

    use Nette\SmartObject;

    /** The relative path of the submodule from boost root. */
    var $path;

    /** The name of the submodule in the boost repo. */
    var $boost_name;

    /** Github's name for the submodule. */
    var $github_name;

    /** Hash currently in the superproject repo */
    var $current_hash_value;

    /** The hash value currently in the submodule repo. */
    var $updated_hash_value;

    /** Will be updated to this hash value eventually. */
    var $pending_hash_value;

    /** Push events that have been ignored */
    var $ignored_events = array();

    function __construct($name, $values) {
        $this->boost_name = $name;
        $this->path = $values['path'];

        $matches = null;
        // Q: Set github name based on super project name?
        //    Should make that an option. For testing purposes, I'm running
        //    this in my own repo, so actually want it to use the boostorg
        //    repo - even though that's not how git would interpret the
        //    relative paths.
        if (preg_match('@^(?:\.\.|https?://github\.com/boostorg)/(\w+)(\.git)?$@', $values['url'], $matches)) {
            $this->github_name = "boostorg/{$matches[1]}";
        }
    }
}
