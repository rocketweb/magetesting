<?php

/*if plan is removed, remove all coupons assigned to it */

$sql[]="ALTER TABLE `coupon` CHANGE `plan_id` `plan_id` INT( 11 ) UNSIGNED NOT NULL";

$sql[]=  'ALTER TABLE `coupon` 
ADD CONSTRAINT `fk_coupon_plan`
    FOREIGN KEY (`plan_id` )
    REFERENCES `plan` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD INDEX `fk_coupon_plan` (`plan_id` ASC)';

/* if extension category is removed, remove all assigned extensions  */
$sql[]=  'ALTER TABLE `extension` 
ADD CONSTRAINT `fk_extension_category`
    FOREIGN KEY (`category_id` )
    REFERENCES `extension_category` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
ADD INDEX `fk_extension_category` (`category_id` ASC)';

/*if extension is removed, do not remove extension, 
 * let magento remove cleanup all files */
$sql[]=  'ALTER TABLE `revision` 
ADD CONSTRAINT `fk_revision_extension`
    FOREIGN KEY (`extension_id` )
    REFERENCES `extension` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
ADD INDEX `fk_revision_extension` (`extension_id` ASC)';

/* if version is removed, leave currently installed stores */
$sql[]=  'ALTER TABLE `store` 
ADD CONSTRAINT `fk_store_version`
    FOREIGN KEY (`version_id` )
    REFERENCES `version` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
ADD INDEX `fk_store_version` (`version_id` ASC)';

/* if user is removed leave stores */
$sql[]=  'ALTER TABLE `store` 
ADD CONSTRAINT `fk_store_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
ADD INDEX `fk_store_user` (`user_id` ASC)';

/* if server is removed remove all stores assigned */
$sql[]="ALTER TABLE `store` CHANGE `server_id` `server_id` INT( 11 ) UNSIGNED NOT NULL";
$sql[]=  'ALTER TABLE `store` 
ADD CONSTRAINT `fk_store_server`
    FOREIGN KEY (`server_id` )
    REFERENCES `server` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD INDEX `fk_store_server` (`server_id` ASC)';

/* remove store extension when extension is removed */
$sql[]=  'ALTER TABLE `store_extension` 
ADD CONSTRAINT `fk_store_extension_extension`
    FOREIGN KEY (`extension_id` )
    REFERENCES `extension` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD INDEX `fk_store_extension_extension` (`extension_id` ASC)';

/* remove store logs when store is removed */
$sql[]=  'ALTER TABLE `store_log` 
ADD CONSTRAINT `fk_store_log_store`
    FOREIGN KEY (`store_id` )
    REFERENCES `store` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD INDEX `fk_store_log_store` (`store_id` ASC)';

$sql[]="DROP TABLE IF EXISTS user_extension";
$sql[]="DROP TABLE IF EXISTS dev_extension_queue";
$sql[]="DROP TABLE IF EXISTS dev_extension";

/* adding/updating missing extensions */
$sql[]="INSERT INTO `version` (`id`, `edition`, `version`, `sample_data_version`) VALUES
(10, 'EE', '1.11.2.0', '1.3.1'),
(11, 'EE', '1.12.0.2', '1.3.1'),
(12, 'CE', '1.7.0.1', '1.6.1.0'),
(13, 'CE', '1.7.0.2', '1.6.1.0');";

/* update alpha to stable */
$sql[]="UPDATE `version` SET `version`='1.7.0.0' WHERE id=7;";