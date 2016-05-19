USE `gyproc_2`;

CREATE TABLE IF NOT EXISTS `deviceinfo` (
  `id_device` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(100) NOT NULL,
  `os` varchar(10) NOT NULL,
  `push_token` varchar(200) NOT NULL,
  PRIMARY KEY (`id_device`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `userinfo` (
  `iduser` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `name` varchar(30) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `role` varchar(30) NOT NULL,
  `id_device` int(11) NOT NULL,
  PRIMARY KEY (`iduser`),
  KEY `fk_id_device` (`id_device`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `versioninfo` (
  `source` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`source`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `versioninfo`(`source`) VALUES (1);


-- DROP TRIGGER IF EXISTS `update_version`;
-- DELIMITER $$
-- CREATE TRIGGER `update_version` AFTER UPDATE ON `wp_postmeta` FOR EACH ROW UPDATE versioninfo SET versioninfo.source = (versioninfo.source + 1)
-- $$
-- DELIMITER ;
