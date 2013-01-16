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
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` integer unsigned NOT NULL default '0',
  `gh_id` integer unsigned DEFAULT NULL,
  `jc_id` integer unsigned DEFAULT NULL,
  `project_id` integer unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT '3',
  `status` integer unsigned NOT NULL DEFAULT '1',
  `opened` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `closed_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `closed_sha` varchar(40) DEFAULT NULL COMMENT 'The GitHub SHA where the issue has been closed',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` varchar(255) NULL DEFAULT NULL, 
  `patch_url` varchar(255) NULL,
  `rel_id` integer unsigned DEFAULT NULL COMMENT 'Relation id user',
  `rel_type` varchar(150) DEFAULT NULL COMMENT 'Relation type',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  CONSTRAINT `#__issues_fk_status` FOREIGN KEY (`status`) REFERENCES `#__status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__activity`
--

CREATE TABLE IF NOT EXISTS `#__activity` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `gh_comment_id` integer unsigned NULL,
  `issue_id` integer unsigned NOT NULL,
  `user` varchar(255) NOT NULL DEFAULT '',
  `event` varchar(32) NOT NULL,
  `text` mediumtext NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`),
  CONSTRAINT `#__activity_fk_issue_id` FOREIGN KEY (`issue_id`) REFERENCES `#__issues` (`id`)
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
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `alias` varchar(150) NOT NULL,
  `gh_user` varchar(150) NOT NULL COMMENT 'GitHub user',
  `gh_project` varchar(150) NOT NULL COMMENT 'GitHub project',
  `ext_tracker_link` varchar(500) NOT NULL COMMENT 'A tracker link format (e.g. http://tracker.com/issue/%d)',
  PRIMARY KEY (`project_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=54 ;

--
-- Dumping data `#__tracker_projects`
--

INSERT INTO `#__tracker_projects` (`project_id`, `title`, `alias`, `gh_user`, `gh_project`, `ext_tracker_link`) VALUES
(1, 'Joomla! CMS 3 issues', 'joomla-cms-3-issues', 'joomla', 'joomla-cms', 'http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=%d'),
(2, 'J!Tracker Bugs', 'jtracker-bugs', 'JTracker', 'jissues', '');

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

--
-- Table structure for table `#__assets`
--

CREATE TABLE IF NOT EXISTS `#__assets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set parent.',
  `lft` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set lft.',
  `rgt` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set rgt.',
  `level` int(10) unsigned NOT NULL COMMENT 'The cached level in the nested tree.',
  `name` varchar(50) NOT NULL COMMENT 'The unique name for the asset.\n',
  `title` varchar(100) NOT NULL COMMENT 'The descriptive title for the asset.',
  `rules` varchar(5120) NOT NULL COMMENT 'JSON encoded access control.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_asset_name` (`name`),
  KEY `idx_lft_rgt` (`lft`,`rgt`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8;

--
-- Dumping data for table `#__assets`
--

INSERT INTO `#__assets` (`id`, `parent_id`, `lft`, `rgt`, `level`, `name`, `title`, `rules`) VALUES
(1, 0, 1, 12, 0, 'root.1', 'Root Asset', '{"core.login.site":{"6":1,"2":1},"core.login.admin":{"6":1},"core.login.offline":{"6":1},"core.admin":{"8":1},"core.manage":{"7":1},"core.create":{"6":1,"2":1},"core.delete":{"6":1},"core.edit":{"6":1,"4":1},"core.edit.state":{"6":1,"5":1},"core.edit.own":{"6":1,"3":1}}'),
(2, 1, 2, 3, 1, 'com_tracker', 'com_tracker', '{"core.admin":{"7":1},"core.manage":[],"core.create":[],"core.delete":[],"core.edit":[],"core.edit.state":[]}'),
(3, 1, 4, 5, 1, 'com_cpanel', 'com_cpanel', '{}'),
(4, 1, 6, 7, 1, 'com_languages', 'com_languages', '{"core.admin":{"7":1},"core.manage":[],"core.create":[],"core.delete":[],"core.edit":[],"core.edit.state":[]}'),
(5, 1, 8, 9, 1, 'com_login', 'com_login', '{}'),
(6, 1, 10, 11, 1, 'com_users', 'com_users', '{"core.admin":{"7":1},"core.manage":[],"core.create":[],"core.delete":[],"core.edit":[],"core.edit.state":[]}');

--
-- Table structure for table `#__extensions`
--

CREATE TABLE IF NOT EXISTS `#__extensions` (
  `extension_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `element` varchar(100) NOT NULL,
  `folder` varchar(100) NOT NULL,
  `client_id` tinyint(3) NOT NULL,
  `enabled` tinyint(3) NOT NULL DEFAULT '1',
  `access` int(10) unsigned NOT NULL DEFAULT '1',
  `protected` tinyint(3) NOT NULL DEFAULT '0',
  `manifest_cache` text NOT NULL,
  `params` text NOT NULL,
  `custom_data` text NOT NULL,
  `system_data` text NOT NULL,
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` int(11) DEFAULT '0',
  `state` int(11) DEFAULT '0',
  PRIMARY KEY (`extension_id`),
  KEY `element_clientid` (`element`,`client_id`),
  KEY `element_folder_clientid` (`element`,`folder`,`client_id`),
  KEY `extension` (`type`,`element`,`folder`,`client_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10000;

--
-- Dumping data for table `#__extensions`
--

INSERT INTO `#__extensions` (`extension_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`) VALUES
(1, 'com_tracker', 'component', 'com_tracker', '', 0, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(2, 'com_cpanel', 'component', 'com_cpanel', '', 1, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(3, 'com_languages', 'component', 'com_languages', '', 1, 1, 1, 1, '', '{"administrator":"en-GB","site":"en-GB"}', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(4, 'com_login', 'component', 'com_login', '', 1, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(5, 'com_users', 'component', 'com_users', '', 1, 1, 0, 1, '', '{"allowUserRegistration":"1","new_usertype":"2","guest_usergroup":"9","sendpassword":"1","useractivation":"1","mail_to_admin":"0","captcha":"","frontend_userparams":"1","site_language":"0","change_login_name":"0","reset_count":"10","reset_time":"1","mailSubjectPrefix":"","mailBodySuffix":""}', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(6, 'com_categories', 'component', 'com_categories', '', 1, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(201, 'mod_toolbar', 'module', 'mod_toolbar', '', 0, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(301, 'mod_login', 'module', 'mod_login', '', 1, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(302, 'mod_menu', 'module', 'mod_menu', '', 1, 1, 1, 0, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(303, 'mod_toolbar', 'module', 'mod_toolbar', '', 1, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(401, 'plg_authentication_joomla', 'plugin', 'joomla', 'authentication', 0, 1, 1, 1, '', '{}', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(402, 'plg_user_joomla', 'plugin', 'joomla', 'user', 0, 1, 1, 0, '', '{"autoregister":"1"}', '', '', 0, '0000-00-00 00:00:00', 2, 0),
(403, 'plg_editors_none', 'plugin', 'none', 'editors', 0, 1, 1, 1, '', '{}', '', '', 0, '0000-00-00 00:00:00', 2, 0),
(451, 'plg_editors_kisskontent', 'plugin', 'kisskontent', 'editors', 0, 1, 1, 1, '', '{}', '', '', 0, '0000-00-00 00:00:00', 2, 0),
(600, 'English (United Kingdom)', 'language', 'en-GB', '', 0, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0),
(601, 'English (United Kingdom)', 'language', 'en-GB', '', 1, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0);

--
-- Table structure for table `#__messages`
--

CREATE TABLE IF NOT EXISTS `#__messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id_from` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id_to` int(10) unsigned NOT NULL DEFAULT '0',
  `folder_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `date_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `priority` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `useridto_state` (`user_id_to`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__messages_cfg`
--

CREATE TABLE IF NOT EXISTS `#__messages_cfg` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `cfg_name` varchar(100) NOT NULL DEFAULT '',
  `cfg_value` varchar(255) NOT NULL DEFAULT '',
  UNIQUE KEY `idx_user_var_name` (`user_id`,`cfg_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__modules`
--

CREATE TABLE IF NOT EXISTS `#__modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '',
  `note` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '0',
  `position` varchar(50) NOT NULL DEFAULT '',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `module` varchar(50) DEFAULT NULL,
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `showtitle` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `params` text NOT NULL,
  `client_id` tinyint(4) NOT NULL DEFAULT '0',
  `language` char(7) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `published` (`published`,`access`),
  KEY `newsfeeds` (`module`,`published`),
  KEY `idx_language` (`language`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4;

--
-- Dumping data for table `#__modules`
--

INSERT INTO `#__modules` (`id`, `title`, `note`, `content`, `ordering`, `position`, `checked_out`, `checked_out_time`, `publish_up`, `publish_down`, `published`, `module`, `access`, `showtitle`, `params`, `client_id`, `language`) VALUES
(1, 'Login', '', '', 1, 'login', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 'mod_login', 1, 1, '', 1, '*'),
(2, 'Admin Toolbar', '', '', 1, 'toolbar', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 'mod_toolbar', 3, 1, '', 1, '*'),
(3, 'Admin Menu', '', '', 1, 'menu', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 'mod_menu', 3, 1, '{"layout":"","moduleclass_sfx":"","shownew":"1","showhelp":"1","cache":"0"}', 1, '*'),
(4, 'Site Toolbar', '', '', 1, 'toolbar', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 'mod_toolbar', 1, 1, '', 0, '*');

-- --------------------------------------------------------

--
-- Table structure for table `#__modules_menu`
--

CREATE TABLE IF NOT EXISTS `#__modules_menu` (
  `moduleid` int(11) NOT NULL DEFAULT '0',
  `menuid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`moduleid`,`menuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `#__modules_menu`
--

INSERT INTO `#__modules_menu` (`moduleid`, `menuid`) VALUES
(1, 0),
(2, 0),
(3, 0),
(4, 0);

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

--
-- Table structure for table `#__usergroups`
--

CREATE TABLE IF NOT EXISTS `#__usergroups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Adjacency List Reference Id',
  `lft` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set lft.',
  `rgt` int(11) NOT NULL DEFAULT '0' COMMENT 'Nested set rgt.',
  `title` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_usergroup_parent_title_lookup` (`parent_id`,`title`),
  KEY `idx_usergroup_title_lookup` (`title`),
  KEY `idx_usergroup_adjacency_lookup` (`parent_id`),
  KEY `idx_usergroup_nested_set_lookup` (`lft`,`rgt`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10;

--
-- Dumping data for table `#__usergroups`
--

INSERT INTO `#__usergroups` (`id`, `parent_id`, `lft`, `rgt`, `title`) VALUES
(1, 0, 1, 18, 'Public'),
(2, 1, 8, 15, 'Registered'),
(3, 2, 9, 14, 'Author'),
(4, 3, 10, 13, 'Editor'),
(5, 4, 11, 12, 'Publisher'),
(6, 1, 4, 7, 'Manager'),
(7, 6, 5, 6, 'Administrator'),
(8, 1, 16, 17, 'Super Users'),
(9, 1, 2, 3, 'Guest');

-- --------------------------------------------------------

--
-- Table structure for table `#__users`
--

CREATE TABLE IF NOT EXISTS `#__users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(150) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(100) NOT NULL DEFAULT '',
  `block` tinyint(4) NOT NULL DEFAULT '0',
  `sendEmail` tinyint(4) DEFAULT '0',
  `registerDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastvisitDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `activation` varchar(100) NOT NULL DEFAULT '',
  `params` text NOT NULL,
  `lastResetTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Date of last password reset',
  `resetCount` int(11) NOT NULL DEFAULT '0' COMMENT 'Count of password resets since lastResetTime',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_block` (`block`),
  KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__user_notes`
--

CREATE TABLE IF NOT EXISTS `#__user_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `catid` int(10) unsigned NOT NULL DEFAULT '0',
  `subject` varchar(100) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `checked_out` int(10) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_user_id` int(10) unsigned NOT NULL,
  `modified_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `review_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `#__user_usergroup_map`
--

CREATE TABLE IF NOT EXISTS `#__user_usergroup_map` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign Key to #__users.id',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign Key to #__usergroups.id',
  PRIMARY KEY (`user_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__viewlevels`
--

CREATE TABLE IF NOT EXISTS `#__viewlevels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `title` varchar(100) NOT NULL DEFAULT '',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `rules` varchar(5120) NOT NULL COMMENT 'JSON encoded access control.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_assetgroup_title_lookup` (`title`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5;

--
-- Dumping data for table `#__viewlevels`
--

INSERT INTO `#__viewlevels` (`id`, `title`, `ordering`, `rules`) VALUES
(1, 'Public', 0, '[1]'),
(2, 'Registered', 1, '[6,2,8]'),
(3, 'Special', 2, '[6,3,8]'),
(4, 'Guest', 0, '[9]');

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
