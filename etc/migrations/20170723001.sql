# Table for Pull Request Reviews

--
-- Table structure for table `#__issues_reviews`
--
CREATE TABLE `#__issue_reviews` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `issue_id` int(11) unsigned NOT NULL COMMENT 'PK of the issue in issue table',
  `project_id` int(11) NOT NULL COMMENT 'The Project id',
  `review_id` int(11) unsigned NOT NULL COMMENT 'The GitHub ID of the review',
  `review_state` int(11) unsigned NOT NULL COMMENT 'The ',
  `reviewed_by` varchar(150) NOT NULL COMMENT 'Reviewed by username',
  `review_comment` varchar(500) NULL DEFAULT NULL COMMENT 'The comment associated with the review',
  `review_submitted` datetime DEFAULT NULL COMMENT 'Date the review was last updated on.',
  `dismissed_by` varchar(150) NULL DEFAULT NULL COMMENT 'Reviewed by username',
  `dismissed_comment` varchar(500) NULL DEFAULT NULL COMMENT 'The comment associated with the review',
  `dismissed_on` datetime DEFAULT NULL COMMENT 'Date the review was dismissed on.',
  PRIMARY KEY (`id`),
  KEY `issue_number` (`issue_id`),
  KEY `project_id` (`project_id`),
  KEY `review_id` (`review_id`),
  KEY `issue_project_index`(`issue_id`, `project_id`),
  UNIQUE (`review_id`),
  CONSTRAINT `#__issue_reviews_iifk` FOREIGN KEY (`issue_id`) REFERENCES `#__issues` (`issue_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `#__issue_reviews_pifk` FOREIGN KEY (`project_id`) REFERENCES `#__tracker_projects` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
