--
-- Table setup order:
-- #__tracker_projects
-- #__tracker_labels
-- #__tracker_milestones
-- #__status
-- #__issues_relations_types
-- #__issues_tests
-- #__issues
-- #__activities
-- #__activity_types
-- #__users
-- #__accessgroups
-- #__user_accessgroup_map
-- #__issues_voting
-- #__articles
-- #__issues_categories
-- #__issue_category_map
-- #__migrations
--

--
-- Table structure for table `#__tracker_projects`
--

CREATE TABLE IF NOT EXISTS `#__tracker_projects` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `title` varchar(150) NOT NULL COMMENT 'Project title',
  `alias` varchar(150) NOT NULL COMMENT 'Project URL alias',
  `gh_user` varchar(150) NOT NULL COMMENT 'GitHub user',
  `gh_project` varchar(150) NOT NULL COMMENT 'GitHub project',
  `gh_editbot_user` varchar(150) NOT NULL COMMENT 'GitHub editbot username',
  `gh_editbot_pass` varchar(150) NOT NULL COMMENT 'GitHub editbot password',
  `ext_tracker_link` varchar(500) NOT NULL COMMENT 'A tracker link format (e.g. http://tracker.com/issue/%d)',
  `short_title` varchar(50) NOT NULL COMMENT 'Project short title',
  PRIMARY KEY (`project_id`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data `#__tracker_projects`
--

INSERT INTO `#__tracker_projects` (`project_id`, `title`, `alias`, `gh_user`, `gh_project`, `ext_tracker_link`, `short_title`) VALUES
(1, 'Joomla! CMS', 'joomla-cms', 'joomla', 'joomla-cms', 'http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=%d', 'CMS'),
(2, 'J!Tracker', 'jtracker', 'joomla', 'jissues', '', 'J!Tracker'),
(3, 'Joomla! Security', 'joomla-security', '', '', '', 'JSST');

-- --------------------------------------------------------

--
-- Table structure for table `#__tracker_labels`
--

CREATE TABLE IF NOT EXISTS `#__tracker_labels` (
  `label_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `project_id` int(11) NOT NULL COMMENT 'Project ID',
  `name` varchar(50) NOT NULL COMMENT 'Label name',
  `color` varchar(6) NOT NULL COMMENT 'Label color',
  PRIMARY KEY (`label_id`),
  KEY `name` (`name`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `#__tracker_labels_fk_project_id` FOREIGN KEY (`project_id`) REFERENCES `#__tracker_projects` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__tracker_milestones`
--

CREATE TABLE `#__tracker_milestones` (
  `milestone_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `milestone_number` int(11) NOT NULL COMMENT 'Milestone number from Github',
  `project_id` int(11) NOT NULL COMMENT 'Project ID',
  `title` varchar(50) NOT NULL COMMENT 'Milestone title',
  `description` mediumtext NOT NULL COMMENT 'Milestone description',
  `state` varchar(6) NOT NULL COMMENT 'Label state: open | closed',
  `due_on` datetime DEFAULT NULL COMMENT 'Date the milestone is due on.',
  PRIMARY KEY (`milestone_id`),
  KEY `name` (`title`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `#__tracker_milestones_fk_project_id` FOREIGN KEY (`project_id`) REFERENCES `#__tracker_projects` (`project_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__status`
--

CREATE TABLE IF NOT EXISTS `#__status` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `closed` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#__status`
--

INSERT INTO `#__status` (`id`, `status`, `closed`) VALUES
(1, 'New', 0),
(2, 'Confirmed', 0),
(3, 'Pending', 0),
(4, 'Ready to Commit', 0),
(5, 'Fixed in Code Base', 1),
(6, 'Needs Review', 0),
(7, 'Information Required', 0),
(8, 'Closed - Unconfirmed Report', 1),
(9, 'Closed - No Reply', 1),
(10, 'Closed', 1),
(11, 'Expected Behaviour', 1),
(12, 'Known Issue', 1),
(13, 'Duplicate Report', 1),
(14, 'Discussion', 1);

-- --------------------------------------------------------

--
-- Table structure for table `#__issues_relations_types`
--

CREATE TABLE IF NOT EXISTS `#__issues_relations_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data `#__issues_relations_types`
--

INSERT INTO `#__issues_relations_types` (`id`, `name`) VALUES
(1, 'duplicate_of'),
(2, 'related_to'),
(3, 'not_before'),
(4, 'pr_for');

--
-- Table structure for table `#__issues_tests`
--

CREATE TABLE IF NOT EXISTS `#__issues_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `item_id` int(11) NOT NULL COMMENT 'Item ID',
  `username` varchar(50) NOT NULL COMMENT 'User name',
  `result` smallint(6) NOT NULL COMMENT 'Test result (1=success, 2=failure)',
  `sha` varchar(40) DEFAULT NULL COMMENT 'The GitHub SHA where the issue has been tested against',
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__issues`
--

CREATE TABLE IF NOT EXISTS `#__issues` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `issue_number` int(11) unsigned DEFAULT NULL COMMENT 'THE issue number (ID)',
  `foreign_number` int(11) unsigned DEFAULT NULL COMMENT 'Foreign tracker id',
  `project_id` int(11) unsigned DEFAULT NULL COMMENT 'Project id',
  `milestone_id` int(11) unsigned DEFAULT NULL COMMENT 'Milestone id if applicable',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT 'Issue title',
  `description` mediumtext NOT NULL COMMENT 'Issue description',
  `description_raw` mediumtext NOT NULL COMMENT 'The raw issue description (markdown)',
  `priority` tinyint(4) NOT NULL DEFAULT 3 COMMENT 'Issue priority',
  `status` int(11) unsigned NOT NULL DEFAULT 1 COMMENT 'Issue status',
  `opened_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Issue open date',
  `opened_by` varchar(50) NULL DEFAULT NULL COMMENT 'Opened by username',
  `closed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Issue closed date',
  `closed_by` varchar(50) NULL DEFAULT NULL COMMENT 'Issue closed by username',
  `closed_sha` varchar(40) DEFAULT NULL COMMENT 'The GitHub SHA where the issue has been closed',
  `modified_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Issue modified date',
  `modified_by` varchar(50) NULL DEFAULT NULL COMMENT 'Issue modified by username',
  `rel_number` int(11) unsigned DEFAULT NULL COMMENT 'Relation issue number',
  `rel_type` int(11) unsigned DEFAULT NULL COMMENT 'Relation type',
  `has_code` tinyint(1)NOT NULL DEFAULT 0 COMMENT 'If the issue has code attached - aka a pull request',
  `pr_head_user` varchar(150) NOT NULL COMMENT 'Pull request head user',
  `pr_head_ref` varchar(150) NOT NULL COMMENT 'Pull request head ref',
  `pr_head_sha` varchar(40) NOT NULL COMMENT 'Pull request head SHA',
  `labels` varchar(250) NOT NULL COMMENT 'Comma separated list of label IDs',
  `build` varchar(40) NOT NULL DEFAULT '' COMMENT 'Build on which the issue is reported',
  `easy` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag whether an item is an easy test',
  `merge_state` varchar(50) NOT NULL COMMENT 'The merge state',
  `gh_merge_status` text NOT NULL COMMENT 'The GitHub merge status (JSON encoded)',
  `commits` text NOT NULL COMMENT 'Commits of the PR',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `issue_number` (`issue_number`),
  KEY `project_id` (`project_id`),
  KEY `milestone_id` (`milestone_id`,`project_id`),
  UNIQUE `issue_project_index`(`issue_number`, `project_id`),
  CONSTRAINT `#__issues_fk_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `#__tracker_milestones` (`milestone_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `#__issues_fk_status` FOREIGN KEY (`status`) REFERENCES `#__status` (`id`),
  CONSTRAINT `#__issues_fk_rel_type` FOREIGN KEY (`rel_type`) REFERENCES `#__issues_relations_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__activities`
--

CREATE TABLE IF NOT EXISTS `#__activities` (
  `activities_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `gh_comment_id` int(11) unsigned NULL COMMENT 'The GitHub comment id',
  `issue_number` int(11) unsigned NOT NULL COMMENT 'THE issue number (ID)',
  `project_id` int(11) NOT NULL COMMENT 'The Project id',
  `user` varchar(255) NOT NULL DEFAULT '' COMMENT 'The user name',
  `event` varchar(32) NOT NULL COMMENT 'The event type',
  `text` mediumtext NULL COMMENT 'The event text',
  `text_raw` mediumtext NULL COMMENT 'The raw event text',
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`activities_id`),
  KEY `issue_number` (`issue_number`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `#__activities_fk_issue_number` FOREIGN KEY (`issue_number`) REFERENCES `#__issues` (`issue_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `#__activities_fk_project_id` FOREIGN KEY (`project_id`) REFERENCES `#__tracker_projects` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `#__activity_types`
--

CREATE TABLE `#__activity_types` (
  `type_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `event` varchar(32) NOT NULL COMMENT 'The event type, referenced by the #__activities.event column',
  `activity_group` varchar(255) DEFAULT NULL,
  `activity_description` varchar(500) DEFAULT NULL,
  `activity_points` tinyint(4) DEFAULT NULL COMMENT 'Weighting for each type of activity',
  PRIMARY KEY (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `#__activity_types`
--

INSERT INTO `#__activity_types` (`type_id`, `event`, `activity_group`, `activity_description`, `activity_points`)
VALUES
  (1, 'open', 'Tracker', 'Create a new item on the tracker.', 3),
  (2, 'close', 'Tracker', 'Close an issue on the tracker.', 1),
  (3, 'comment', 'Tracker', 'Add a comment to an issue.', 1),
  (4, 'reopen', 'Tracker', 'Reopens an issue.', 1),
  (5, 'assign', 'Tracker', 'Assign an issue to a user', 1),
  (6, 'merge', 'Tracker', 'Merge a Pull Request', 2),
  (7, 'test_item', 'Test', 'Test an issue.', 5),
  (8, 'add_code', 'Code', 'Add a pull request to the tracker.', 5);


-- --------------------------------------------------------

--
-- Table structure for table `#__users`
--

CREATE TABLE IF NOT EXISTS `#__users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `name` varchar(400) NOT NULL DEFAULT '' COMMENT 'The users name',
  `username` varchar(150) NOT NULL DEFAULT '' COMMENT 'The users username',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT 'The users e-mail',
  `block` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'If the user is blocked',
  `sendEmail` tinyint(4) DEFAULT 0 COMMENT 'If the users recieves e-mail',
  `registerDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'The register date',
  `lastvisitDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'The last visit date',
  `params` text NOT NULL COMMENT 'Parameters',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`(100)),
  KEY `idx_block` (`block`),
  KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__accessgroups`
--

CREATE TABLE IF NOT EXISTS `#__accessgroups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `can_view` int(11) NOT NULL,
  `can_create` int(11) NOT NULL,
  `can_manage` int(11) NOT NULL,
  `can_edit` int(11) NOT NULL,
  `can_editown` int(11) NOT NULL,
  `system` int(11) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `#__accessgroups_fk_project_id` FOREIGN KEY (`project_id`) REFERENCES `#__tracker_projects` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#__accessgroups`
--

INSERT INTO `#__accessgroups` (`group_id`, `project_id`, `title`, `can_view`, `can_create`, `can_manage`, `can_edit`, `can_editown`, `system`) VALUES
(1, 1, 'Public', 1, 0, 0, 0, 0, 1),
(2, 1, 'User', 1, 1, 0, 0, 1, 1),
(3, 2, 'Public', 1, 0, 0, 0, 0, 1),
(4, 2, 'User', 1, 1, 0, 0, 1, 1),
(5, 3, 'Public', 0, 0, 0, 0, 0, 1),
(6, 3, 'User', 0, 1, 0, 0, 1, 1),
(7, 3, 'JSST', 1, 1, 0, 1, 0, 0),
(8, 3, 'JSST Managers', 1, 1, 1, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `#__user_accessgroup_map`
--

CREATE TABLE IF NOT EXISTS `#__user_accessgroup_map` (
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT 'Foreign Key to #__users.id',
  `group_id` int(11) NOT NULL DEFAULT 0 COMMENT 'Foreign Key to #__accessgroups.id',
  PRIMARY KEY (`user_id`,`group_id`),
  CONSTRAINT `#__user_accessgroup_map_fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `#__users` (`id`),
  CONSTRAINT `#__user_accessgroup_map_fk_group_id` FOREIGN KEY (`group_id`) REFERENCES `#__accessgroups` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__issues_voting`
--

CREATE TABLE IF NOT EXISTS `#__issues_voting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `issue_number` int(11) unsigned NOT NULL COMMENT 'Foreign key to #__issues.id',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT 'Foreign key to #__users.id',
  `experienced` tinyint(2) unsigned NOT NULL COMMENT 'Flag indicating whether the user has experienced the issue',
  `score` int(11) unsigned NOT NULL COMMENT 'User score for importance of issue',
  PRIMARY KEY (`id`),
  CONSTRAINT `#__issues_voting_fk_issue_id` FOREIGN KEY (`issue_number`) REFERENCES `#__issues` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `#__issues_voting_fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `#__users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `#__articles`
--

CREATE TABLE IF NOT EXISTS `#__articles` (
  `article_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `path` varchar(500) NOT NULL COMMENT 'The article path',
  `title` varchar(250) NOT NULL COMMENT 'The article title',
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'The article alias.',
  `text` text NOT NULL COMMENT 'The article text.',
  `text_md` text NOT NULL COMMENT 'The raw article text.',
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'The created date.',
  `is_file` int(1) unsigned NOT NULL COMMENT 'If the text is present as a file (for different handling)',
  PRIMARY KEY (`article_id`),
  KEY `alias` (`alias`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#__articles`
--

INSERT INTO `#__articles` (`title`, `alias`, `text`, `text_md`, `created_date`) VALUES
('The J!Tracker Project', 'about', '<p>Some info about the project here... @todo add more</p>', 'Some info about the project here...  @todo add more', '2013-10-01 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `#__issues_categories`
--
CREATE TABLE `#__issues_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `project_id` int(11) NOT NULL COMMENT 'The id of the Project',
  `title` varchar(150) NOT NULL COMMENT 'The title of the category',
  `alias` varchar(150) NOT NULL COMMENT 'The alias of the category',
  `color` varchar(6) NOT NULL COMMENT 'The hex value of the category',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `#__issues_categories_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `#__tracker_projects` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__issues_category_map`
--
CREATE TABLE `#__issue_category_map` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `issue_id` int(11) unsigned NOT NULL COMMENT 'PK of the issue in issue table',
  `category_id` int(11) unsigned NOT NULL COMMENT 'Category id',
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `#__issue_category_map_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `#__issues` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `#__issue_category_map_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `#__issues_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

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


-- --------------------------------------------------------

--
-- Table structure for table `#__migrations`
--
CREATE TABLE `#__migrations` (
  `version` varchar(25) NOT NULL COMMENT 'Applied migration versions',
  KEY `version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__migrations` (`version`) VALUES
('20160611001'),
('20160612001'),
('20160612002'),
('20170723001');
