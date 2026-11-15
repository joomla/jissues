## AI reviews

### Purpose

Reviews of open issues and pull requests are made outside the tracker, by a separate review tool that runs locally. The tracker doesn't run any AI. It stores the imported results and lets site admins process them: adopt improved testing instructions, confirm duplicates or items fixed elsewhere, and dismiss or mark findings as done.

Nothing is written to GitHub without an admin's action, and every GitHub change is made with that admin's own token.

### Tables

| Table | Content |
|---|---|
| `#__ai_reviews` | One row per reviewed item and review run (`batch`) |
| `#__ai_review_findings` | The findings of a review |
| `#__issue_promotions` | Items that were posted to the testing channel, one row per item and label |

#### `#__ai_reviews`

| Column | Meaning |
|---|---|
| `project_id`, `issue_id`, `issue_number` | The reviewed item. `issue_id` is `#__issues.id` |
| `batch` | Identifier of the review run, for example `20261120-01`. Unique together with `issue_id` |
| `reviewed_date` | When the review was made (UTC) |
| `head_sha` | Pull requests: the head commit the review is based on. `NULL` for issues |
| `description_hash` | `sha1(str_replace("\r\n", "\n", description_raw))` of the reviewed description (`NULL` counts as an empty string), see `AiReviewsTable::hashDescription()`. The list computes the same value in SQL |
| `source_updated` | The latest of the item's `modified_date` and its latest activity at review time (UTC) |
| `model`, `summary` | The model used and a one-sentence summary of the item |
| `state` | `active`, or `superseded` once a newer review of the same item was imported |

#### `#__ai_review_findings`

| Column | Meaning |
|---|---|
| `review_id` | The review |
| `kind` | `testing_instructions`, `test_suitability`, `duplicate`, `fixed_elsewhere`, `obsolete`, `abandoned` or `needs_info` |
| `rating` | `testing_instructions`: `missing`, `inadequate`, `good` or `na`. `test_suitability`: `yes`, `partly` or `no`. Otherwise `NULL` |
| `confidence` | `low`, `medium` or `high` |
| `rationale` | Reasoning and evidence |
| `related_number` | `duplicate`: the other item. `fixed_elsewhere`: the fixing pull request. Optional for `obsolete` |
| `proposal` | Markdown: the improved testing instructions, or the comment text for `duplicate`, `fixed_elsewhere` and `needs_info` |
| `state` | `open`, `accepted`, `dismissed` or `done` |
| `final_text`, `processed_by`, `processed_date` | Filled in when an admin processes the finding. `final_text` is the text that was actually sent |

### When a review is shown

The list only shows active reviews of open items, and it hides a review as soon as the item changed after the review:

* the head commit of the pull request differs from `head_sha`,
* the description no longer matches `description_hash`, or
* someone commented, tested or reopened the item after `source_updated`.

Label changes and other metadata don't hide a review. A hidden review stays in the database; the next review run reviews the item again and supersedes it.

### Actions

All actions are for site admins and are sent as POST requests to `aireview/<project alias>/<action>` with the `findingId`. Changes on GitHub are made with the admin's own token; if GitHub refuses one, the admin sees the error and nothing is changed.

| Action | For | What it does |
|---|---|---|
| `process` | every finding | Sets the finding to `dismissed` or `done` (`state`). Nothing changes on GitHub |
| `instructions` | `testing_instructions` of pull requests | Replaces the sections of the pull request description that have the same headings as the edited proposal (`text`), see `DescriptionSectionsHelper`. Refused if the description on GitHub changed since the review |
| `close` | `duplicate` and `fixed_elsewhere` | Posts the edited comment (`text`) and closes the item on GitHub. In the tracker the item gets the status "Duplicate Report" with the relation "duplicate of", or "Fixed in Code Base" with the relation "related to" the fixing pull request |
| `promote` | `test_suitability` of pull requests | Sets the promotion label (see below) on GitHub |

