ALTER TABLE `#__activities` MODIFY `gh_comment_id` bigint(20) unsigned NULL COMMENT 'The GitHub comment id';
ALTER TABLE `#__issue_reviews` MODIFY `review_id` bigint(20) unsigned NOT NULL COMMENT 'The GitHub ID of the review';
