# Use a VARCHAR field for the migration versions instead of integer
ALTER TABLE `#__migrations` MODIFY `version` varchar(25) NOT NULL COMMENT 'Applied migration versions';
