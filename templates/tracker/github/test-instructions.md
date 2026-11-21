Everybody can test a pull request, you don't need special permissions. Thank you for helping!

1. Set up a test site and apply this pull request, for example with the [Joomla! Patch Tester](https://github.com/joomla-extensions/patchtester).
2. Follow the test instructions in the description above.
3. Report your result in a **new comment** on this pull request:
   * `/tested ok` if everything works as described. Notes are welcome.
   * `/tested fail` followed by a description of what went wrong. Notes are required for a failed test.

   For example:

   ```
   /tested fail
   The save button is missing in the toolbar after applying the patch.
   ```

Rules:

* The result counts for the code you tested. When the changes of the pull request are updated, earlier results are marked as outdated and should be repeated. Rebasing the pull request or merging the base branch does not reset the results.
* Each person has one result per pull request; a new comment replaces your earlier result. Editing or deleting your comment updates or removes your result.
* The author of the pull request ({author}) can't test their own pull request.
* {required} successful tests by different people are needed before a maintainer can mark the pull request as ready to commit (label `RTC`).
* Maintainers can reset results with `/tested reset @user` or `/tested reset`.

{bot} confirms a recorded result with a :+1: reaction on your comment.
