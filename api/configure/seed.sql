DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `token` varchar(50) NOT NULL DEFAULT '00000000-00000-0000-0000-000000000000',
  `lease` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `role` varchar(50) DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',  		  
  `secret` varchar(50) NOT NULL DEFAULT '206b2dbe-ecc9-490b-b81b-83767288bc5e',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
);

-- Insert 3 default users

INSERT INTO `users` (`id`, `email`, `username`, `password`, `token`, `lease`, `role`, `is_active`, `secret`) VALUES
(1,	'superadmin@example.com',	'superadmin',	'17c4520f6cfd1ab53d8745e84681eb49',	'1',	'0000-00-00 00:00:00',	'superadmin', 1, '206b2dbe-ecc9-490b-b81b-83767288bc5e');

INSERT INTO `users` (`id`, `email`, `username`, `password`, `token`, `lease`, `role`, `is_active`, `secret`) VALUES
(2,	'admin@example.com',	'admin',	'21232f297a57a5a743894a0e4a801fc3',	'1',	'0000-00-00 00:00:00',	'admin', 1, '206b2dbe-ecc9-490b-b81b-83767288bc5e');

INSERT INTO `users` (`id`, `email`, `username`, `password`, `token`, `lease`, `role`, `is_active`, `secret`) VALUES
(3,	'user@example.com',	'user',	'ee11cbb19052e40b07aac0ca060c23ee',	'1',	'0000-00-00 00:00:00',	'user', 1, '206b2dbe-ecc9-490b-b81b-83767288bc5e');


-- SQL Script for creating organizations table that can be used to associate secret key with each unique organization
DROP TABLE IF EXISTS `organizations`;
CREATE TABLE `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `license` varchar(15) NOT NULL DEFAULT 'basic',
  `validity` datetime NOT NULL,  
  `is_active` tinyint(1) NOT NULL DEFAULT '0',  
  `org_secret` varchar(50) NOT NULL,
  `secret` varchar(50) NOT NULL DEFAULT '206b2dbe-ecc9-490b-b81b-83767288bc5e',
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_secret` (`org_secret`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- Insert a default organization

INSERT INTO `organizations` (`id`, `name`, `email`, `license`, `validity`, `is_active`, `org_secret`, `secret`) VALUES
(1,	'Default Organization',	'superadmin@example.com', 'super',	'0000-00-00 00:00:00', 1, '206b2dbe-ecc9-490b-b81b-83767288bc5e',	'206b2dbe-ecc9-490b-b81b-83767288bc5e');


-- SQL Script for creating files table

DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`)
);