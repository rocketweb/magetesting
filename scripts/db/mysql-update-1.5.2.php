<?php

$sql[]="ALTER TABLE `queue` 
    ADD COLUMN `added_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `task` ;";

$sql[]="ALTER TABLE `instance_extension` 
    CHANGE COLUMN `added_date` `added_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP  ;";
