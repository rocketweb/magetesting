<?php

$sql[] = '
ALTER TABLE `extension` 
    ADD COLUMN `extension_detail` VARCHAR(255) NULL DEFAULT NULL  AFTER `sort` , 
    ADD COLUMN `extension_documentation` VARCHAR(255) NULL DEFAULT NULL  AFTER `extension_detail` ;
';