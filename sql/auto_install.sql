-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from schema.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--
-- /*******************************************************
-- *
-- * Clean up the existing tables - this section generated from drop.tpl
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_osdi_deletion`;
DROP TABLE IF EXISTS `civicrm_osdi_person_sync_state`;
DROP TABLE IF EXISTS `civicrm_osdi_sync_profile`;
DROP TABLE IF EXISTS `civicrm_osdi_flag`;
DROP TABLE IF EXISTS `civicrm_osdi_donation_sync_state`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/


-- /*******************************************************
-- *
-- * civicrm_osdi_flag
-- *
-- * Information about OSDI sync problems
-- *
-- *******************************************************/
CREATE TABLE `civicrm_osdi_flag` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique OsdiFlag ID',
  `contact_id` int unsigned COMMENT 'FK to Contact',
  `remote_object_id` varchar(255) DEFAULT NULL COMMENT 'FK to identifier field on remote system',
  `flag_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL COMMENT 'Status code',
  `message` varchar(511) DEFAULT NULL COMMENT 'Description of the issue',
  `context` text DEFAULT NULL COMMENT 'Structured data to help understand the issue',
  `created_date` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'When the flag was created',
  `modified_date` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When the client was created or modified.',
  PRIMARY KEY (`id`),
  INDEX `index_contact_id`(contact_id),
  INDEX `index_remote_object_id`(remote_object_id),
  INDEX `index_flag_type`(flag_type),
  INDEX `index_status`(status),
  CONSTRAINT FK_civicrm_osdi_flag_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * civicrm_osdi_sync_profile
-- *
-- * OSDI Sync configurations
-- *
-- *******************************************************/
CREATE TABLE `civicrm_osdi_sync_profile` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique OsdiSyncProfile ID',
  `is_default` tinyint DEFAULT 0 COMMENT 'Is this default OSDI SyncProfile?',
  `label` varchar(128) COMMENT 'User-friendly label for the sync configuration',
  `entry_point` varchar(1023) COMMENT 'API entry point (AEP) URL',
  `api_token` varchar(1023) COMMENT 'API token',
  `remote_system` varchar(127) COMMENT 'class name of Remote System',
  `matcher` varchar(127) COMMENT 'class name of Matcher',
  `mapper` varchar(127) COMMENT 'class name of Mapper',
  PRIMARY KEY (`id`)
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * civicrm_osdi_donation_sync_state
-- *
-- * Linkages between CiviCRM Contributions and their counterparts on remote OSDI systems
-- *
-- *******************************************************/
CREATE TABLE `civicrm_osdi_donation_sync_state` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique DonationSyncState ID',
  `contribution_id` int unsigned COMMENT 'FK to Contact',
  `sync_profile_id` int unsigned COMMENT 'FK to OSDI Sync Profile',
  `remote_donation_id` varchar(255) DEFAULT NULL COMMENT 'FK to identifier field on remote system',
  `source` varchar(12) COMMENT 'Whether the donation source was local (CiviCRM) or remote',
  PRIMARY KEY (`id`),
  INDEX `index_sync_profile_id`(sync_profile_id),
  INDEX `index_remote_donation_id`(remote_donation_id),
  CONSTRAINT FK_civicrm_osdi_donation_sync_state_contribution_id FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_civicrm_osdi_donation_sync_state_sync_profile_id FOREIGN KEY (`sync_profile_id`) REFERENCES `civicrm_osdi_sync_profile`(`id`) ON DELETE CASCADE
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * civicrm_osdi_person_sync_state
-- *
-- * Linkages between CiviCRM contacts and their counterparts on remote OSDI systems
-- *
-- *******************************************************/
CREATE TABLE `civicrm_osdi_person_sync_state` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique PersonSyncState ID',
  `contact_id` int unsigned COMMENT 'FK to Contact',
  `sync_profile_id` int unsigned COMMENT 'FK to OSDI Sync Profile',
  `remote_person_id` varchar(255) DEFAULT NULL COMMENT 'FK to identifier field on remote system',
  `remote_pre_sync_modified_time` int unsigned DEFAULT NULL COMMENT 'Modification date and time of the remote person record as of the beginning of the last sync, in unix timestamp format',
  `remote_post_sync_modified_time` int unsigned DEFAULT NULL COMMENT 'Modification date and time of the remote person record at the end of the last sync, in unix timestamp format',
  `local_pre_sync_modified_time` int unsigned DEFAULT NULL COMMENT 'Modification date and time of the local contact record as of the beginning of the last sync, in unix timestamp format',
  `local_post_sync_modified_time` int unsigned DEFAULT NULL COMMENT 'Modification date and time of the local contact record at the end of the last sync, in unix timestamp format',
  `sync_time` int unsigned DEFAULT NULL COMMENT 'Date and time of the last sync, in unix timestamp format',
  `sync_origin` tinyint DEFAULT NULL COMMENT '0 if local CiviCRM was the origin of the last sync, 1 if remote system was the origin',
  `sync_status` varchar(255) DEFAULT NULL COMMENT 'Status of the last sync',
  PRIMARY KEY (`id`),
  INDEX `index_contact_id`(contact_id),
  INDEX `index_sync_profile_id`(sync_profile_id),
  INDEX `index_remote_person_id`(remote_person_id),
  INDEX `index_remote_pre_sync_modified_time`(remote_pre_sync_modified_time),
  INDEX `index_remote_post_sync_modified_time`(remote_post_sync_modified_time),
  INDEX `index_local_pre_sync_modified_time`(local_pre_sync_modified_time),
  INDEX `index_local_post_sync_modified_time`(local_post_sync_modified_time),
  INDEX `index_sync_time`(sync_time),
  INDEX `index_sync_status`(sync_status),
  CONSTRAINT FK_civicrm_osdi_person_sync_state_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_civicrm_osdi_person_sync_state_sync_profile_id FOREIGN KEY (`sync_profile_id`) REFERENCES `civicrm_osdi_sync_profile`(`id`) ON DELETE CASCADE
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * civicrm_osdi_deletion
-- *
-- * Data about deletions synced from Civi to an OSDI remote system
-- *
-- *******************************************************/
CREATE TABLE `civicrm_osdi_deletion` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique OsdiDeletion ID',
  `sync_profile_id` int unsigned COMMENT 'FK to OSDI Sync Profile',
  `remote_object_id` varchar(255) DEFAULT NULL COMMENT 'FK to identifier field on remote system',
  PRIMARY KEY (`id`),
  INDEX `index_sync_profile_id`(sync_profile_id),
  INDEX `index_remote_object_id`(remote_object_id),
  CONSTRAINT FK_civicrm_osdi_deletion_sync_profile_id FOREIGN KEY (`sync_profile_id`) REFERENCES `civicrm_osdi_sync_profile`(`id`) ON DELETE CASCADE
)
ENGINE=InnoDB;
