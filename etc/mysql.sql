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
  `system` int(11) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `#__accessgroups`
--

INSERT INTO `#__accessgroups` (`group_id`, `project_id`, `title`, `can_view`, `can_create`, `can_manage`,
                                 `can_edit`, `system`) VALUES
(1, 1, 'Public', 1, 0, 0, 0, 1),
(2, 1, 'User', 1, 1, 0, 0, 1),
(3, 2, 'Public', 1, 0, 0, 0, 1),
(4, 2, 'User', 1, 1, 0, 0, 1),
(5, 3, 'Public', 0, 0, 0, 0, 1),
(6, 3, 'User', 0, 0, 0, 0, 1),
(7, 3, 'JSST', 1, 1, 0, 1, 0),
(8, 3, 'JBS Managers', 1, 1, 1, 1, 0);

--
-- Table structure for table `#__user_accessgroup_map`
--

CREATE TABLE IF NOT EXISTS `#__user_accessgroup_map` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign Key to #__users.id',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign Key to #__usergroups.id',
  PRIMARY KEY (`user_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__status`
--

CREATE TABLE IF NOT EXISTS `#__status` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `closed` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=13;

--
-- Dumping data for table `#__status`
--

INSERT INTO `#__status` (`id`, `status`, `closed`) VALUES
(1, 'open', 0),
(2, 'confirmed', 0),
(3, 'pending', 0),
(4, 'rtc', 0),
(5, 'fixed', 1),
(6, 'review', 0),
(7, 'info', 0),
(8, 'platform', 1),
(9, 'no_reply', 1),
(10, 'closed', 1),
(11, 'expected', 1),
(12, 'known', 1);

--
-- Table structure for table `#__issues`
--

CREATE TABLE IF NOT EXISTS `#__issues` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `issue_number` integer unsigned DEFAULT NULL COMMENT 'THE issue number (ID)',
  `foreign_number` integer unsigned DEFAULT NULL COMMENT 'Foreign tracker id',
  `project_id` integer unsigned DEFAULT NULL COMMENT 'Project id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT 'Issue title',
  `description` mediumtext NOT NULL COMMENT 'Issue description',
  `description_raw` mediumtext NOT NULL COMMENT 'The raw issue description (markdown)',
  `priority` tinyint(4) NOT NULL DEFAULT '3' COMMENT 'Issue priority',
  `status` integer unsigned NOT NULL DEFAULT '1' COMMENT 'Issue status',
  `opened_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Issue open date',
  `opened_by` varchar(50) NULL DEFAULT NULL COMMENT 'Opened by username',
  `closed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Issue closed date',
  `closed_by` varchar(50) NULL DEFAULT NULL COMMENT 'Issue closed by username',
  `closed_sha` varchar(40) DEFAULT NULL COMMENT 'The GitHub SHA where the issue has been closed',
  `modified_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Issue modified date',
  `modified_by` varchar(50) NULL DEFAULT NULL COMMENT 'Issue modified by username',
  `rel_id` integer unsigned DEFAULT NULL COMMENT 'Relation id user',
  `rel_type` varchar(150) DEFAULT NULL COMMENT 'Relation type',
  `has_code` tinyint(1)NOT NULL DEFAULT '0' COMMENT 'If the issue has code attached - aka a pull request',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `issue_number` (`issue_number`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `#__issues_fk_status` FOREIGN KEY (`status`) REFERENCES `#__status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__activities`
--

CREATE TABLE IF NOT EXISTS `#__activities` (
  `activities_id` integer unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `gh_comment_id` integer unsigned NULL COMMENT 'The GitHub comment id',
  `issue_number` integer unsigned NOT NULL COMMENT 'THE issue number (ID)',
  `project_id` integer unsigned NOT NULL COMMENT 'The Project id',
  `user` varchar(255) NOT NULL DEFAULT '' COMMENT 'The user name',
  `event` varchar(32) NOT NULL COMMENT 'The event type',
  `text` mediumtext NULL COMMENT 'The event text',
  `text_raw` mediumtext NULL COMMENT 'The raw event text',
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`activities_id`),
  KEY `issue_number` (`issue_number`),
  CONSTRAINT `#__activities_fk_issue_number` FOREIGN KEY (`issue_number`) REFERENCES `#__issues` (`issue_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__issues_relations_types`
--

CREATE TABLE IF NOT EXISTS `#__issues_relations_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data `#__issues_relations_types`
--

INSERT INTO `#__issues_relations_types` (`id`, `name`) VALUES
(1, 'duplicate_of'),
(2, 'related_to'),
(3, 'not_before');

--
-- Table structure for table `#__tracker_fields_values`
--

CREATE TABLE IF NOT EXISTS `#__tracker_fields_values` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `issue_id` integer unsigned NOT NULL,
  `field_id` integer NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`),
  CONSTRAINT `#__tracker_fields_values_fk_issue_id` FOREIGN KEY (`issue_id`) REFERENCES `#__issues` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__tracker_projects`
--

CREATE TABLE IF NOT EXISTS `#__tracker_projects` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `title` varchar(150) NOT NULL COMMENT 'Project title',
  `alias` varchar(150) NOT NULL COMMENT 'Project URL alias',
  `gh_user` varchar(150) NOT NULL COMMENT 'GitHub user',
  `gh_project` varchar(150) NOT NULL COMMENT 'GitHub project',
  `ext_tracker_link` varchar(500) NOT NULL COMMENT 'A tracker link format (e.g. http://tracker.com/issue/%d)',
  PRIMARY KEY (`project_id`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data `#__tracker_projects`
--

INSERT INTO `#__tracker_projects` (`project_id`, `title`, `alias`, `gh_user`, `gh_project`,
                                     `ext_tracker_link`) VALUES
(1, 'Joomla! CMS 3 issues', 'joomla-cms-3-issues', 'joomla', 'joomla-cms', 'http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=%d'),
(2, 'J!Tracker Bugs', 'jtracker-bugs', 'joomla', 'jissues', ''),
(3, 'Joomla! Security', 'joomla-security', '', '', '');

--
-- Table structure for table `#__categories`
--

CREATE TABLE IF NOT EXISTS `#__categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lft` int(11) NOT NULL DEFAULT '0',
  `rgt` int(11) NOT NULL DEFAULT '0',
  `level` int(10) unsigned NOT NULL DEFAULT '0',
  `path` varchar(255) NOT NULL DEFAULT '',
  `extension` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `checked_out` int(11) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `modified_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `cat_idx` (`extension`,`published`,`access`),
  KEY `idx_access` (`access`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_path` (`path`),
  KEY `idx_left_right` (`lft`,`rgt`),
  KEY `idx_alias` (`alias`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=41;

--
-- Dumping data for table `#__categories`
--

INSERT INTO `#__categories` (`id`, `parent_id`, `lft`, `rgt`, `level`, `path`, `extension`, `title`, `alias`, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `created_user_id`, `created_time`, `modified_user_id`, `modified_time`, `version`) VALUES
(1, 0, 0, 82, 0, '', 'system', 'ROOT', 'root', '', 1, 0, '0000-00-00 00:00:00', 1, 42, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(2, 1, 0, 1, 1, 'joomla-cms-issue-tracker', 'com_tracker', 'Joomla! CMS Issue Tracker', 'joomla-cms-issue-tracker', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(6, 1, 8, 9, 1, 'php-version', 'com_tracker.fields', 'PHP Version', 'php-version', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(7, 1, 10, 11, 1, 'browser', 'com_tracker.fields', 'Browser', 'browser', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(8, 1, 12, 13, 1, 'web-server', 'com_tracker.fields', 'Web Server', 'web-server', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(9, 1, 14, 15, 1, 'database', 'com_tracker.fields', 'Database', 'database', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(10, 1, 16, 17, 1, '5-2-x', 'com_tracker.fields.6', '5.2.x', '5-2-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(11, 1, 18, 19, 1, '5-3-x', 'com_tracker.fields.6', '5.3.x', '5-3-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(12, 1, 20, 21, 1, '5-4-x', 'com_tracker.fields.6', '5.4.x', '5-4-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(13, 1, 22, 29, 1, 'internet-explorer', 'com_tracker.fields.7', 'Internet Explorer', 'internet-explorer', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(14, 1, 30, 37, 1, 'mozilla-firefox', 'com_tracker.fields.7', 'Mozilla Firefox', 'mozilla-firefox', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(15, 1, 38, 43, 1, 'google-chrome', 'com_tracker.fields.7', 'Google Chrome', 'google-chrome', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(16, 1, 44, 45, 1, 'safari', 'com_tracker.fields.7', 'Safari', 'safari', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(17, 13, 23, 24, 2, 'internet-explorer/internet-explorer-7', 'com_tracker.fields.7', 'Internet Explorer 7', 'internet-explorer-7', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(18, 13, 25, 26, 2, 'internet-explorer/internet-explorer-8', 'com_tracker.fields.7', 'Internet Explorer 8', 'internet-explorer-8', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(19, 13, 27, 28, 2, 'internet-explorer/internet-explorer-9', 'com_tracker.fields.7', 'Internet Explorer 9', 'internet-explorer-9', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(20, 14, 31, 32, 2, 'mozilla-firefox/firefox-14', 'com_tracker.fields.7', 'Firefox 14', 'firefox-14', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(21, 14, 33, 34, 2, 'mozilla-firefox/firefox-15', 'com_tracker.fields.7', 'Firefox 15', 'firefox-15', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(22, 14, 35, 36, 2, 'mozilla-firefox/firefox-16', 'com_tracker.fields.7', 'Firefox 16', 'firefox-16', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(23, 15, 39, 40, 2, 'google-chrome/chrome-desktop', 'com_tracker.fields.7', 'Chrome (Desktop)', 'chrome-desktop', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(24, 15, 41, 42, 2, 'google-chrome/chrome-mobile', 'com_tracker.fields.7', 'Chrome (Mobile)', 'chrome-mobile', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(25, 1, 46, 51, 1, 'apache', 'com_tracker.fields.8', 'Apache', 'apache', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(26, 1, 52, 55, 1, 'iis', 'com_tracker.fields.8', 'IIS', 'iis', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(27, 25, 47, 48, 2, 'apache/apache-2-2-x', 'com_tracker.fields.8', 'Apache 2.2.x', 'apache-2-2-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(28, 25, 49, 50, 2, 'apache/apache-2-4-x', 'com_tracker.fields.8', 'Apache 2.4.x', 'apache-2-4-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(29, 26, 53, 54, 2, 'iis/iis-7', 'com_tracker.fields.8', 'IIS 7', 'iis-7', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(30, 1, 56, 63, 1, 'mysql', 'com_tracker.fields.9', 'MySQL', 'mysql', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(31, 1, 64, 67, 1, 'microsoft-sql-server', 'com_tracker.fields.9', 'Microsoft SQL Server', 'microsoft-sql-server', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(32, 1, 68, 77, 1, 'postgresql', 'com_tracker.fields.9', 'PostgreSQL', 'postgresql', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(33, 30, 57, 58, 2, 'mysql/mysql-5-0-x', 'com_tracker.fields.9', 'MySQL 5.0.x', 'mysql-5-0-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(34, 30, 59, 60, 2, 'mysql/mysql-5-1-x', 'com_tracker.fields.9', 'MySQL 5.1.x', 'mysql-5-1-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(35, 30, 61, 62, 2, 'mysql/mysql-5-5-x', 'com_tracker.fields.9', 'MySQL 5.5.x', 'mysql-5-5-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(36, 31, 65, 66, 2, 'microsoft-sql-server/sql-server-2008-r2-10-50-1600-1', 'com_tracker.fields.9', 'SQL Server 2008 R2 (10.50.1600.1)', 'sql-server-2008-r2-10-50-1600-1', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(37, 32, 69, 70, 2, 'postgresql/postgresql-8-3-x', 'com_tracker.fields.9', 'PostgreSQL 8.3.x', 'postgresql-8-3-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(38, 32, 71, 72, 2, 'postgresql/postgresql-8-4-x', 'com_tracker.fields.9', 'PostgreSQL 8.4.x', 'postgresql-8-4-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(39, 32, 73, 74, 2, 'postgresql/postgresql-9-0-x', 'com_tracker.fields.9', 'PostgreSQL 9.0.x', 'postgresql-9-0-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(40, 32, 75, 76, 2, 'postgresql/postgresql-9-1-x', 'com_tracker.fields.9', 'PostgreSQL 9.1.x', 'postgresql-9-1-x', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2012-10-20 12:00:00', 0, '0000-00-00 00:00:00', 1),
(41, 1, 78, 79, 1, 'build', 'com_tracker.textfields', 'Build', 'build', 'The build number where the issue occurs.', 1, 0, '0000-00-00 00:00:00', 4, 1, '2012-10-21 21:19:08', 0, '0000-00-00 00:00:00', 1),
(42, 1, 80, 81, 1, 'easy', 'com_tracker.checkboxes', 'Easy', 'easy', 'Is this an "easy" issue ?', 1, 1, '2012-10-21 21:21:04', 4, 1, '2012-10-21 21:20:28', 0, '0000-00-00 00:00:00', 1),
(43, 1, 83, 84, 1, 'category', 'com_tracker.fields', 'Category', 'category', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2013-01-07 23:17:33', 0, '0000-00-00 00:00:00', 1),
(44, 1, 85, 86, 1, 'languages', 'com_tracker.fields.43', 'Languages', 'languages', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2013-01-07 23:18:04', 0, '0000-00-00 00:00:00', 1),
(45, 1, 87, 88, 1, 'components', 'com_tracker.fields.43', 'Components', 'components', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2013-01-07 23:18:44', 0, '0000-00-00 00:00:00', 1),
(46, 1, 89, 90, 1, 'templates', 'com_tracker.fields.43', 'Templates', 'templates', '', 1, 0, '0000-00-00 00:00:00', 1, 1, '2013-01-07 23:18:51', 0, '0000-00-00 00:00:00', 1);

--
-- Tables below are core Platform/CMS tables
--

-- --------------------------------------------------------

--
-- Table structure for table `#__session`
--

CREATE TABLE IF NOT EXISTS `#__session` (
  `session_id` varchar(200) NOT NULL DEFAULT '',
  `client_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `guest` tinyint(4) unsigned DEFAULT '1',
  `time` varchar(14) DEFAULT '',
  `data` mediumtext,
  `userid` int(11) DEFAULT '0',
  `username` varchar(150) DEFAULT '',
  PRIMARY KEY (`session_id`),
  KEY `userid` (`userid`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `#__users`
--

CREATE TABLE IF NOT EXISTS `#__users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT 'The users name',
  `username` varchar(150) NOT NULL DEFAULT '' COMMENT 'The users username',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT 'The users e-mail',
  `block` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'If the user is blocked',
  `sendEmail` tinyint(4) DEFAULT '0' COMMENT 'If the users recieves e-mail',
  `registerDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'The register date',
  `lastvisitDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'The last visit date',
  `params` text NOT NULL COMMENT 'Parameters',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_block` (`block`),
  KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `#__languages`
--

CREATE TABLE IF NOT EXISTS `#__languages` (
  `lang_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lang_code` char(7) NOT NULL,
  `title` varchar(50) NOT NULL,
  `title_native` varchar(50) NOT NULL,
  `sef` varchar(50) NOT NULL,
  `image` varchar(50) NOT NULL,
  `description` varchar(512) NOT NULL,
  `metakey` text NOT NULL,
  `metadesc` text NOT NULL,
  `sitename` varchar(1024) NOT NULL DEFAULT '',
  `published` int(11) NOT NULL DEFAULT '0',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lang_id`),
  UNIQUE KEY `idx_sef` (`sef`),
  UNIQUE KEY `idx_image` (`image`),
  UNIQUE KEY `idx_langcode` (`lang_code`),
  KEY `idx_access` (`access`),
  KEY `idx_ordering` (`ordering`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `#__languages`
--

INSERT INTO `#__languages` (`lang_id`, `lang_code`, `title`, `title_native`, `sef`, `image`, `description`, `metakey`, `metadesc`, `sitename`, `published`, `access`, `ordering`) VALUES
(1, 'en-GB', 'English (UK)', 'English (UK)', 'en', 'en', '', '', '', '', 1, 0, 1);

--
-- Dumping data for table `#__articles`
--

CREATE TABLE IF NOT EXISTS `#__articles` (
  `article_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `title` varchar(250) NOT NULL COMMENT 'The article title',
  `alias` varchar(250) NOT NULL COMMENT 'The article alias.',
  `text` text NOT NULL COMMENT 'The article text.',
  `text_md` text NOT NULL COMMENT 'The raw article text.',
  `created_date` datetime NOT NULL COMMENT 'The created date.',
  PRIMARY KEY (`article_id`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `#__articles`
(`title`, `alias`, `text`, `text_md`, `created_date`) VALUES
('The J!Tracker Project', 'about', '<p>Some info about the project here...</p>', 'Some info about the project here...', '2013-06-18 20:20:41');
