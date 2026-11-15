CREATE TABLE IF NOT EXISTS `#__ai_reviews` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `project_id` int(11) NOT NULL COMMENT 'The Project id',
  `issue_id` int(11) unsigned NOT NULL COMMENT 'PK of the issue in issue table',
  `issue_number` int(11) unsigned NOT NULL COMMENT 'The GitHub issue or pull request number',
  `batch` varchar(32) NOT NULL COMMENT 'Identifier of the review run',
  `reviewed_date` datetime NOT NULL COMMENT 'When the review was made',
  `head_sha` varchar(40) NULL DEFAULT NULL COMMENT 'Reviewed pull request head commit',
  `description_hash` char(40) NOT NULL DEFAULT '' COMMENT 'SHA1 of the reviewed description',
  `source_updated` datetime NOT NULL COMMENT 'Last activity of the item at review time',
  `model` varchar(64) NOT NULL DEFAULT '' COMMENT 'The model used for the review',
  `summary` text NOT NULL COMMENT 'Short summary of the item',
  `state` varchar(16) NOT NULL DEFAULT 'active' COMMENT 'active or superseded',
  PRIMARY KEY (`id`),
  UNIQUE KEY `issue_batch` (`issue_id`, `batch`),
  KEY `project_state` (`project_id`, `state`),
  CONSTRAINT `#__ai_reviews_iifk` FOREIGN KEY (`issue_id`) REFERENCES `#__issues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `#__ai_reviews_pifk` FOREIGN KEY (`project_id`) REFERENCES `#__tracker_projects` (`project_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ai_review_findings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `review_id` int(11) unsigned NOT NULL COMMENT 'PK of the review in the ai_reviews table',
  `kind` varchar(32) NOT NULL COMMENT 'The type of the finding',
  `rating` varchar(16) NULL DEFAULT NULL COMMENT 'Rating for testing instructions and test suitability',
  `confidence` varchar(8) NOT NULL DEFAULT 'medium' COMMENT 'low, medium or high',
  `rationale` text NOT NULL COMMENT 'Reasoning and evidence',
  `related_number` int(11) unsigned NULL DEFAULT NULL COMMENT 'Number of the duplicate or fixing item',
  `proposal` mediumtext NULL COMMENT 'Proposed text',
  `state` varchar(16) NOT NULL DEFAULT 'open' COMMENT 'open, accepted, dismissed or done',
  `final_text` mediumtext NULL COMMENT 'The text actually sent by the admin',
  `processed_by` varchar(150) NULL DEFAULT NULL COMMENT 'Username of the admin who processed the finding',
  `processed_date` datetime NULL DEFAULT NULL COMMENT 'When the finding was processed',
  PRIMARY KEY (`id`),
  KEY `review_kind` (`review_id`, `kind`),
  KEY `kind_state` (`kind`, `state`),
  CONSTRAINT `#__ai_review_findings_rifk` FOREIGN KEY (`review_id`) REFERENCES `#__ai_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__issue_promotions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `issue_id` int(11) unsigned NOT NULL COMMENT 'PK of the issue in issue table',
  `label` varchar(100) NOT NULL COMMENT 'The label that triggered the promotion',
  `posted_date` datetime NOT NULL COMMENT 'When the item was posted to Mattermost',
  `posted_by` varchar(150) NOT NULL COMMENT 'GitHub login of the user who set the label',
  PRIMARY KEY (`id`),
  UNIQUE KEY `issue_label` (`issue_id`, `label`),
  CONSTRAINT `#__issue_promotions_iifk` FOREIGN KEY (`issue_id`) REFERENCES `#__issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
