-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `passkeys`
--

DROP TABLE IF EXISTS `passkeys`;
CREATE TABLE `passkeys` (
  `id` int NOT NULL AUTO_INCREMENT,
  `credentialId` varchar(1023) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL COMMENT 'Received from the authentication device',
  `relyingParty` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Set in the reg_admin.ini file as how many parts of the hostname (R-L)',
  `source` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Which application created this entry',
  `userId` varchar(64) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'sha256 of the email address (hex string)',
  `userName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'This is the email address again, but stored separately in case we want to change the userId',
  `userDisplayName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'This is a friendly name the user chooses when registering the passkey',
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createIP` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastUsedDate` datetime DEFAULT NULL,
  `lastUsedIP` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `useCount` int NOT NULL DEFAULT '0',
  `publicKey` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passkeys_cid_idx` (`credentialId`),
  KEY `passkeys_uid_idx` (`userId`),
  KEY `passkeys_uname_idx` (`userName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


