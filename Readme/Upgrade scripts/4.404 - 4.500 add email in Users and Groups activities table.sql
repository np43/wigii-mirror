ALTER TABLE `Users` 
ADD `email` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL AFTER `passwordDate`,
ADD `emailProofKey` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL AFTER `email`,
ADD `emailProof` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL AFTER `emailProofKey`,
ADD `emailProofStatus` tinyint(1) DEFAULT NULL AFTER `emailProof`,
ADD KEY `email` (`email`);

ALTER TABLE `Groups` 
ADD `activities` text COLLATE utf8_unicode_ci AFTER `xmlPublish`;

CREATE TABLE IF NOT EXISTS `Groups_Activities` (
  `id_group_activity` int(11) NOT NULL AUTO_INCREMENT,
  `id_group` int(11) DEFAULT NULL,
  `activityname` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8_unicode_ci,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_group_activity`),
  UNIQUE KEY `id_group` (`id_group`,`activityname`,`field`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;