-- phpMyAdmin SQL Dump
-- version 4.6.6
-- https://www.phpmyadmin.net/
--
-- Server version: 5.6.33-log
-- PHP Version: 7.0.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `Addresses`
--

DROP TABLE IF EXISTS `Addresses`;
CREATE TABLE `Addresses` (
  `id_Addresse` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Attributs`
--

DROP TABLE IF EXISTS `Attributs`;
CREATE TABLE `Attributs` (
  `id_Attribut` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Blobs`
--

DROP TABLE IF EXISTS `Blobs`;
CREATE TABLE `Blobs` (
  `id_Blob` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Booleans`
--

DROP TABLE IF EXISTS `Booleans`;
CREATE TABLE `Booleans` (
  `id_Boolean` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` tinyint(1) DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ConfigService_parameters`
--

DROP TABLE IF EXISTS `ConfigService_parameters`;
CREATE TABLE `ConfigService_parameters` (
  `id_params` int(11) NOT NULL,
  `lp` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xmlLp` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ConfigService_xml`
--

DROP TABLE IF EXISTS `ConfigService_xml`;
CREATE TABLE `ConfigService_xml` (
  `id_xml` int(11) NOT NULL,
  `xmlLp` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xml` mediumtext COLLATE utf8mb4_unicode_ci,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Dates`
--

DROP TABLE IF EXISTS `Dates`;
CREATE TABLE `Dates` (
  `id_Date` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` datetime DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Elements`
--

DROP TABLE IF EXISTS `Elements`;
CREATE TABLE `Elements` (
  `id_element` int(11) NOT NULL,
  `modulename` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_element_parent` int(11) DEFAULT NULL,
  `linkName` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `peerId` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_lockId` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_lockMicroTime` bigint(20) UNSIGNED DEFAULT NULL,
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
  `state_lockedInfo` text COLLATE utf8mb4_unicode_ci,
  `state_hiddenInfo` text COLLATE utf8mb4_unicode_ci,
  `state_archivedInfo` text COLLATE utf8mb4_unicode_ci,
  `state_deprecatedInfo` text COLLATE utf8mb4_unicode_ci,
  `state_important1Info` text COLLATE utf8mb4_unicode_ci,
  `state_important2Info` text COLLATE utf8mb4_unicode_ci,
  `state_finalizedInfo` text COLLATE utf8mb4_unicode_ci,
  `state_approvedInfo` text COLLATE utf8mb4_unicode_ci,
  `state_dismissedInfo` text COLLATE utf8mb4_unicode_ci,
  `state_blockedInfo` text COLLATE utf8mb4_unicode_ci,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ElementStatistic`
--

DROP TABLE IF EXISTS `ElementStatistic`;
CREATE TABLE `ElementStatistic` (
  `id_statistic` int(11) NOT NULL,
  `timestamp` int(10) UNSIGNED DEFAULT NULL,
  `eventName` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entityName` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modulename` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `realUserId` int(11) DEFAULT NULL,
  `realUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `elementId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Elements_Elements`
--

DROP TABLE IF EXISTS `Elements_Elements`;
CREATE TABLE `Elements_Elements` (
  `id_elements_elements` int(11) NOT NULL,
  `id_element_owner` int(11) DEFAULT NULL,
  `id_element` int(11) DEFAULT NULL,
  `linkName` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkType` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_element_src` int(11) DEFAULT NULL,
  `sys_creationDate` int(10) DEFAULT NULL,
  `sys_creationUser` int(10) DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Elements_Groups`
--

DROP TABLE IF EXISTS `Elements_Groups`;
CREATE TABLE `Elements_Groups` (
  `id_element_group` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `id_group` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Emails`
--

DROP TABLE IF EXISTS `Emails`;
CREATE TABLE `Emails` (
  `id_Email` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `proofKey` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proofStatus` tinyint(1) DEFAULT NULL,
  `externalCode` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `externalAccessLevel` tinyint(4) DEFAULT NULL,
  `externalAccessEndDate` int(10) UNSIGNED DEFAULT NULL,
  `externalConfigGroup` int(11) DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `EmailService`
--

DROP TABLE IF EXISTS `EmailService`;
CREATE TABLE `EmailService` (
  `id_email` int(11) NOT NULL,
  `status` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nbFailure` int(11) DEFAULT NULL,
  `creationDate` int(10) UNSIGNED DEFAULT NULL,
  `lastUpdate` int(10) UNSIGNED DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `realUserId` int(11) DEFAULT NULL,
  `realUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `charset` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachement` text COLLATE utf8mb4_unicode_ci,
  `to` longtext COLLATE utf8mb4_unicode_ci,
  `cc` longtext COLLATE utf8mb4_unicode_ci,
  `bcc` longtext COLLATE utf8mb4_unicode_ci,
  `replyTo` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` text COLLATE utf8mb4_unicode_ci,
  `bodyHtml` longtext COLLATE utf8mb4_unicode_ci,
  `bodyText` longtext COLLATE utf8mb4_unicode_ci,
  `sys_lockId` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_lockMicroTime` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `EmailServiceAttachementsToDelete`
--

DROP TABLE IF EXISTS `EmailServiceAttachementsToDelete`;
CREATE TABLE `EmailServiceAttachementsToDelete` (
  `id_attachementToDelete` int(11) NOT NULL,
  `path` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nb` int(11) DEFAULT NULL,
  `timestamp` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Files`
--

DROP TABLE IF EXISTS `Files`;
CREATE TABLE `Files` (
  `id_File` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint(20) DEFAULT NULL,
  `mime` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int(4) UNSIGNED DEFAULT NULL,
  `content` longblob,
  `thumbnail` blob,
  `textContent` longtext COLLATE utf8mb4_unicode_ci,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `FileStatistic`
--

DROP TABLE IF EXISTS `FileStatistic`;
CREATE TABLE `FileStatistic` (
  `id_statistic` int(11) NOT NULL,
  `timestamp` int(10) UNSIGNED DEFAULT NULL,
  `eventName` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entityName` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modulename` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `realUserId` int(11) DEFAULT NULL,
  `realUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `elementId` int(11) DEFAULT NULL,
  `field` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Floats`
--

DROP TABLE IF EXISTS `Floats`;
CREATE TABLE `Floats` (
  `id_Float` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` double DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `GlobalStatistic`
--

DROP TABLE IF EXISTS `GlobalStatistic`;
CREATE TABLE `GlobalStatistic` (
  `id_statistic` int(11) NOT NULL,
  `timestamp` int(10) UNSIGNED DEFAULT NULL,
  `eventName` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entityName` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entityId` int(11) DEFAULT NULL,
  `modulename` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `realUserId` int(11) DEFAULT NULL,
  `realUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Groups`
--

DROP TABLE IF EXISTS `Groups`;
CREATE TABLE `Groups` (
  `id_group` int(11) NOT NULL,
  `groupname` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modulename` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `portal` longtext COLLATE utf8mb4_unicode_ci,
  `htmlContent` longtext COLLATE utf8mb4_unicode_ci,
  `id_group_parent` int(11) DEFAULT NULL,
  `subscription` longtext COLLATE utf8mb4_unicode_ci,
  `emailNotification` longtext COLLATE utf8mb4_unicode_ci,
  `xmlPublish` longtext COLLATE utf8mb4_unicode_ci,
  `activities` text COLLATE utf8mb4_unicode_ci,
  `sys_date` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_lockId` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_lockMicroTime` bigint(20) UNSIGNED DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Groups_Activities`
--

DROP TABLE IF EXISTS `Groups_Activities`;
CREATE TABLE `Groups_Activities` (
  `id_group_activity` int(11) NOT NULL,
  `id_group` int(11) DEFAULT NULL,
  `activityname` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Groups_Groups`
--

DROP TABLE IF EXISTS `Groups_Groups`;
CREATE TABLE `Groups_Groups` (
  `id_relation_group` int(11) NOT NULL,
  `id_group_owner` int(11) DEFAULT NULL,
  `id_group` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Links`
--

DROP TABLE IF EXISTS `Links`;
CREATE TABLE `Links` (
  `id_Link` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` int(11) DEFAULT NULL,
  `commonId` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `MultipleAttributs`
--

DROP TABLE IF EXISTS `MultipleAttributs`;
CREATE TABLE `MultipleAttributs` (
  `id_MultipleAttribut` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Numerics`
--

DROP TABLE IF EXISTS `Numerics`;
CREATE TABLE `Numerics` (
  `id_Numeric` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` decimal(32,2) DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SessionAdminService`
--

DROP TABLE IF EXISTS `SessionAdminService`;
CREATE TABLE `SessionAdminService` (
  `id_data` int(11) NOT NULL,
  `key` varchar(224) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Strings`
--

DROP TABLE IF EXISTS `Strings`;
CREATE TABLE `Strings` (
  `id_String` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Texts`
--

DROP TABLE IF EXISTS `Texts`;
CREATE TABLE `Texts` (
  `id_Text` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l01` text COLLATE utf8mb4_unicode_ci,
  `value_l02` text COLLATE utf8mb4_unicode_ci,
  `value_l03` text COLLATE utf8mb4_unicode_ci,
  `value_l04` text COLLATE utf8mb4_unicode_ci,
  `value_l05` text COLLATE utf8mb4_unicode_ci,
  `value_l06` text COLLATE utf8mb4_unicode_ci,
  `value_l07` text COLLATE utf8mb4_unicode_ci,
  `value_l08` text COLLATE utf8mb4_unicode_ci,
  `value_l09` text COLLATE utf8mb4_unicode_ci,
  `value_l10` text COLLATE utf8mb4_unicode_ci,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TimeRanges`
--

DROP TABLE IF EXISTS `TimeRanges`;
CREATE TABLE `TimeRanges` (
  `id_TimeRange` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isAllDay` tinyint(1) DEFAULT NULL,
  `begTime` time DEFAULT NULL,
  `endTime` time DEFAULT NULL,
  `begDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Times`
--

DROP TABLE IF EXISTS `Times`;
CREATE TABLE `Times` (
  `id_Time` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` time DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Urls`
--

DROP TABLE IF EXISTS `Urls`;
CREATE TABLE `Urls` (
  `id_url` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Users`
--

DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wigiiNamespace` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passwordHistory` text COLLATE utf8mb4_unicode_ci,
  `passwordLength` int(11) DEFAULT NULL,
  `passwordLife` int(11) DEFAULT NULL,
  `passwordDate` int(11) DEFAULT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emailProofKey` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emailProof` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emailProofStatus` tinyint(1) DEFAULT NULL,
  `moduleAccess` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `info_lastLogin` int(10) UNSIGNED DEFAULT NULL,
  `info_nbLogin` int(10) UNSIGNED DEFAULT NULL,
  `info_lastFailedLogin` int(10) UNSIGNED DEFAULT NULL,
  `info_nbFailedLogin` int(10) UNSIGNED DEFAULT NULL,
  `info_lastLogout` int(10) UNSIGNED DEFAULT NULL,
  `info_lastSessionContext` longtext COLLATE utf8mb4_unicode_ci,
  `info_resetSessionContext` tinyint(1) DEFAULT NULL,
  `authenticationMethod` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authenticationServer` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userCreator` tinyint(1) DEFAULT NULL,
  `groupCreator` text COLLATE utf8mb4_unicode_ci,
  `adminCreator` tinyint(1) DEFAULT NULL,
  `readAllUsersInWigiiNamespace` tinyint(1) DEFAULT NULL,
  `rootGroupCreator` text COLLATE utf8mb4_unicode_ci,
  `readAllGroupsInWigiiNamespace` text COLLATE utf8mb4_unicode_ci,
  `wigiiNamespaceCreator` tinyint(1) DEFAULT NULL,
  `moduleEditor` tinyint(1) DEFAULT NULL,
  `canModifyOwnPassword` tinyint(1) DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_lockId` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_lockMicroTime` bigint(20) UNSIGNED DEFAULT NULL,
  `isRole` tinyint(1) DEFAULT NULL,
  `isCalculatedRole` tinyint(1) DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Users_Groups_Rights`
--

DROP TABLE IF EXISTS `Users_Groups_Rights`;
CREATE TABLE `Users_Groups_Rights` (
  `id_user_group_right` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_group` int(11) DEFAULT NULL,
  `canModify` tinyint(1) DEFAULT NULL,
  `canWriteElement` tinyint(1) DEFAULT NULL,
  `canShareElement` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Users_Users`
--

DROP TABLE IF EXISTS `Users_Users`;
CREATE TABLE `Users_Users` (
  `id_relation_user` int(11) NOT NULL,
  `id_user_owner` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `isOwner` tinyint(1) DEFAULT NULL,
  `hasRole` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Varchars`
--

DROP TABLE IF EXISTS `Varchars`;
CREATE TABLE `Varchars` (
  `id_Varchar` int(11) NOT NULL,
  `id_element` int(11) DEFAULT NULL,
  `field` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l01` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l02` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l03` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l04` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l05` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l06` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l07` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l08` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l09` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_l10` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationUser` int(10) UNSIGNED DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) UNSIGNED DEFAULT NULL,
  `sys_user` int(10) UNSIGNED DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sys_date` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Addresses`
--
ALTER TABLE `Addresses`
  ADD PRIMARY KEY (`id_Addresse`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `street` (`street`),
  ADD KEY `zip_code` (`zip_code`),
  ADD KEY `city` (`city`),
  ADD KEY `state` (`state`),
  ADD KEY `country` (`country`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Attributs`
--
ALTER TABLE `Attributs`
  ADD PRIMARY KEY (`id_Attribut`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `value` (`value`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Blobs`
--
ALTER TABLE `Blobs`
  ADD PRIMARY KEY (`id_Blob`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Booleans`
--
ALTER TABLE `Booleans`
  ADD PRIMARY KEY (`id_Boolean`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `value` (`value`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `ConfigService_parameters`
--
ALTER TABLE `ConfigService_parameters`
  ADD PRIMARY KEY (`id_params`),
  ADD UNIQUE KEY `lp` (`lp`),
  ADD KEY `xmlLp` (`xmlLp`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `ConfigService_xml`
--
ALTER TABLE `ConfigService_xml`
  ADD PRIMARY KEY (`id_xml`),
  ADD UNIQUE KEY `xmlLp` (`xmlLp`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Dates`
--
ALTER TABLE `Dates`
  ADD PRIMARY KEY (`id_Date`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `value` (`value`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Elements`
--
ALTER TABLE `Elements`
  ADD PRIMARY KEY (`id_element`),
  ADD UNIQUE KEY `id_element` (`id_element`,`modulename`),
  ADD KEY `created_by` (`sys_creationUser`),
  ADD KEY `last_modif_user` (`sys_user`),
  ADD KEY `tags` (`tags`),
  ADD KEY `sys_lockId` (`sys_lockId`),
  ADD KEY `state_locked` (`state_locked`),
  ADD KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_date` (`sys_date`),
  ADD KEY `state_important1` (`state_important1`),
  ADD KEY `state_important2` (`state_important2`),
  ADD KEY `state_hidden` (`state_hidden`),
  ADD KEY `state_archived` (`state_archived`),
  ADD KEY `state_deprecated` (`state_deprecated`),
  ADD KEY `state_finalized` (`state_finalized`),
  ADD KEY `state_approved` (`state_approved`),
  ADD KEY `state_dismissed` (`state_dismissed`),
  ADD KEY `state_blocked` (`state_blocked`),
  ADD KEY `version` (`version`),
  ADD KEY `id_element_parent` (`id_element_parent`),
  ADD KEY `peerId` (`peerId`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_user` (`sys_user`);

--
-- Indexes for table `ElementStatistic`
--
ALTER TABLE `ElementStatistic`
  ADD PRIMARY KEY (`id_statistic`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `eventName` (`eventName`),
  ADD KEY `entityName` (`entityName`),
  ADD KEY `modulename` (`modulename`),
  ADD KEY `wigiiNamespace` (`wigiiNamespace`),
  ADD KEY `userId` (`userId`),
  ADD KEY `realUserId` (`realUserId`),
  ADD KEY `elementId` (`elementId`);

--
-- Indexes for table `Elements_Elements`
--
ALTER TABLE `Elements_Elements`
  ADD PRIMARY KEY (`id_elements_elements`),
  ADD UNIQUE KEY `link` (`id_element_owner`,`id_element`,`linkName`),
  ADD KEY `linkName` (`linkName`),
  ADD KEY `linkType` (`linkType`),
  ADD KEY `id_element_src` (`id_element_src`);

--
-- Indexes for table `Elements_Groups`
--
ALTER TABLE `Elements_Groups`
  ADD PRIMARY KEY (`id_element_group`),
  ADD UNIQUE KEY `id_element` (`id_element`,`id_group`),
  ADD KEY `id_group` (`id_group`,`id_element`);

--
-- Indexes for table `Emails`
--
ALTER TABLE `Emails`
  ADD PRIMARY KEY (`id_Email`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `proofKey` (`proofKey`),
  ADD KEY `externalCode` (`externalCode`),
  ADD KEY `externalAccessLevel` (`externalAccessLevel`),
  ADD KEY `externalAccessEndDate` (`externalAccessEndDate`),
  ADD KEY `externalConfigGroup` (`externalConfigGroup`),
  ADD KEY `proofStatus` (`proofStatus`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `EmailService`
--
ALTER TABLE `EmailService`
  ADD PRIMARY KEY (`id_email`),
  ADD KEY `sys_lockId` (`sys_lockId`),
  ADD KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  ADD KEY `status` (`status`),
  ADD KEY `wigiiNamespace` (`wigiiNamespace`),
  ADD KEY `userId` (`userId`),
  ADD KEY `realUserId` (`realUserId`);

--
-- Indexes for table `EmailServiceAttachementsToDelete`
--
ALTER TABLE `EmailServiceAttachementsToDelete`
  ADD PRIMARY KEY (`id_attachementToDelete`),
  ADD UNIQUE KEY `path` (`path`);

--
-- Indexes for table `Files`
--
ALTER TABLE `Files`
  ADD PRIMARY KEY (`id_File`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `name` (`name`),
  ADD KEY `date` (`date`),
  ADD KEY `type` (`type`),
  ADD KEY `mime` (`mime`),
  ADD KEY `size` (`size`),
  ADD KEY `user` (`user`),
  ADD KEY `username` (`username`),
  ADD KEY `path` (`path`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `FileStatistic`
--
ALTER TABLE `FileStatistic`
  ADD PRIMARY KEY (`id_statistic`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `eventName` (`eventName`),
  ADD KEY `entityName` (`entityName`),
  ADD KEY `modulename` (`modulename`),
  ADD KEY `wigiiNamespace` (`wigiiNamespace`),
  ADD KEY `userId` (`userId`),
  ADD KEY `realUserId` (`realUserId`),
  ADD KEY `elementId` (`elementId`),
  ADD KEY `field` (`field`);

--
-- Indexes for table `Floats`
--
ALTER TABLE `Floats`
  ADD PRIMARY KEY (`id_Float`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `value` (`value`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `GlobalStatistic`
--
ALTER TABLE `GlobalStatistic`
  ADD PRIMARY KEY (`id_statistic`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `eventName` (`eventName`),
  ADD KEY `entityName` (`entityName`),
  ADD KEY `entityId` (`entityId`),
  ADD KEY `modulename` (`modulename`),
  ADD KEY `wigiiNamespace` (`wigiiNamespace`),
  ADD KEY `userId` (`userId`),
  ADD KEY `realUserId` (`realUserId`);

--
-- Indexes for table `Groups`
--
ALTER TABLE `Groups`
  ADD PRIMARY KEY (`id_group`),
  ADD KEY `groupname` (`groupname`,`modulename`,`wigiiNamespace`),
  ADD KEY `id_group_parent` (`id_group_parent`),
  ADD KEY `sys_lockId` (`sys_lockId`),
  ADD KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`);

--
-- Indexes for table `Groups_Activities`
--
ALTER TABLE `Groups_Activities`
  ADD PRIMARY KEY (`id_group_activity`),
  ADD UNIQUE KEY `id_group` (`id_group`,`activityname`,`field`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Groups_Groups`
--
ALTER TABLE `Groups_Groups`
  ADD PRIMARY KEY (`id_relation_group`),
  ADD UNIQUE KEY `id_group_owner` (`id_group_owner`,`id_group`);

--
-- Indexes for table `Links`
--
ALTER TABLE `Links`
  ADD PRIMARY KEY (`id_Link`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `commonId` (`commonId`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `MultipleAttributs`
--
ALTER TABLE `MultipleAttributs`
  ADD PRIMARY KEY (`id_MultipleAttribut`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Numerics`
--
ALTER TABLE `Numerics`
  ADD PRIMARY KEY (`id_Numeric`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `value` (`value`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `SessionAdminService`
--
ALTER TABLE `SessionAdminService`
  ADD PRIMARY KEY (`id_data`),
  ADD UNIQUE KEY `key` (`key`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Strings`
--
ALTER TABLE `Strings`
  ADD PRIMARY KEY (`id_String`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `value` (`value`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Texts`
--
ALTER TABLE `Texts`
  ADD PRIMARY KEY (`id_Text`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `TimeRanges`
--
ALTER TABLE `TimeRanges`
  ADD PRIMARY KEY (`id_TimeRange`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `isAllDay` (`isAllDay`),
  ADD KEY `begTime` (`begTime`),
  ADD KEY `endTime` (`endTime`),
  ADD KEY `begDate` (`begDate`),
  ADD KEY `endDate` (`endDate`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Times`
--
ALTER TABLE `Times`
  ADD PRIMARY KEY (`id_Time`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `value` (`value`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Urls`
--
ALTER TABLE `Urls`
  ADD PRIMARY KEY (`id_url`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `name` (`name`),
  ADD KEY `url` (`url`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- Indexes for table `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `wigiiNamespace` (`wigiiNamespace`),
  ADD KEY `username_password` (`username`,`password`),
  ADD KEY `isRole` (`isRole`),
  ADD KEY `isCalculatedRole` (`isCalculatedRole`),
  ADD KEY `sys_lockId` (`sys_lockId`),
  ADD KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  ADD KEY `sys_date` (`sys_date`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `Users_Groups_Rights`
--
ALTER TABLE `Users_Groups_Rights`
  ADD PRIMARY KEY (`id_user_group_right`),
  ADD UNIQUE KEY `id_user` (`id_user`,`id_group`),
  ADD KEY `canModify` (`canModify`),
  ADD KEY `canWriteElement` (`canWriteElement`),
  ADD KEY `canShareElement` (`canShareElement`);

--
-- Indexes for table `Users_Users`
--
ALTER TABLE `Users_Users`
  ADD PRIMARY KEY (`id_relation_user`),
  ADD UNIQUE KEY `isOwner` (`id_user_owner`,`id_user`,`isOwner`),
  ADD UNIQUE KEY `hasRole` (`id_user_owner`,`id_user`,`hasRole`);

--
-- Indexes for table `Varchars`
--
ALTER TABLE `Varchars`
  ADD PRIMARY KEY (`id_Varchar`),
  ADD UNIQUE KEY `id_element` (`id_element`,`field`),
  ADD KEY `value_l01` (`value_l01`),
  ADD KEY `value_l02` (`value_l02`),
  ADD KEY `value_l03` (`value_l03`),
  ADD KEY `value_l04` (`value_l04`),
  ADD KEY `value_l05` (`value_l05`),
  ADD KEY `value_l06` (`value_l06`),
  ADD KEY `value_l07` (`value_l07`),
  ADD KEY `value_l08` (`value_l08`),
  ADD KEY `value_l09` (`value_l09`),
  ADD KEY `value_l10` (`value_l10`),
  ADD KEY `sys_creationUser` (`sys_creationUser`),
  ADD KEY `sys_creationDate` (`sys_creationDate`),
  ADD KEY `sys_user` (`sys_user`),
  ADD KEY `sys_date` (`sys_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Addresses`
--
ALTER TABLE `Addresses`
  MODIFY `id_Addresse` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Attributs`
--
ALTER TABLE `Attributs`
  MODIFY `id_Attribut` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Blobs`
--
ALTER TABLE `Blobs`
  MODIFY `id_Blob` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Booleans`
--
ALTER TABLE `Booleans`
  MODIFY `id_Boolean` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ConfigService_parameters`
--
ALTER TABLE `ConfigService_parameters`
  MODIFY `id_params` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ConfigService_xml`
--
ALTER TABLE `ConfigService_xml`
  MODIFY `id_xml` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Dates`
--
ALTER TABLE `Dates`
  MODIFY `id_Date` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Elements`
--
ALTER TABLE `Elements`
  MODIFY `id_element` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ElementStatistic`
--
ALTER TABLE `ElementStatistic`
  MODIFY `id_statistic` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Elements_Elements`
--
ALTER TABLE `Elements_Elements`
  MODIFY `id_elements_elements` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Elements_Groups`
--
ALTER TABLE `Elements_Groups`
  MODIFY `id_element_group` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Emails`
--
ALTER TABLE `Emails`
  MODIFY `id_Email` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `EmailService`
--
ALTER TABLE `EmailService`
  MODIFY `id_email` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `EmailServiceAttachementsToDelete`
--
ALTER TABLE `EmailServiceAttachementsToDelete`
  MODIFY `id_attachementToDelete` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Files`
--
ALTER TABLE `Files`
  MODIFY `id_File` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `FileStatistic`
--
ALTER TABLE `FileStatistic`
  MODIFY `id_statistic` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Floats`
--
ALTER TABLE `Floats`
  MODIFY `id_Float` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `GlobalStatistic`
--
ALTER TABLE `GlobalStatistic`
  MODIFY `id_statistic` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Groups`
--
ALTER TABLE `Groups`
  MODIFY `id_group` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Groups_Activities`
--
ALTER TABLE `Groups_Activities`
  MODIFY `id_group_activity` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Groups_Groups`
--
ALTER TABLE `Groups_Groups`
  MODIFY `id_relation_group` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Links`
--
ALTER TABLE `Links`
  MODIFY `id_Link` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `MultipleAttributs`
--
ALTER TABLE `MultipleAttributs`
  MODIFY `id_MultipleAttribut` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Numerics`
--
ALTER TABLE `Numerics`
  MODIFY `id_Numeric` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `SessionAdminService`
--
ALTER TABLE `SessionAdminService`
  MODIFY `id_data` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Strings`
--
ALTER TABLE `Strings`
  MODIFY `id_String` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Texts`
--
ALTER TABLE `Texts`
  MODIFY `id_Text` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `TimeRanges`
--
ALTER TABLE `TimeRanges`
  MODIFY `id_TimeRange` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Times`
--
ALTER TABLE `Times`
  MODIFY `id_Time` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Urls`
--
ALTER TABLE `Urls`
  MODIFY `id_url` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Users`
--
ALTER TABLE `Users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Users_Groups_Rights`
--
ALTER TABLE `Users_Groups_Rights`
  MODIFY `id_user_group_right` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Users_Users`
--
ALTER TABLE `Users_Users`
  MODIFY `id_relation_user` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `Varchars`
--
ALTER TABLE `Varchars`
  MODIFY `id_Varchar` int(11) NOT NULL AUTO_INCREMENT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
