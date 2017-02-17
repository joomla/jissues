## Requirements [![Build Status](https://travis-ci.org/joomla/jissues.png?branch=master)](https://travis-ci.org/joomla/jissues) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/joomla/jissues/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/joomla/jissues/?branch=master) [![Crowdin](https://d322cqt584bo4o.cloudfront.net/joomla-official-sites/localized.svg)](https://crowdin.com/project/joomla-official-sites)

The issue tracker application requires a server running:

* PHP 7.0 or later
    * PHP's `ext/curl` and `ext/intl` should also be installed
* MySQL 5.5.3 with InnoDB support (required to support the MySQL utf8mb4 charset) 

The application also has external dependencies installable via [Composer](https://getcomposer.org/) and [Bower](https://bower.io/).

See also: [Dependencies](Documentation/Development/Dependencies.md).

Note: All references to `bin/jtracker` refer to an executable symlink to `cli/tracker.php`. If you cannot execute the `bin/jtracker` symlink replace that path with `cli/tracker.php`

## Setup

1. Clone the git repo to where ever your test environment is located or download a ZIP file.
    * **Note** If you plan to contribute to the project, you might have to use `git clone --recursive` to get the submodules checked out.
1. Copy `etc/config.dist.json` to `etc/config.json`.
1. In the `etc/config.json` file, enter your database credentials and other information.
1. Run `composer install` (or the equivalent for your system) to install dependencies from Composer.
    * If you need to install Composer, you can do so from http://getcomposer.org/download/.
1. From a command prompt, run the `install` command to set up your database.
    * `bin/jtracker install`

If you are making a change to the issue tracker's web assets, you'll also need to set up Bower and Grunt. Please see the [Asset Management](Documentation/Development/Asset-Management.md) documentation for more information.

Verify the installation is successful by doing the following:

1. View the site in your browser.
1. Run the `get project` command to pull issues, issue comments and other information related to the project from GitHub.
    * `bin/jtracker get project`

See also: [CLI script](Documentation/Development/CLI-application.md).

### Virtual Test Environment

As an alternative method, there is a setup for a virtual test environment using Vagrant and VirtualBox.

See also: [Virtual server documentation](Documentation/Development/Virtual-Test-Server.md)

### Using Login with GitHub

If you want the 'Login with GitHub' button to work properly you'll need to register an app with GitHub. To do this manage your account at github.com and go to the applications page. Create a new application.

You'll be asked for the application URL and the callback URL. This can be your test server or your localhost environment. As long as you enter the URL that your localhost app is running on. An example might be `http://jissues.local`.

Once you've registered the app at GitHub you'll receive a `Client ID` and a `Client Secret`, enter these into your installation's `etc/config.json` file, along with your GitHub login credentials. You should now be able to login with GitHub successfully.

See also: [GitHub Authentication](Documentation/Users/GitHub-Authentication.md)

## Support & Discussion

* If you've found a bug, please report it to the Issue Tracker at https://github.com/joomla/jissues/issues.
* Please note this repository is _not_ for the Joomla! CMS. Take all Joomla! CMS issues, bug reports, etc.. to: http://github.com/joomla/joomla-cms
