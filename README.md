Requirements
===============
The issue tracker application requires a server running:
- PHP 5.3.10 or later (Joomla Framework minimum version)
- MySQL 5.1 with InnoDB support

Setup
===============
1. Clone the git repo to where ever your test environment is located or download the ZIP from https://github.com/joomla/jissues/zipball/master.
2. Copy `/etc/configuration.example.php` to `/etc/configuration.php`.
3. Enter your database credentials in the `JConfig` class. Change $prefix if desired. (defaults to jos_).
4. Install dependencies from Composer by running `composer install` (or the equivalent for your system).  If you need to install Composer, you can do so from http://getcomposer.org/download/.

From this point, you can setup your database in one of two ways:

*Preferred*

1. From a command prompt, run the script located at `cli/tracker.php` with the install option to set up your database

- This option will prompt you for the creation of an Administrative user account.

*Alternate*

If you are in an environment where you cannot execute PHP scripts from the command line, you can set up your database with the following steps:

1. Open `/etc/mysql.sql` and do a find/replace from `#__` to whatever your prefix is, and save
2. Import the SQL into your database

- When using this option, you will need to manually create an Adminstrative user account.

After setting up your database, verify the installation is successful by doing the following:

1. View site in browser to verify setup
2. Open a console a execute cli/tracker.php with the retrieve option to pull issues from GitHub.

Support & Discussion
===============
* If you've found a bug, please report it to the Issue Tracker at https://github.com/joomla/jissues/issues.
* For discussion about this project, please visit the Google Group at https://groups.google.com/forum/#!forum/jtracker-rebuild.
