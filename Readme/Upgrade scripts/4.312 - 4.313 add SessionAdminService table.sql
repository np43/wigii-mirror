CREATE TABLE IF NOT EXISTS `SessionAdminService` (
  `id_data` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(224) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_data`),
  UNIQUE KEY `key` (`key`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;