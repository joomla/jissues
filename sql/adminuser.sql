INSERT INTO `#__users` (`id`, `name`, `username`, `email`, `password`, `block`, `sendEmail`, `registerDate`, `lastvisitDate`, `activation`, `params`, `lastResetTime`, `resetCount`) VALUES
(733, 'Super User', 'admin', 'test@example.com', '8a5bf086d370c38612f4a30158376b4f:I5vNFHMuxhFRXYJN9H2qcsXJc1gKZIls', 0, 1, '2012-09-22 01:34:29', '2012-10-08 23:57:37', '0', '{}', '0000-00-00 00:00:00', 0);


INSERT INTO `#__user_usergroup_map` (`user_id`, `group_id`) VALUES
(733, 8);
