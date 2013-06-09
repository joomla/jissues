## Debugging and Logging

Added a TrackerDebugger class that... handles debugging :P
Added a CallbackLogger class to provide database query logging.
A log file can be written to log queries even during redirects ;)
Exception rendering has been improved to include clickable file links using the xdebug protocol.

### Log files
To activate or deactivate logging use the `debug.logging` option in `etc/config.json`.

Supported log events:
* 403
* 404
* 500
* database queries

The supported "events" will be written to their proper log file. Unsupported events go to the `500.log`.
