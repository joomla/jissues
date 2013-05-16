Requirements [![Build Status](https://travis-ci.org/joomla/jissues.png?branch=framework)](https://travis-ci.org/joomla/jissues)
===============
The issue tracker application requires a server running:
- PHP 5.3.10 or later (Joomla Framework minimum version)
- MySQL 5.1 with InnoDB support

The application also has external dependencies installable via Composer.  You can run `ant installdep` if you have ANT installed or `composer update --dev` from the command line.

Setup
===============
1. Clone the git repo to where ever your test environment is located or download the ZIP from https://github.com/joomla/jissues/zipball/framework<br />**NOTE** The currently active working branch is the [framework branch](https://github.com/joomla/jissues/tree/framework)
2. Copy `/etc/config.dist.json` to `/etc/config.json`.
3. Enter your database credentials in the `/etc/config.json` file.
4. Install dependencies from Composer by running `composer install` (or the equivalent for your system).  If you need to install Composer, you can do so from http://getcomposer.org/download/.

From this point, you can setup your database in one of two ways:

*Preferred*

1. From a command prompt, run the script located at `cli/tracker.php` with the install option to set up your database.<br />`tracker.php install`

- This option will prompt you for the creation of an Administrative user account.

*Alternate*

If you are in an environment where you cannot execute PHP scripts from the command line, you can set up your database with the following steps:

1. Open `/etc/mysql.sql` and do a find/replace from `#__` to whatever your prefix is, and save.
2. Import the SQL into your database.

- When using this option, you will need to manually create an Adminstrative user account.

After setting up your database, verify the installation is successful by doing the following:

1. View site in browser to verify setup
2. Open a console and execute cli/tracker.php with the retrieve option to pull issues and issue comments from GitHub.
`cli/tracker.php retrieve issues`<br />
`cli/tracker.php retrieve comments`

For more information on the CLI script see the [cli/readme.md](cli/readme.md) file.

Support & Discussion
===============
* If you've found a bug, please report it to the Issue Tracker at https://github.com/joomla/jissues/issues.
* Please note this repository is _not_ for the Joomla CMS. Take all Joomla CMS issues, bug reports, etc.. to: http://github.com/joomla/joomla-cms
* For discussion about this project, please visit the Google Group at https://groups.google.com/forum/#!forum/jtracker-rebuild.
