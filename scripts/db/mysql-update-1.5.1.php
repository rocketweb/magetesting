<?php

$sql[]="ALTER TABLE `instance_extension` 
    ADD COLUMN `id` INT(11) NOT NULL AUTO_INCREMENT  AFTER `instance_id` , 
    ADD COLUMN `added_date` TIMESTAMP NOT NULL  AFTER `id` , 
    CHANGE COLUMN `instance_id` `instance_id` INT(11) NOT NULL  
, DROP PRIMARY KEY 
, ADD PRIMARY KEY (`id`) ;
;
";

$sql[]="ALTER TABLE `queue` 
    CHANGE COLUMN `task` `task` ENUM('ExtensionInstall','ExtensionOpensource','ExtensionRemove','MagentoDownload','MagentoInstall','MagentoRemove','RevisionCommit','RevisionDeploy','RevisionRollback') NULL DEFAULT NULL  ;
";

