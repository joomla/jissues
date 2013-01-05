Requirements
===============
The issue tracker application requires a server running:
- PHP 5.3.1 or later (Joomla Platform minimum version)
- MySQL 5.1 with InnoDB support

Setup
===============
1. Clone the git repo to where ever your test environment is located or download the ZIP from https://github.com/JTracker/jissues/zipball/master.
2. Copy `/configuration.example.php` to `/configuration.php`
3. Enter your database credentials in the `JConfig` class. Change $prefix if desired. (defaults to jos_)

From this point, you can setup your database in one of two ways:

*Preferred*

1. From a command prompt, run the script located at cli/installer.php to set up your database

- This option will prompt you for the creation of an Administrative user account.

*Alternate*

If you are in an environment where you cannot execute PHP scripts from the command line, you can set up your database with the following steps:

1. Open `/sql/mysql.sql` and do a find/replace from `#__` to whatever your prefix is, and save
2. Import the SQL into your database
3. You can optionally import the sample data found at `/sql/sampledata.sql` by repeating steps 1 and 2 with this file

- When using this option, you will need to manually create an Adminstrative user account.

After setting up your database, verify the installation is successful by doing the following:

1. View site in browser to verify setup
2. Open a console a execute cli/retrieveissues.php to get the open issues from GitHub.

Support & Discussion
===============
* If you've found a bug, please report it to the Issue Tracker at https://github.com/JTracker/jissues/issues.
* For discussion about this project, please visit the Google Group at https://groups.google.com/forum/#!forum/jtracker-rebuild.
