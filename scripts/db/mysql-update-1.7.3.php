<?php

// drop indexes

$sql[]="ALTER TABLE `dev_extension_queue` 
    DROP INDEX `fk_dev_extension_queue_instance1`,
    DROP FOREIGN KEY `fk_dev_extension_queue_instance1`;";

$sql[]="ALTER TABLE `instance`
    DROP INDEX `instance_to_version`,
    DROP FOREIGN KEY `queue_to_version`,
    DROP INDEX `instance_to_user1`,
    DROP FOREIGN KEY `queue_to_user1`;";

$sql[]="ALTER TABLE `instance_extension`
    DROP INDEX `fk_store_extension_store`,
    DROP FOREIGN KEY `fk_store_extension_store`;";

$sql[]="ALTER TABLE `queue`
    DROP INDEX `fk_queue_instance1`,
    DROP FOREIGN KEY `fk_queue_instance1`,
    DROP INDEX `fk_instance_server`,
    DROP FOREIGN KEY `fk_instance_server`;";

$sql[]="ALTER TABLE `revision`
    DROP INDEX `fk_revision_instance`,
    DROP FOREIGN KEY `fk_revision_instance`;";
    
$sql[]="ALTER TABLE `dev_extension_queue`
    CHANGE COLUMN `instance_id` `store_id` INT(11) NOT NULL";

$sql[]="ALTER TABLE `instance_extension`
    CHANGE COLUMN `instance_id` `store_id` INT(11) NOT NULL";

$sql[]="ALTER TABLE `plan`
    CHANGE COLUMN `instances` `stores` INT(3) UNSIGNED NOT NULL DEFAULT 0";

$sql[]="ALTER TABLE `plan`
    CHANGE COLUMN `can_add_custom_instance` `can_add_custom_store` INT(3) UNSIGNED NOT NULL DEFAULT 0";

$sql[]="ALTER TABLE `queue`
    CHANGE COLUMN `instance_id` `store_id` INT(11) NOT NULL";

$sql[]="ALTER TABLE `revision`
    CHANGE COLUMN `instance_id` `store_id` INT(11) NOT NULL";

$sql[]="ALTER TABLE `instance`
    CHANGE COLUMN `instance_name` `store_name` VARCHAR(100) NULL DEFAULT NULL";

$sql[]="RENAME TABLE `instance_extension` TO `store_extension`";
$sql[]="RENAME TABLE `instance` TO `store`";

$sql[]="ALTER TABLE `store`
    ADD INDEX `store_to_version` (`version_id` ASC),
    ADD INDEX `store_to_user` (`user_id` ASC);";

$sql[]="ALTER TABLE `dev_extension_queue`
    ADD INDEX `fk_dev_extension_queue_store` (`store_id` ASC),
    ADD CONSTRAINT `fk_dev_extension_queue_store`
  FOREIGN KEY (`store_id` )
  REFERENCES `store` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION;";

$sql[]="ALTER TABLE `store_extension`
    ADD INDEX `fk_store_extension_store` (`store_id` ASC),
    ADD CONSTRAINT `fk_store_extension_store`
  FOREIGN KEY (`store_id` )
  REFERENCES `store` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION;";

$sql[]="ALTER TABLE `queue`
    ADD INDEX `fk_queue_store` (`store_id` ASC),
    ADD CONSTRAINT `fk_queue_store`
  FOREIGN KEY (`store_id` )
  REFERENCES `store` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;";

$sql[]="ALTER TABLE `revision`
    ADD INDEX `fk_revision_store` (`store_id` ASC),
    ADD CONSTRAINT `fk_revision_store`
  FOREIGN KEY (`store_id` )
  REFERENCES `store` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION;";
