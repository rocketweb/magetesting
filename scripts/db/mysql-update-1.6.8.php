<?php 

$sql[]="ALTER TABLE `queue` 
    ADD `retry_count` TINYINT( 1 ) UNSIGNED NULL DEFAULT '0' AFTER `task_params` ,
    ADD INDEX ( `retry_count` )";


$sql[] = "ALTER TABLE `queue` 
    CHANGE `task` `task` ENUM('ExtensionInstall','ExtensionOpensource','MagentoDownload','MagentoInstall','MagentoRemove','RevisionCommit','RevisionDeploy','RevisionRollback','RevisionInit','PapertrailUserCreate','PapertrailUserRemove','PapertrailSystemCreate','PapertrailSystemRemove' ) NULL DEFAULT NULL ";

$sql[]="ALTER TABLE `instance` 
    CHANGE `status` `status` ENUM( 'ready', 'removing-magento', 'error', 'installing-extension', 'installing-magento', 'downloading-magento', 'committing-revision', 'deploying-revision', 'rolling-back-revision', 'creating-papertrail-user', 'creating-papertrail-system', 'removing-papertrail-user', 'removing-papertrail-system' ) NULL DEFAULT 'ready'";