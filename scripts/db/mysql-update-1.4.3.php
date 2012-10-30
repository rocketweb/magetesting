<?php

/**
 * This version adds more install statuses and additional error mesage for user
 */
$sql[]= "ALTER TABLE `queue` 
    ADD COLUMN `error_message` VARCHAR(255) NULL COMMENT 'error message shown on instance grid when error occurs'  AFTER `custom_sql` , 
    CHANGE COLUMN `status` `status` ENUM('pending','installing','ready','closed','error','installing-extension','installing-magento','installing-samples','installing-user','installing-files','installing-data') NOT NULL DEFAULT 'pending'  ;
";