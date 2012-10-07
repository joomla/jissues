--
-- Table structure for table `#__select_items`
--

CREATE TABLE `#__select_items` (
  `id` int(11) NOT NULL,
  `option_id` int(10) NOT NULL,
  `value` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `#__select_items`
--

INSERT INTO `#__select_items` VALUES(1, 1, 'mysql_5_0_x', 'MySql 5.0.x');
INSERT INTO `#__select_items` VALUES(2, 1, 'mysql_5_1_x', 'MySql 5.1.x');
INSERT INTO `#__select_items` VALUES(3, 1, 'mysql_5_2_x', 'MySql 5.2.x');
INSERT INTO `#__select_items` VALUES(4, 1, 'mssql_svr', 'MS SQL Svr');
INSERT INTO `#__select_items` VALUES(5, 1, 'azure', 'Azure');
INSERT INTO `#__select_items` VALUES(6, 1, 'postgres', 'Postgres');
INSERT INTO `#__select_items` VALUES(7, 1, 'oracle', 'Oracle');
INSERT INTO `#__select_items` VALUES(8, 1, 'other', 'Other');
INSERT INTO `#__select_items` VALUES(9, 2, 'apache_1_3_x', 'Apache 1.3.x');
INSERT INTO `#__select_items` VALUES(10, 2, 'apache_2_0_x', 'Apache 2.0.x');
INSERT INTO `#__select_items` VALUES(11, 2, 'apache_2_2_x', 'Apache 2.2.x');
INSERT INTO `#__select_items` VALUES(12, 2, 'iis_4_x', 'IIS 4.x');
INSERT INTO `#__select_items` VALUES(13, 2, 'iis_5_x', 'IIS 5.x');
INSERT INTO `#__select_items` VALUES(14, 2, 'iis_6_x', 'IIS 6.x');
INSERT INTO `#__select_items` VALUES(15, 2, 'iis_7_x', 'IIS 7.x');
INSERT INTO `#__select_items` VALUES(16, 2, 'other', 'Other');
INSERT INTO `#__select_items` VALUES(17, 3, 'php_4_3_x', 'PHP 4.3.x');
INSERT INTO `#__select_items` VALUES(18, 3, 'php_4_4_x', 'PHP 4.4.x');
INSERT INTO `#__select_items` VALUES(19, 2, 'php_5_0_x', 'PHP 5.0.x');
INSERT INTO `#__select_items` VALUES(20, 3, 'php_lt_5_2', 'PHP < 5.2');
INSERT INTO `#__select_items` VALUES(21, 3, 'php_5_1_x', 'PHP 5.1.x');
INSERT INTO `#__select_items` VALUES(22, 3, 'php_5_2_x', 'PHP 5.2.x');
INSERT INTO `#__select_items` VALUES(23, 3, 'php_5_3_X', 'PHP 5.3.x');
INSERT INTO `#__select_items` VALUES(24, 3, 'PHP_5_4_X', 'PHP 5.4.x');
INSERT INTO `#__select_items` VALUES(25, 4, 'ff_6_x', 'Firefox 6.x');
INSERT INTO `#__select_items` VALUES(26, 4, 'ff_5_x', 'Firefox 5.x');
INSERT INTO `#__select_items` VALUES(27, 4, 'ff_4_x', 'Firefox 4.x');
INSERT INTO `#__select_items` VALUES(28, 4, 'sf_5_x', 'Safari 5.x');
INSERT INTO `#__select_items` VALUES(29, 4, 'sf_ipod', 'Safari iPod');
INSERT INTO `#__select_items` VALUES(30, 4, 'sf_other', 'Safari Other');
INSERT INTO `#__select_items` VALUES(32, 4, 'sf_iphone', 'Safari iPhone');
INSERT INTO `#__select_items` VALUES(33, 4, 'sf_ipad', 'Safari iPad');
INSERT INTO `#__select_items` VALUES(34, 4, 'gc_14', 'Google Chrome 14 ');
INSERT INTO `#__select_items` VALUES(38, 4, 'ff_other', 'Firefox Other');

--
-- Table structure for table `#__selects`
--

CREATE TABLE IF NOT EXISTS `#__selects` (
  `id` integer NOT NULL AUTO_INCREMENT,
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
