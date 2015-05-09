ALTER TABLE `Elements` 
ADD `linkName` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL AFTER `id_element_parent`,
DROP INDEX `id_element_parent`, ADD INDEX `id_element_parent` (`id_element_parent`, `linkName`); 
