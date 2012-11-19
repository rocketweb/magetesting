<?php

$sql[]="ALTER TABLE `extension` 
    CHANGE COLUMN `file_name` `extension` VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN `extension_encoded` VARCHAR(100) NULL DEFAULT NULL AFTER `extension`
;
";
