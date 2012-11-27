<?php

$sql[]= "ALTER TABLE `queue` 
    CHANGE `task` `task` ENUM( 'ExtensionInstall', 'ExtensionOpensource', 'ExtensionRemove', 'MagentoDownload', 'MagentoInstall', 'MagentoRemove', 'RevisionCommit', 'RevisionDeploy', 'RevisionRollback', 'RevisionInit' ) NULL DEFAULT NULL ";

$sql[]= "ALTER TABLE `queue` 
    ADD COLUMN `task_params` TEXT NULL DEFAULT NULL COMMENT 'additional task parameters, e.g: commit comment' AFTER `task`";

$sql[] = "CREATE TABLE IF NOT EXISTS `revision` (
  `id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(30) NOT NULL,
  `comment` text NOT NULL,
  `hash` varchar(32) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `db_before_revision` varchar(255) NOT NULL
) ENGINE=InnoDB";

$sql[] = "ALTER TABLE `revision` 
  ADD CONSTRAINT `fk_revision_user`
  FOREIGN KEY (`user_id` )
  REFERENCES `user` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION
, ADD INDEX `fk_revision_user` (`user_id` ASC)";

$sql[] = "ALTER TABLE `revision` 
  ADD CONSTRAINT `fk_revision_instance`
  FOREIGN KEY (`instance_id` )
  REFERENCES `instance` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION
, ADD INDEX `fk_revision_instance` (`instance_id` ASC),
ADD PRIMARY KEY ( `id` ) ";

$sql[] = "ALTER TABLE `revision` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ";

$sql[]="ALTER TABLE `instance` ADD COLUMN `revision_count` INT(11) NULL DEFAULT '1' ";