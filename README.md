Requirements
===============
The issue tracker application requires a server running:
- PHP 5.3.1 or later (Joomla Framework minimum version)
- MySQL 5.1 with InnoDB support

Setup
===============
1. Clone the git repo to where ever your test environment is located or download the ZIP from https://github.com/joomla/jissues/zipball/master.<br />**NOTE** The currently active working branch is the [framework branch](https://github.com/joomla/jissues/tree/framework)
2. Copy `/etc/config.dist.json` to `/etc/config.json`
3. Enter your database credentials. Change database prefix if desired (defaults to `jos_`).

From this point, you can setup your database in one of two ways:

*Preferred*

1. From a command prompt, run the script located in the `cli` folder to set up your database.<br />`tracker.php install`

- This option will prompt you for the creation of an Administrative user account.

*Alternate*

If you are in an environment where you cannot execute PHP scripts from the command line, you can set up your database with the following steps:

1. Open `/etc/mysql.sql` and do a find/replace from `#__` to whatever your prefix is, and save
2. Import the SQL into your database
3. <del>You can optionally import the sample data found at `/etc/sampledata.sql` by repeating steps 1 and 2 with this file</del> currently unsupported due to lack of time..

- When using this option, you will need to manually create an Adminstrative user account.

After setting up your database, verify the installation is successful by doing the following:

1. View site in browser to verify setup
2. Open a console a execute `cli/tracker.php retrieve issues` to get the open issues from GitHub.

For more information on the CLI script see the [cli/readme.md](cli/readme.md) file.

Support & Discussion
===============
* If you've found a bug, please report it to the Issue Tracker at https://github.com/joomla/jissues/issues.
* For discussion about this project, please visit the Google Group at https://groups.google.com/forum/#!forum/jtracker-rebuild.
