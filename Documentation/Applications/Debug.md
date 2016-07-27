## Debug Application

### Purpose

Debug and profile the application, manage log files.

### Functionality

* A dedicated class for debugging, profiling and logging.
* Database logging to a log file to log queries even during redirects.
* Exception rendering including clickable file links using the xdebug protocol.

#### Log files

To activate or deactivate logging use the `debug.logging` option in `etc/config.json`.

Supported log events:
* application
* cron jobs
* database queries
* GitHub issues
* GitHub comments
* GitHub pull requests
* PHP error log

The supported "events" are written to separate log files.
Unsupported events go to the `error.log`.
