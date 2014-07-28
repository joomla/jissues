## Requirements [![Build Status](https://travis-ci.org/joomla/jissues.png?branch=framework)](https://travis-ci.org/joomla/jissues) [![Analytics](https://ga-beacon.appspot.com/UA-544070-3/joomla-issue-tracker/readme)](https://github.com/igrigorik/ga-beacon)

The issue tracker application requires a server running:

* PHP 5.4.4 or later
* MySQL 5.1 with InnoDB support

The application also has external dependencies installable via Composer and Bower.  You can run `ant installdep` if you have ANT installed or `composer install` and `bower install` from the command line.

See also: [Dependencies](Documentation/Development/Dependencies.md).

## Setup

1. Clone the git repo to where ever your test environment is located or download a ZIP file.
    * **Note** If you plan to contribute to the project, you might have to use `git clone --recursive` to get the submodules checked out.
1. Copy `etc/config.dist.json` to `etc/config.json`.
1. In the `etc/config.json` file, enter your database credentials and other information.
1. Run `composer install` (or the equivalent for your system) to install dependencies from Composer.
    * If you need to install Composer, you can do so from http://getcomposer.org/download/.
1. Run `bower install` to install media files from Bower
    * If you need to install Bower you can do so by using NPM. Read more http://bower.io/.
1. From a command prompt, run the script located at `cli/tracker.php` with the install option to set up your database.
    * `./cli/tracker.php install`

Verify the installation is successful by doing the following:

1. View the site in your browser.
1. Open a console and execute the `tracker.php` script with the `get project` option to pull issues, issue comments and other information related to the project from GitHub.
    * `cli/tracker.php get project`

See also: [CLI script](Documentation/Development/CLI-application.md).

### Virtual Test Environment

As an alternative method, there is a setup for a virtual test environment using Vagrant and VirtualBox.

See also: [Virtual server documentation](Documentation/Development/Virtual-Test-Server.md)

### Using Login with Github

If you want the 'login with Github' button to work properly you'll need to register an app with Github. To do this manage your account at github.com and go to the applications page. Create a new application.

You'll be asked for the application URL and the callback URL. This can be your test server or your localhost environment. As long as you enter the URL that your localhost app is running on. An example might be ```http://jissues.local```.

Once you've registered the app at Github you'll receive a ```Client ID``` and a ```Client Secret```, enter these into your JTracker ```config.json``` file, along with your Github login credentials. You should now be able to login with Github successfully

See also: [GitHub Authentication](Documentation/Users/GitHub-Authentication.md)

## Support & Discussion

* If you've found a bug, please report it to the Issue Tracker at https://github.com/joomla/jissues/issues.
* Please note this repository is _not_ for the Joomla CMS. Take all Joomla CMS issues, bug reports, etc.. to: http://github.com/joomla/joomla-cms
* For discussion about this project, please visit the Google+ Community at https://plus.google.com/u/0/communities/102541713885101375024.
