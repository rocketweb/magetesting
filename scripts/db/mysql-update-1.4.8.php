<?php

$sql[]="ALTER TABLE `edition` CHANGE COLUMN `key` `key` ENUM('CE','PE','EE','GO') NULL DEFAULT NULL  ;";

$sql[]="ALTER TABLE `queue` CHANGE COLUMN `task` `task` ENUM('ExtensionInstall','ExtensionOpenSource','ExtensionRemove','MagentoDownload','MagentoInstall','MagentoRemove','RevisionCommit','RevisionDeploy','RevisionRollback') NULL DEFAULT NULL;";

