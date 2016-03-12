-- phpMyAdmin SQL Dump
-- version 3.3.2deb1ubuntu1
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Mar 10 Décembre 2013 à 10:30
-- Version du serveur: 5.1.68
-- Version de PHP: 5.3.2-1ubuntu4.19

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `wigii_example`
--

-- --------------------------------------------------------

--
-- Structure de la table `Addresses`
--

CREATE TABLE IF NOT EXISTS `Addresses` (
  `id_Addresse` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip_code` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Addresse`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `street` (`street`),
  KEY `zip_code` (`zip_code`),
  KEY `city` (`city`),
  KEY `state` (`state`),
  KEY `country` (`country`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Attributs`
--

CREATE TABLE IF NOT EXISTS `Attributs` (
  `id_Attribut` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Attribut`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `value` (`value`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Blobs`
--

CREATE TABLE IF NOT EXISTS `Blobs` (
  `id_Blob` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8_unicode_ci,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Blob`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Booleans`
--

CREATE TABLE IF NOT EXISTS `Booleans` (
  `id_Boolean` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` tinyint(1) DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Boolean`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `value` (`value`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure des tables du ConfigService
--

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

-- --------------------------------------------------------

--
-- Structure de la table `Dates`
--

CREATE TABLE IF NOT EXISTS `Dates` (
  `id_Date` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` datetime DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Date`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `value` (`value`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Elements`
--

CREATE TABLE IF NOT EXISTS `Elements` (
  `id_element` int(11) NOT NULL AUTO_INCREMENT,
  `modulename` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_element_parent` int(11) DEFAULT NULL,
  `linkName` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `peerId` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `version` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tags` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_lockId` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_lockMicroTime` bigint(20) unsigned DEFAULT NULL,
  `state_locked` tinyint(1) DEFAULT NULL,
  `state_important1` tinyint(1) DEFAULT NULL,
  `state_important2` tinyint(1) DEFAULT NULL,
  `state_hidden` tinyint(1) DEFAULT NULL,
  `state_archived` tinyint(1) DEFAULT NULL,
  `state_deprecated` tinyint(1) DEFAULT NULL,
  `state_finalized` tinyint(1) DEFAULT NULL,
  `state_approved` tinyint(1) DEFAULT NULL,
  `state_dismissed` tinyint(1) DEFAULT NULL,
  `state_blocked` tinyint(1) DEFAULT NULL,
  `state_lockedInfo` text COLLATE utf8_unicode_ci,
  `state_hiddenInfo` text COLLATE utf8_unicode_ci,
  `state_archivedInfo` text COLLATE utf8_unicode_ci,
  `state_deprecatedInfo` text COLLATE utf8_unicode_ci,
  `state_important1Info` text COLLATE utf8_unicode_ci,
  `state_important2Info` text COLLATE utf8_unicode_ci,
  `state_finalizedInfo` text COLLATE utf8_unicode_ci,
  `state_approvedInfo` text COLLATE utf8_unicode_ci,
  `state_dismissedInfo` text COLLATE utf8_unicode_ci,
  `state_blockedInfo` text COLLATE utf8_unicode_ci,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_element`),
  UNIQUE KEY `id_element` (`id_element`,`modulename`),
  KEY `created_by` (`sys_creationUser`),
  KEY `last_modif_user` (`sys_user`),
  KEY `tags` (`tags`),
  KEY `sys_lockId` (`sys_lockId`),
  KEY `state_locked` (`state_locked`),
  KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_date` (`sys_date`),
  KEY `state_important1` (`state_important1`),
  KEY `state_important2` (`state_important2`),
  KEY `state_hidden` (`state_hidden`),
  KEY `state_archived` (`state_archived`),
  KEY `state_deprecated` (`state_deprecated`),
  KEY `state_finalized` (`state_finalized`),
  KEY `state_approved` (`state_approved`),
  KEY `state_dismissed` (`state_dismissed`),
  KEY `state_blocked` (`state_blocked`),
  KEY `version` (`version`),
  KEY `id_element_parent` (`id_element_parent`),
  KEY `peerId` (`peerId`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_user` (`sys_user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ElementStatistic`
--

CREATE TABLE IF NOT EXISTS `ElementStatistic` (
  `id_statistic` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `eventName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entityName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `modulename` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `realUserId` int(11) DEFAULT NULL,
  `realUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `elementId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_statistic`),
  KEY `timestamp` (`timestamp`),
  KEY `eventName` (`eventName`),
  KEY `entityName` (`entityName`),
  KEY `modulename` (`modulename`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `userId` (`userId`),
  KEY `realUserId` (`realUserId`),
  KEY `elementId` (`elementId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Elements_Elements`
--

CREATE TABLE IF NOT EXISTS `Elements_Elements` (
  `id_elements_elements` int(11) NOT NULL AUTO_INCREMENT,
  `id_element_owner` int(11) DEFAULT NULL,
  `id_element` int(11) DEFAULT NULL,
  `linkName` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `linkType` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_element_src` int(11) DEFAULT NULL,
  `sys_creationDate` int(10) DEFAULT NULL,
  `sys_creationUser` int(10) DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_elements_elements`),
  UNIQUE KEY `link` (`id_element_owner`,`id_element`,`linkName`),
  KEY `linkName` (`linkName`),
  KEY `linkType` (`linkType`),
  KEY `id_element_src` (`id_element_src`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Elements_Groups`
--

CREATE TABLE IF NOT EXISTS `Elements_Groups` (
  `id_element_group` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `id_group` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_element_group`),
  UNIQUE KEY `id_element` (`id_element`,`id_group`),
  KEY `id_group` (`id_group`,`id_element`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Emails`
--

CREATE TABLE IF NOT EXISTS `Emails` (
  `id_Email` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `proofKey` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `proof` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `proofStatus` tinyint(1) DEFAULT NULL,
  `externalCode` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `externalAccessLevel` tinyint(4) DEFAULT NULL,
  `externalAccessEndDate` int(10) unsigned DEFAULT NULL,
  `externalConfigGroup` int(11) DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Email`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `proofKey` (`proofKey`),
  KEY `externalCode` (`externalCode`),
  KEY `externalAccessLevel` (`externalAccessLevel`),
  KEY `externalAccessEndDate` (`externalAccessEndDate`),
  KEY `externalConfigGroup` (`externalConfigGroup`),
  KEY `proofStatus` (`proofStatus`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `EmailService`
--

CREATE TABLE IF NOT EXISTS `EmailService` (
  `id_email` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nbFailure` int(11) DEFAULT NULL,
  `creationDate` int(10) unsigned DEFAULT NULL,
  `lastUpdate` int(10) unsigned DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `realUserId` int(11) DEFAULT NULL,
  `realUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `charset` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attachement` text COLLATE utf8_unicode_ci,
  `to` longtext COLLATE utf8_unicode_ci,
  `cc` longtext COLLATE utf8_unicode_ci,
  `bcc` longtext COLLATE utf8_unicode_ci,
  `replyTo` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `from` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subject` text COLLATE utf8_unicode_ci,
  `bodyHtml` longtext COLLATE utf8_unicode_ci,
  `bodyText` longtext COLLATE utf8_unicode_ci,
  `sys_lockId` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_lockMicroTime` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_email`),
  KEY `sys_lockId` (`sys_lockId`),
  KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  KEY `status` (`status`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `userId` (`userId`),
  KEY `realUserId` (`realUserId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `EmailServiceAttachementsToDelete`
--

CREATE TABLE IF NOT EXISTS `EmailServiceAttachementsToDelete` (
  `id_attachementToDelete` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nb` int(11) DEFAULT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_attachementToDelete`),
  UNIQUE KEY `path` (`path`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Files`
--

CREATE TABLE IF NOT EXISTS `Files` (
  `id_File` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` bigint(20) DEFAULT NULL,
  `mime` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `version` INT( 4 ) unsigned NULL DEFAULT NULL,
  `content` longblob,
  `thumbnail` blob,
  `textContent` longtext COLLATE utf8_unicode_ci,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_File`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `name` (`name`),
  KEY `date` (`date`),
  KEY `type` (`type`),
  KEY `mime` (`mime`),
  KEY `size` (`size`),
  KEY `user` (`user`),
  KEY `username` (`username`),
  KEY `path` (`path`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `FileStatistic`
--

CREATE TABLE IF NOT EXISTS `FileStatistic` (
  `id_statistic` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `eventName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entityName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `modulename` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `realUserId` int(11) DEFAULT NULL,
  `realUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `elementId` int(11) DEFAULT NULL,
  `field` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_statistic`),
  KEY `timestamp` (`timestamp`),
  KEY `eventName` (`eventName`),
  KEY `entityName` (`entityName`),
  KEY `modulename` (`modulename`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `userId` (`userId`),
  KEY `realUserId` (`realUserId`),
  KEY `elementId` (`elementId`),
  KEY `field` (`field`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Floats`
--

CREATE TABLE IF NOT EXISTS `Floats` (
  `id_Float` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` double DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Float`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `value` (`value`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `GlobalStatistic`
--

CREATE TABLE IF NOT EXISTS `GlobalStatistic` (
  `id_statistic` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `eventName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entityName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entityId` int(11) DEFAULT NULL,
  `modulename` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `realUserId` int(11) DEFAULT NULL,
  `realUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_statistic`),
  KEY `timestamp` (`timestamp`),
  KEY `eventName` (`eventName`),
  KEY `entityName` (`entityName`),
  KEY `entityId` (`entityId`),
  KEY `modulename` (`modulename`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `userId` (`userId`),
  KEY `realUserId` (`realUserId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Groups`
--

CREATE TABLE IF NOT EXISTS `Groups` (
  `id_group` int(11) NOT NULL AUTO_INCREMENT,
  `groupname` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `modulename` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `portal` longtext COLLATE utf8_unicode_ci,
  `htmlContent` longtext COLLATE utf8_unicode_ci,
  `id_group_parent` int(11) DEFAULT NULL,
  `subscription` longtext COLLATE utf8_unicode_ci,
  `emailNotification` longtext COLLATE utf8_unicode_ci,
  `xmlPublish` longtext COLLATE utf8_unicode_ci,
  `sys_date` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_lockId` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_lockMicroTime` bigint(20) unsigned DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_group`),
  KEY `groupname` (`groupname`,`modulename`,`wigiiNamespace`),
  KEY `id_group_parent` (`id_group_parent`),
  KEY `sys_lockId` (`sys_lockId`),
  KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Groups_Groups`
--

CREATE TABLE IF NOT EXISTS `Groups_Groups` (
  `id_relation_group` int(11) NOT NULL AUTO_INCREMENT,
  `id_group_owner` int(11) DEFAULT NULL,
  `id_group` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_relation_group`),
  UNIQUE KEY `id_group_owner` (`id_group_owner`,`id_group`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Links`
--

CREATE TABLE IF NOT EXISTS `Links` (
  `id_Link` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` int(11) DEFAULT NULL,
  `commonId` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Link`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `commonId` (`commonId`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `MultipleAttributs`
--

CREATE TABLE IF NOT EXISTS `MultipleAttributs` (
  `id_MultipleAttribut` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_MultipleAttribut`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Numerics`
--

CREATE TABLE IF NOT EXISTS `Numerics` (
  `id_Numeric` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` decimal(32,2) DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Numeric`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `value` (`value`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `SessionAdminService`
--

CREATE TABLE IF NOT EXISTS `SessionAdminService` (
  `id_data` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(224) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8_unicode_ci,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_data`),
  UNIQUE KEY `key` (`key`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Strings`
--

CREATE TABLE IF NOT EXISTS `Strings` (
  `id_String` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_String`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `value` (`value`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Texts`
--

CREATE TABLE IF NOT EXISTS `Texts` (
  `id_Text` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l01` text COLLATE utf8_unicode_ci,
  `value_l02` text COLLATE utf8_unicode_ci,
  `value_l03` text COLLATE utf8_unicode_ci,
  `value_l04` text COLLATE utf8_unicode_ci,
  `value_l05` text COLLATE utf8_unicode_ci,
  `value_l06` text COLLATE utf8_unicode_ci,
  `value_l07` text COLLATE utf8_unicode_ci,
  `value_l08` text COLLATE utf8_unicode_ci,
  `value_l09` text COLLATE utf8_unicode_ci,
  `value_l10` text COLLATE utf8_unicode_ci,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Text`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `TimeRanges`
--

CREATE TABLE IF NOT EXISTS `TimeRanges` (
  `id_TimeRange` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isAllDay` tinyint(1) DEFAULT NULL,
  `begTime` time DEFAULT NULL,
  `endTime` time DEFAULT NULL,
  `begDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_TimeRange`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `isAllDay` (`isAllDay`),
  KEY `begTime` (`begTime`),
  KEY `endTime` (`endTime`),
  KEY `begDate` (`begDate`),
  KEY `endDate` (`endDate`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Times`
--

CREATE TABLE IF NOT EXISTS `Times` (
  `id_Time` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` time DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Time`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `value` (`value`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Urls`
--

CREATE TABLE IF NOT EXISTS `Urls` (
  `id_url` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `target` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_url`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `name` (`name`),
  KEY `url` (`url`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Users`
--

CREATE TABLE IF NOT EXISTS `Users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `passwordHistory` text COLLATE utf8_unicode_ci,
  `passwordLength` int(11) DEFAULT NULL,
  `passwordLife` int(11) DEFAULT NULL,
  `passwordDate` int(11) DEFAULT NULL,
  `moduleAccess` text COLLATE utf8_unicode_ci,
  `description` text COLLATE utf8_unicode_ci,
  `info_lastLogin` int(10) unsigned DEFAULT NULL,
  `info_nbLogin` int(10) unsigned DEFAULT NULL,
  `info_lastFailedLogin` int(10) unsigned DEFAULT NULL,
  `info_nbFailedLogin` int(10) unsigned DEFAULT NULL,
  `info_lastLogout` int(10) unsigned DEFAULT NULL,
  `info_lastSessionContext` longtext COLLATE utf8_unicode_ci,
  `info_resetSessionContext` tinyint(1) DEFAULT NULL,
  `authenticationMethod` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `authenticationServer` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userCreator` tinyint(1) DEFAULT NULL,
  `groupCreator` text COLLATE utf8_unicode_ci,
  `adminCreator` tinyint(1) DEFAULT NULL,
  `readAllUsersInWigiiNamespace` tinyint(1) DEFAULT NULL,
  `rootGroupCreator` text COLLATE utf8_unicode_ci,
  `readAllGroupsInWigiiNamespace` text COLLATE utf8_unicode_ci,
  `wigiiNamespaceCreator` tinyint(1) DEFAULT NULL,
  `moduleEditor` tinyint(1) DEFAULT NULL,
  `canModifyOwnPassword` tinyint(1) DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_lockId` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_lockMicroTime` bigint(20) unsigned DEFAULT NULL,
  `isRole` tinyint(1) DEFAULT NULL,
  `isCalculatedRole` tinyint(1) DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `username_password` (`username`,`password`),
  KEY `isRole` (`isRole`),
  KEY `isCalculatedRole` (`isCalculatedRole`),
  KEY `sys_lockId` (`sys_lockId`),
  KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  KEY `sys_date` (`sys_date`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Users_Groups_Rights`
--

CREATE TABLE IF NOT EXISTS `Users_Groups_Rights` (
  `id_user_group_right` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `id_group` int(11) DEFAULT NULL,
  `canModify` tinyint(1) DEFAULT NULL,
  `canWriteElement` tinyint(1) DEFAULT NULL,
  `canShareElement` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_user_group_right`),
  UNIQUE KEY `id_user` (`id_user`,`id_group`),
  KEY `canModify` (`canModify`),
  KEY `canWriteElement` (`canWriteElement`),
  KEY `canShareElement` (`canShareElement`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Users_Users`
--

CREATE TABLE IF NOT EXISTS `Users_Users` (
  `id_relation_user` int(11) NOT NULL AUTO_INCREMENT,
  `id_user_owner` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `isOwner` tinyint(1) DEFAULT NULL,
  `hasRole` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_relation_user`),
  UNIQUE KEY `isOwner` (`id_user_owner`,`id_user`,`isOwner`),
  UNIQUE KEY `hasRole` (`id_user_owner`,`id_user`,`hasRole`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Varchars`
--

CREATE TABLE IF NOT EXISTS `Varchars` (
  `id_Varchar` int(11) NOT NULL AUTO_INCREMENT,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l01` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l02` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l03` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l04` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l05` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l06` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l07` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l08` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l09` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_l10` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_Varchar`),
  UNIQUE KEY `id_element` (`id_element`,`field`),
  KEY `value_l01` (`value_l01`),
  KEY `value_l02` (`value_l02`),
  KEY `value_l03` (`value_l03`),
  KEY `value_l04` (`value_l04`),
  KEY `value_l05` (`value_l05`),
  KEY `value_l06` (`value_l06`),
  KEY `value_l07` (`value_l07`),
  KEY `value_l08` (`value_l08`),
  KEY `value_l09` (`value_l09`),
  KEY `value_l10` (`value_l10`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
