CREATE TABLE IF NOT EXISTS `ConfigService_xml` (
  `id_xml` int(11) NOT NULL AUTO_INCREMENT,
  `xmlLp` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `xml` mediumtext COLLATE utf8_unicode_ci,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_xml`),
  UNIQUE KEY `xmlLp` (`xmlLp`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `ConfigService_parameters` (
  `id_params` int(11) NOT NULL AUTO_INCREMENT,
  `lp` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `xmlLp` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_params`),
  UNIQUE KEY `lp` (`lp`),
  KEY `xmlLp` (`xmlLp`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE  `SessionAdminService` CHANGE  `value`  `value` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL