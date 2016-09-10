# Add releases table
CREATE TABLE `#__releases` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `release_id` int(11) unsigned NOT NULL COMMENT 'Release ID from GitHub',
  `milestone_id` int(11) unsigned NOT NULL COMMENT 'Optional milestone to associate with this release',
  `name` varchar(50) NOT NULL COMMENT 'Release Name',
  `tag_name` varchar(50) NOT NULL COMMENT 'Name of the Git tag for this release',
  `created_at` datetime DEFAULT NULL COMMENT 'Date the release was created',
  `notes` mediumtext NOT NULL COMMENT 'The HTML formatted release notes',
  `notes_raw` mediumtext NOT NULL COMMENT 'The raw release notes (markdown)',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `release_id` (`release_id`),
  CONSTRAINT `#__releases_fk_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `#__tracker_milestones` (`milestone_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
