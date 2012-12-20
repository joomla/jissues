Requrements
===============
The issue tracker application requires a server running PHP 5.3.1 or later (Joomla Platform minimum version) and MySQL 5.1 with InnoDB support

Setup
===============
1. Clone the git repo to where ever your test environment is located or download the ZIP from https://github.com/JTracker/jissues/zipball/master.
2. Copy `/libraries/config.example.php` to `/configuration.php`
3. Enter your database credentials in the `JConfig` class. Change $prefix if desired. (defaults to jos_)

From this point, you can setup your database in one of two ways:

*Option 1*

1. Open `/sql/mysql.sql` and do a find/replace from `#__` to whatever your prefix is, and save
2. Import the SQL into your database

- When using this option, you will need to manually create an Adminstrative user account.

*Option 2*

1. From a command prompt, run the script located at cli/installer.php to set up your database

After setting up your database, verify the installation is successful by doing the following:

1. View site in browser to verify setup
2. Open a console a execute cli/retrieveissues.php to get the open issues from GitHub.

- This option will prompt you for the creation of an Administrative user account.

Support & Discussion
===============
* If you've found a bug, please report it to the Issue Tracker at https://github.com/JTracker/jissues/issues.
* For discussion about this project, please visit the Google Group at https://groups.google.com/forum/#!forum/jtracker-rebuild.
