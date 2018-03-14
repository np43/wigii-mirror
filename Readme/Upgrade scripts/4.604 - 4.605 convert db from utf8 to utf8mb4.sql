-- Please rename the database name before execution
ALTER DATABASE `MyDatabaseName` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Convert each table to utf8mb4
ALTER TABLE `Addresses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Attributs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Blobs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Booleans` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ConfigService_parameters` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ConfigService_parameters` 
	CHANGE `xmlLp` `xmlLp` VARCHAR(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `name` `name` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `value` `value` VARCHAR(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `ConfigService_xml` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ConfigService_xml` 
	CHANGE `xml` `xml` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `Dates` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Elements` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ElementStatistic` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Elements_Elements` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Elements_Groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Emails` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `EmailService` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `EmailServiceAttachementsToDelete` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Files` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `FileStatistic` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Floats` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `GlobalStatistic` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Groups_Activities` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Groups_Groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Links` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `MultipleAttributs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Numerics` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `SessionAdminService` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `SessionAdminService` 
	CHANGE `value` `value` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `Strings` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Texts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `TimeRanges` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Times` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Urls` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Users` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Users` 
	CHANGE `wigiiNamespace` `wigiiNamespace` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `password` `password` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `passwordHistory` `passwordHistory` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `email` `email` VARCHAR(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `emailProofKey` `emailProofKey` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `emailProof` `emailProof` VARCHAR(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `moduleAccess` `moduleAccess` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `description` `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `info_lastSessionContext` `info_lastSessionContext` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `authenticationMethod` `authenticationMethod` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `authenticationServer` `authenticationServer` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `groupCreator` `groupCreator` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `rootGroupCreator` `rootGroupCreator` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `readAllGroupsInWigiiNamespace` `readAllGroupsInWigiiNamespace` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `sys_lockId` `sys_lockId` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `sys_creationUsername` `sys_creationUsername` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, 
	CHANGE `sys_username` `sys_username` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `Users_Groups_Rights` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Users_Users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `Varchars` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- rebuild the indexes (REPAIR is not available for InnoDB)
ALTER TABLE `Addresses` ENGINE = InnoDB;
ALTER TABLE `Attributs` ENGINE = InnoDB;
ALTER TABLE `Blobs` ENGINE = InnoDB;
ALTER TABLE `Booleans` ENGINE = InnoDB;
ALTER TABLE `ConfigService_parameters` ENGINE = InnoDB;
ALTER TABLE `ConfigService_xml` ENGINE = InnoDB;
ALTER TABLE `Dates` ENGINE = InnoDB;
ALTER TABLE `Elements` ENGINE = InnoDB;
ALTER TABLE `ElementStatistic` ENGINE = InnoDB;
ALTER TABLE `Elements_Elements` ENGINE = InnoDB;
ALTER TABLE `Elements_Groups` ENGINE = InnoDB;
ALTER TABLE `Emails` ENGINE = InnoDB;
ALTER TABLE `EmailService` ENGINE = InnoDB;
ALTER TABLE `EmailServiceAttachementsToDelete` ENGINE = InnoDB;
ALTER TABLE `Files` ENGINE = InnoDB;
ALTER TABLE `FileStatistic` ENGINE = InnoDB;
ALTER TABLE `Floats` ENGINE = InnoDB;
ALTER TABLE `GlobalStatistic` ENGINE = InnoDB;
ALTER TABLE `Groups` ENGINE = InnoDB;
ALTER TABLE `Groups_Activities` ENGINE = InnoDB;
ALTER TABLE `Groups_Groups` ENGINE = InnoDB;
ALTER TABLE `Links` ENGINE = InnoDB;
ALTER TABLE `MultipleAttributs` ENGINE = InnoDB;
ALTER TABLE `Numerics` ENGINE = InnoDB;
ALTER TABLE `SessionAdminService` ENGINE = InnoDB;
ALTER TABLE `Strings` ENGINE = InnoDB;
ALTER TABLE `Texts` ENGINE = InnoDB;
ALTER TABLE `TimeRanges` ENGINE = InnoDB;
ALTER TABLE `Times` ENGINE = InnoDB;
ALTER TABLE `Urls` ENGINE = InnoDB;
ALTER TABLE `Users` ENGINE = InnoDB;
ALTER TABLE `Users_Groups_Rights` ENGINE = InnoDB;
ALTER TABLE `Users_Users` ENGINE = InnoDB;
ALTER TABLE `Varchars` ENGINE = InnoDB;
