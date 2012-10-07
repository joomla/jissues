--
-- Table structure for table `#__select_items`
--

CREATE TABLE IF NOT EXISTS `#__select_items` (
  `extension_id` int(11) NOT NULL AUTO_INCREMENT,
  `option_id` int(10) NOT NULL,
  `value` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `#__select_items`
--

INSERT INTO `#__status` (`id`, `option_id`, `value`, `label`) VALUES
(1, 1, 'mysql_5_0', 'MySQL 5.0.x'),
(2, 1, 'mysql_5_1', 'MySQL 5.1.x'),
(3, 1, 'mysql_5_2', 'MySQL 5.2.x'),
(4, 1, 'mysql_5_5', 'MySQL 5.5.x'),
(5, 1, 'mssql_svr', 'Microsoft SQL Server'),
(6, 1, 'azure', 'Microsoft SQL Azure'),
(7, 1, 'postgres', 'PostgreSQL'),
(8, 1, 'oracle', 'Oracle'),
(9, 1, 'other', 'Other'),
(10, 2, 'apache_2_0', 'Apache 2.0.x'),
(11, 2, 'apache_2_2', 'Apache 2.2.x'),
(12, 2, 'apache_2_4', 'Apache 2.4.x'),
(13, 2, 'iis_7', 'IIS 7.x'),
(14, 3, 'php_5_2', 'PHP 5.2.x'),
(15, 3, 'php_5_3', 'PHP 5.3.x'),
(16, 3, 'php_5_4', 'PHP 5.4.x');

--
-- Table structure for table `#__selects`
--

CREATE TABLE IF NOT EXISTS `#__selects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `#__selects`
--

INSERT INTO `#__selects` (`id`, `name`) VALUES
(1, 'database'),
(2, 'webserver'),
(3, 'php'),
(4, 'browser');

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10000 ;

--
-- Dumping data for table `#__extensions`
--

INSERT INTO `#__extensions` (`extension_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`) VALUES
(1, 'com_tracker', 'component', 'com_tracker', '', 0, 1, 1, 1, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0);

--
-- Table structure for table `#__status`
--

CREATE TABLE IF NOT EXISTS `#__status` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `closed` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `gh_id` integer unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT '3',
  `catid` int(10) unsigned NOT NULL DEFAULT '0',
  `status` integer unsigned NOT NULL DEFAULT '1',
  `opened` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `closed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `patch_url` varchar(255) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`status`) REFERENCES `#__status` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__issue_comments`
--

CREATE TABLE IF NOT EXISTS `#__issue_comments` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `issue_id` integer unsigned NOT NULL,
  `submitter` varchar(255) NOT NULL DEFAULT '',
  `text` mediumtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`issue_id`) REFERENCES `#__issues` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `#__versions`
--

CREATE TABLE IF NOT EXISTS `#__versions` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `#__versions`
--

INSERT INTO `#__versions` (`id`, `version`) VALUES
(1, '2.5'),
(2, '3.0');
