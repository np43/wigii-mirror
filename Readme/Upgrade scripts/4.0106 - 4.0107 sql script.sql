-- update Users, Groups, Elements to have all same system fields

-- + alter Elements table to manage subitem
ALTER TABLE `Elements` 
ADD `id_element_parent` INT( 11 ) NULL AFTER `modulename` ,
ADD `peerId` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `id_element_parent` ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD INDEX `id_element_parent` ( `id_element_parent`) , 
ADD INDEX `peerId` ( `peerId`) , 
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_user` (`sys_user`) ; 

ALTER TABLE `Groups` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) ;

ALTER TABLE `Users` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) ;

-- update any sys_user and sys_creationUser from calculated roleId to realUserId
UPDATE Elements SET
sys_user = (SELECT uu.id_user_owner FROM Users_Users uu WHERE uu.id_user = sys_user)
WHERE sys_user IN (SELECT u1.id_user FROM Users u1 WHERE u1.isCalculatedRole = TRUE);

UPDATE Elements SET
sys_creationUser = (SELECT uu.id_user_owner FROM Users_Users uu WHERE uu.id_user = sys_creationUser)
WHERE sys_creationUser IN (SELECT u1.id_user FROM Users u1 WHERE u1.isCalculatedRole = TRUE);

-- update username and creationUsername based on id and user
UPDATE Elements el
LEFT JOIN Users u1 ON u1.id_user = el.sys_user
LEFT JOIN Users u2 ON u2.id_user = el.sys_creationUser
SET
el.sys_username = u1.username,
el.sys_creationUsername = u2.username
;

UPDATE Users u
LEFT JOIN Users u1 ON u1.id_user = u.sys_user
LEFT JOIN Users u2 ON u2.id_user = u.sys_creationUser
SET
u.sys_username = u1.username,
u.sys_creationUsername = u2.username
;

UPDATE Groups g
LEFT JOIN Users u1 ON u1.id_user = g.sys_user
LEFT JOIN Users u2 ON u2.id_user = g.sys_creationUser
SET
g.sys_username = u1.username,
g.sys_creationUsername = u2.username
;



-- create the two new tables to manage Links: Links and Elements_Elements

DROP TABLE IF EXISTS `Links`;
CREATE TABLE `Links` (
	`id_Link` int( 11 ) NOT NULL AUTO_INCREMENT ,
	`id_element` int( 11 ) DEFAULT NULL ,
	`field` varchar( 32 ) COLLATE utf8_unicode_ci DEFAULT NULL ,
	`value` int(11) DEFAULT NULL ,
	`commonId` varchar( 64 ) COLLATE utf8_unicode_ci DEFAULT NULL ,
	PRIMARY KEY ( `id_Link` ) ,
	UNIQUE KEY `id_element` ( `id_element` , `field` ),
	INDEX  `commonId` ( `commonId` )
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

DROP TABLE IF EXISTS `Elements_Elements`;
CREATE TABLE `Elements_Elements` (
	`id_elements_elements` int( 11 ) NOT NULL AUTO_INCREMENT ,
	`id_element_owner` int( 11 ) DEFAULT NULL ,
	`id_element` int( 11 ) DEFAULT NULL ,
	`linkName` varchar( 32 ) COLLATE utf8_unicode_ci DEFAULT NULL ,
	`linkType` varchar( 32 ) COLLATE utf8_unicode_ci DEFAULT NULL ,
	`id_element_src` int( 11 ) DEFAULT NULL ,
	`sys_creationDate` int(10) DEFAULT NULL ,
	`sys_creationUser` int(10) DEFAULT NULL ,
	`sys_creationUsername` varchar( 64 ) COLLATE utf8_unicode_ci DEFAULT NULL ,
	PRIMARY KEY ( `id_elements_elements` ) ,
	UNIQUE KEY `link` ( `id_element_owner`, `id_element` , `linkName` ),
	INDEX  ( `linkName`), INDEX (`linkType`), INDEX(`id_element_src`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

-- Alter DataType tables to add system fields for creation and update
ALTER TABLE `Addresses` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Attributs` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Blobs` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Booleans` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Dates` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Emails` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Files` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Floats` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Links` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `MultipleAttributs` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Numerics` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Strings` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Texts` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `TimeRanges` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Times` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Urls` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

ALTER TABLE `Varchars` 
ADD `sys_creationUser` INT( 10 ) UNSIGNED NULL ,
ADD `sys_creationUsername` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_creationDate` INT( 10 ) UNSIGNED NULL ,
ADD `sys_user` INT( 10 ) UNSIGNED NULL ,
ADD `sys_username` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
ADD `sys_date` INT( 10 ) UNSIGNED NULL ,
ADD INDEX `sys_creationUser` ( `sys_creationUser`) , 
ADD INDEX `sys_creationDate` (`sys_creationDate`) , 
ADD INDEX `sys_user` (`sys_user`) , 
ADD INDEX `sys_date` (`sys_date`) ;

UPDATE `Addresses` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Attributs` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Blobs` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Booleans` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Dates` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Emails` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Files` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Floats` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Links` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `MultipleAttributs` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Numerics` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Strings` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Texts` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `TimeRanges` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Times` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Urls` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;

UPDATE `Varchars` dt
LEFT JOIN `Elements` el ON el.id_element = dt.id_element
SET 
dt.sys_user = el.sys_user,
dt.sys_username = el.sys_username,
dt.sys_date = el.sys_date,
dt.sys_creationUser = el.sys_creationUser,
dt.sys_creationUsername = el.sys_creationUsername,
dt.sys_creationDate = el.sys_creationDate;