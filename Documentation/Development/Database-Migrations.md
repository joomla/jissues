## Database Migrations

A simple `Migrations` class is used to track database migrations for the issue tracker application.

### File Structure

All schema migration files are stored in the `etc/migrations` folder and use a file name in the format of `YYYYMMDD###` where:

* `YYYYMMDD` is the date the migration was added (for example, for 11 June 2016 this would be 20160611)
* `###` is an index number of the migration relative to the current date (this format supports the possibility for 1000 migrations for a single day)

The base migration version is `20160611001` and this represents the database schema at the time this API was created.

### Creating Migrations

To create a migration, add a new file to the `etc/migrations` folder following the file name format listed above and add all SQL statements relevant to the change to this file. The same changes should also be made to the base install SQL file so it remains in sync for new installations.

Additionally, the new migration's version should be added to the base install SQL file so that new installations are correctly in sync with the proper migration version.

### Checking Migration Status

To validate an installation is on the correct version there is a CLI command which will report the status. Run `bin/jtracker database:status` to get the current status. The command's output will inform you if you are not on the current version and provide details about the missing migrations.

### Migrating the Database

To migrate the database to the current version, run the `database migrate` CLI command. This will apply all migrations that have not been applied to the current installation.

To apply a single migration (if it has not been applied), you can pass the `--version` option (`-v` as a shortcut), for example `bin/jtracker database migrate --version=20160611001` would apply only the `20160611001` versioned migration.
