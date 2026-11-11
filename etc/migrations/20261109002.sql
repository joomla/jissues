ALTER TABLE `#__tracker_projects` ADD COLUMN `gh_editbot_token` varchar(255) DEFAULT '' COMMENT 'GitHub editbot personal access token' AFTER `gh_editbot_pass`;
