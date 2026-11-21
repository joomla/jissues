## Testing Pull Requests on GitHub

Testers can report test results directly on GitHub with `/tested` commands. The tracker records the results and shows
them on GitHub. The feature is switched on per project; projects without the switch keep the tracker workflow unchanged.

### Commands

Comment on the pull request; the command has to start a line:

| Command | Who | Effect |
|---|---|---|
| `/tested ok [notes]` | everybody except the author | Records a successful test. Notes are optional. |
| `/tested fail <notes>` | everybody except the author | Records a failed test. Notes are required. |
| `/tested reset @user` | users with write access | Removes the results of a user. |
| `/tested reset` | users with write access | Removes all results of the pull request. |

`success`, `pass` and `passed` are accepted for `ok`; `failed`, `failure` and `unsuccessful` for `fail`.
The notes are the rest of the command line and all following lines. Commands in quotes (`>`) and code blocks are ignored.

Rules:

* One result per person: a new command replaces the earlier result of the same person.
* Editing a comment updates its result; removing the command from the comment or deleting the comment removes the result.
* Closed pull requests are not tested anymore.
* The bot confirms a recorded result with a :+1: reaction. A rejected command gets a :confused: reaction and a reply with the reason.

### Results on GitHub

* **Section in the pull request description.** The editbot appends a section below a horizontal rule at the end of the description.
  It contains a table of the results (current and outdated), an optional link to a prepared test environment and a folded
  "How to test this pull request" block. The section is enclosed in `<!-- jtracker:test-status:start -->` and
  `<!-- jtracker:test-status:end -->`; the text of the author outside the markers is never changed. If the author removes or
  changes the section, it is restored on the next edit.
* **Commit status** `JTracker/HumanTestResults` on the head commit: success with 2 or more successful tests and no failure,
  failure with 2 or more failed tests and no success, pending otherwise.
* **Labels** `Tested: 1` and `Tested: 2+`.
* **RTC** stays a manual decision. Maintainers set the `RTC` label on GitHub; the tracker follows it and sets the status
  "Ready to Commit" (removing the label sets the status back to "Pending").

The tracker stores the description without the section. The testing instructions are in `templates/tracker/github/test-instructions.md`.

### New commits

Results count for the code that was tested. When commits are pushed, the tracker compares a fingerprint of the diff of the
pull request (the patches of all files without the line numbers of the hunks):

* The diff did not change (rebase onto, or merge of, the base branch): the results are kept and count for the new head commit.
* The diff changed: the results are shown as outdated and should be repeated.

A change of the base branch in the context lines next to a change of the pull request also changes the diff, so in rare cases
results are outdated after a rebase.

### Test results from the tracker and the Patch Tester

* The "Test this" form of the tracker keeps working. It posts the `/tested` command with the GitHub account of the user and
  records the result right away. "Not tested" removes the own result.
* The "Alter test" form for maintainers records the result directly, without a comment.
* The Patch Tester (or any other tool) can post the command with the token of the user and report the test environment in a
  hidden block anywhere in the comment:

  ```
  /tested ok
  Works as described.

  <!-- jtracker-test-env {"joomla": "5.3.0", "php": "8.3.1", "db": "mysql 8.0", "sha": "<tested commit>"} -->
  ```

  Keys are letters, digits and underscores; values are strings or numbers. When `sha` names an older commit of the pull request,
  the result is recorded for that commit (and therefore shown as outdated). No tracker API is needed.

### Test environments

Links to prepared test environments are provided by an implementation of
`App\Tracker\Testing\TestEnvironmentProviderInterface` registered in the DI container under its interface name.
Without one, `NullTestEnvironmentProvider` is used and no link is shown.

### Setting up a project

1. The editbot of the project (`GitHub Editbot user` and `token` in the project settings) needs **write access** to the
   repository: editing the description of someone else's pull request requires it.
2. The repository webhook has to send `issues`, `pull_request`, `issue_comment` and `pull_request_review` events.
3. Enable "Record test results with `/tested` commands on GitHub" in the project settings.
4. Run `bin/jtracker update:testing -p <project id>` once. It calculates the diff fingerprints of the open pull requests,
   keeps the results recorded for their current head commits and writes the sections, statuses and labels.
   The command can be run again safely; `--issue=<number>` processes a single pull request.

### Proposed rollout

1. Merge with the switch off for all projects; nothing changes.
2. Pilot with `joomla-extensions/weblinks`: create the project, configure the editbot and the webhook, import it with
   `bin/jtracker get:project`, enable the switch and run `update:testing`.
3. Enable the switch for `joomla/joomla-cms` and announce the change to the testers.

Pilot checklist:

* `/tested ok` from a second account is recorded: section, status, `Tested: 1` label, tracker counts and activity points.
* `/tested ok` by the author and `/tested fail` without notes are rejected with a reply.
* The text of the author stays untouched; after the author edits the description, the section is restored.
* A rebase keeps the results; a pushed code change marks them as outdated.
* Adding and removing the `RTC` label changes the status in the tracker.
