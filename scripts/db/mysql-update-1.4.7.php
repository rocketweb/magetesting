<?php

$sql[]="ALTER TABLE `queue` RENAME TO `instance`;";

$sql[]="ALTER TABLE `instance` DROP INDEX `queue_to_version` ,
ADD INDEX `instance_to_version` ( `version_id` )
";
$sql[]="ALTER TABLE `instance` DROP INDEX `queue_to_user1` ,
ADD INDEX `instance_to_user1` ( `user_id` ) ";

$sql[]="CREATE  TABLE `user_extension` (
  `extension_id` INT NOT NULL ,
  `user_id` INT NULL ,
  PRIMARY KEY (`extension_id`) 
  ) ENGINE = InnoDB;
";

$sql[]="CREATE  TABLE `instance_extension` (
  `extension_id` INT NOT NULL ,
  `instance_id` INT NULL ,
  PRIMARY KEY (`extension_id`) 
  ) ENGINE = InnoDB
  ;
";

$sql[]="CREATE  TABLE `server` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT
,  `name` VARCHAR(45) NULL ,
  `description` VARCHAR(255) NULL ,
  `domain` VARCHAR(60) NULL ,
  `ip` VARCHAR(15) NULL ,
  PRIMARY KEY (`id`) 
  ) ENGINE = InnoDB;
";

$sql[]="INSERT INTO `server` (`name`,`description`,`domain`,`ip`) 
    VALUES 
    ('Magetesting server1','this server','dev.magetesting.com','127.0.0.1')";

$sql[]="ALTER TABLE `dev_extension_queue` DROP FOREIGN KEY `fk_dev_extension_queue_queue1` ;";

$sql[]="ALTER TABLE `dev_extension_queue` CHANGE COLUMN `queue_id` `instance_id` INT(11) NOT NULL  , 
  ADD CONSTRAINT `fk_dev_extension_queue_queue1`
  FOREIGN KEY (`instance_id` )
  REFERENCES `instance` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, DROP INDEX `fk_dev_extension_queue_queue1` 
, ADD INDEX `fk_dev_extension_queue_instance1` (`instance_id` ASC) ;";

$sql[]="ALTER TABLE `extension_queue` 
    ADD COLUMN `task` VARCHAR(45) NULL  AFTER `extension_id` , 
    RENAME TO  `queue` ;
";

$sql[]="ALTER TABLE `queue` CHANGE COLUMN `queue_id` `instance_id` INT(11) NOT NULL";

$sql[]="ALTER TABLE `queue` DROP INDEX `fk_extension_queue_queue1` ,
ADD INDEX `fk_queue_instance1` ( `instance_id` ) ";

$sql[]="ALTER TABLE `queue` DROP INDEX `fk_extension_queue_user1` ,
ADD INDEX `fk_queue_user1` ( `user_id` ) ";

$sql[]="ALTER TABLE `queue` DROP INDEX `fk_extension_queue_extension1` ,
ADD INDEX `fk_queue_extension1` ( `extension_id` ) ";

$sql[]="ALTER TABLE `queue` 
    DROP FOREIGN KEY `fk_extension_queue_queue1` ;";



$sql[]="ALTER TABLE `queue` 
  ADD CONSTRAINT `fk_queue_instance1`
  FOREIGN KEY (`instance_id` )
  REFERENCES `instance` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_queue_instance1` (`instance_id` ASC) ;";


$sql[]="ALTER TABLE `queue` ADD COLUMN `server_id` INT(11) UNSIGNED NOT NULL  AFTER `extension_id`";
 
$sql[]="ALTER TABLE `queue` SET `server_id`=1;";

$sql[]="ALTER TABLE `queue` ADD CONSTRAINT `fk_queue_server1`
  FOREIGN KEY (`server_id` )
  REFERENCES `server` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION
, ADD INDEX `fk_queue_server1` (`server_id` ASC) ;
";

$sql[]="ALTER TABLE `queue` 
    ADD COLUMN `parent_id` INT(11) NULL COMMENT 'parent id from this table, telling us if we should wait until parent_id is finished'  AFTER `extension_id` ;
";
 
$sql[]="ALTER TABLE `queue` 
    CHANGE COLUMN `status` `status` ENUM('pending','installing','ready','closed','error','installing-extension','installing-magento','installing-samples','installing-user','installing-files','installing-data') NULL DEFAULT 'pending'  ;";

