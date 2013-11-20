## Avatar support

On the first log in, the application tries to fetch the users avatar and store it locally.

The command `cli/tracker.php get avatars` will fetch the avatars for the users that appear in the `#__activities` table.

**This requires cURL.** - since a redirect is made which `Joomla\Http\Http` is unable to handle properly.
