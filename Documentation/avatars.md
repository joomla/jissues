## Avatar support

On the first log in, the application tries to fetch the users avatar and store it locally.

The command `cli/tracker.php retrieve avatars` will fetch the avatars for the users that appear in the `#__activities` table.

**This requires cURL.** - since a redirect is made which J\Http (or me) is unable to handle properly.
