--
-- Table structure for table `#__issues`
--

CREATE TABLE `#__issues` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `gh_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT '3',
  `catid` int(10) unsigned NOT NULL DEFAULT '0',
  `status` int(10) unsigned NOT NULL DEFAULT '1',
  `opened` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `closed` datetime DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