### Promotion in the testing channel

A pull request with the promotion label is posted once to a Mattermost channel, so that people test it. The label is configured in `etc/config.json`:

```json
"mattermost": {
	"webhook_url"  : "https://chat.example.org/hooks/…",
	"channel"      : "",
	"promote_label": "Testers wanted"
}
```

* An empty `webhook_url` turns the posting off. An empty `channel` posts to the default channel of the incoming webhook.
* The post is triggered by the `labeled` webhook of the pull request (in `JoomlacmsPullsListener`), whether the label was set in the tracker or directly on GitHub, so it is posted exactly once. Removing and setting the label again doesn't post it again (`#__issue_promotions`); if posting fails, the next time the label is set tries again.
* The message links the tracker and GitHub and shows the number of successful tests of the current commit, the test setup from the AI review, who set the label, and up to 15 lines of the testing instructions.
* In the tracker, only site admins may set or remove the label. Users with the edit permission keep all other label changes. On GitHub, everybody who may label the pull request can set it.

### Import format

The review tool exports an SQL file that is imported with `mysql` or phpMyAdmin. The table prefix is written out in the file. The file:

1. runs in one transaction;
2. inserts each review with `INSERT … SELECT`, resolving `issue_id` and `project_id` through the GitHub owner, repository and issue number, so database IDs may differ between the machine that ran the review and the live site. Items that don't exist are skipped;
3. ignores a review that already exists for (`issue_id`, `batch`) through `ON DUPLICATE KEY UPDATE`, with the table name in front of `id`, because the SELECT joins `#__issues`, which has an `id` column too. It inserts a finding only if the review doesn't have one of the same `kind` and `related_number` yet, so importing a file twice is harmless;
4. sets older active reviews of the same items to `superseded`.

Example for one item, with the prefix `jos_`:

```sql
START TRANSACTION;

INSERT INTO `jos_ai_reviews`
  (project_id, issue_id, issue_number, batch, reviewed_date, head_sha, description_hash, source_updated, model, summary, state)
SELECT p.project_id, i.id, 48496, '20261120-01', '2026-11-20 10:15:00', 'a1b2c3…', '5d41402a…', '2026-11-15 08:33:34',
       'claude-opus-5-5', 'Gives the CodeMirror content an accessible name.', 'active'
FROM `jos_issues` i
JOIN `jos_tracker_projects` p ON p.project_id = i.project_id
WHERE p.gh_user = 'joomla' AND p.gh_project = 'joomla-cms' AND i.issue_number = 48496
ON DUPLICATE KEY UPDATE `jos_ai_reviews`.id = `jos_ai_reviews`.id;

INSERT INTO `jos_ai_review_findings` (review_id, kind, rating, confidence, rationale, related_number, proposal, state)
SELECT r.id, 'testing_instructions', 'inadequate', 'high', '…', NULL, '…', 'open'
FROM `jos_ai_reviews` r
JOIN `jos_issues` i ON i.id = r.issue_id
JOIN `jos_tracker_projects` p ON p.project_id = i.project_id
WHERE p.gh_user = 'joomla' AND p.gh_project = 'joomla-cms' AND i.issue_number = 48496 AND r.batch = '20261120-01'
  AND NOT EXISTS (
    SELECT 1 FROM `jos_ai_review_findings` f
    WHERE f.review_id = r.id AND f.kind = 'testing_instructions' AND f.related_number <=> NULL
  );

UPDATE `jos_ai_reviews` r
JOIN `jos_issues` i ON i.id = r.issue_id
JOIN `jos_tracker_projects` p ON p.project_id = i.project_id
SET r.state = 'superseded'
WHERE p.gh_user = 'joomla' AND p.gh_project = 'joomla-cms' AND i.issue_number = 48496
  AND r.batch <> '20261120-01' AND r.state = 'active';

COMMIT;
```
