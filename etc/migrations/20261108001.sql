ALTER TABLE `#__users` ADD COLUMN `github_id` bigint(20) unsigned DEFAULT NULL COMMENT 'The numeric GitHub user id' AFTER `username`, ADD KEY `github_id` (`github_id`);
