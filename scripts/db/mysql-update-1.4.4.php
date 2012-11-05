<?php

$sql[]="ALTER TABLE `plan` 
    ADD COLUMN `billing_period` VARCHAR(20) NULL DEFAULT '7 days'  AFTER `price` , 
    ADD COLUMN `ftp_access` TINYINT(1) NULL DEFAULT 0  AFTER `billing_period` , 
    ADD COLUMN `phpmyadmin_access` TINYINT(1) NULL DEFAULT 0  AFTER `ftp_access` ;";