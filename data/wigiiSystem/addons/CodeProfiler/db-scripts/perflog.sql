CREATE TABLE IF NOT EXISTS `perflog` (
  `id_perflog` int(11) NOT NULL AUTO_INCREMENT,
  `realusername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `executionId` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` bigint(13) unsigned DEFAULT NULL,
  `classname` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `operation` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `action` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_perflog`)
) ENGINE=Innodb  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;