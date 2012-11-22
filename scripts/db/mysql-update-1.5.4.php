<?php

$sql[]="ALTER TABLE `plan` 
    ADD COLUMN `can_add_custom_instance` TINYINT(1) NULL DEFAULT 0 AFTER `phpmyadmin_access`;
";
