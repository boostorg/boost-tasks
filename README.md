# Boost Update Scripts

Script to handle various Boost tasks, such as:

* Updating the submodules in the super project.
* Updating a mirror of the Boost repositories.
* Fetching the pull request data from GitHub.

## Getting started.

These scripts require PHP 5.3 or above (preferably 5.4 or above),
and [composer](https://getcomposer.org/).

1. Clone this repo.
2. Install the dependencies using `composer install`.
3. Create a `config.neon` configuration file in the 'var' directory, using
   [Neon](http://ne-on.org/) synatx.

Example configuration file:

    # Path to directory where script stores data
    # (Paths resolve relative to config file)
    data: data

    # GitHub login details
    # (remember to restrict read permissions).
    username: github-username
    password: github-password

    # Path to directory where website stores data
    website-data: /home/www/shared/data

    # Set to true to push to github, false when you're testing
    push-to-repo: false

    # Branches to update (comment out to disable a branch)
    superproject-branches:
        develop: develop
        master: master

    # Extra configuration files:
    config-paths:
        - /home/www/shared/branches.neon

`data` is the path to the directory that will hold data for this script.

`username` and `password` are the GitHub login details for the account that
the script will use.

`website-data` is the path to the website's data folder, where any data
generated for the website (currently only the pull request report) is placed.
This is optional, if it's absent the data will be written to stdout.

If `push-to-repo` is true then any git changes made (such as updating the
submodules) will be pushed to GitHub. Don't want this to happen when testing
the script.

The keys in `superproject-branches` specifies the branches in the super
project to update, the values are the branches to update from.

---

The new script release-from-artifactory requires git version 2.x or higher. Upgrade git with these steps:  
```
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:git-core/ppa
sudo apt update
sudo apt install -y git
```
