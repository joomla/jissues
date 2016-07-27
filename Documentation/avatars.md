## Avatar support

On the first log in, the application tries to fetch the users avatar and store it locally.

The command `get avatars` will fetch the avatars for the users that appear in the `#__activities` table.

```sh

$ bin/jtracker get avatars
------------------------------------------------------------
              Joomla! Tracker CLI Application
                         1.0.0-beta
------------------------------------------------------------
------------------------------------------------------------
                      Retrieve Avatars
------------------------------------------------------------

```

Note: This and all remote HTTP requests **requires cURL.**
