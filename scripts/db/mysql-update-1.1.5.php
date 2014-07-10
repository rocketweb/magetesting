<?php
$sql[] = '
CREATE TABLE IF NOT EXISTS `store_conflict` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`store_id` int(11) NOT NULL,
`type` varchar(255) COLLATE utf8_bin NOT NULL,
`class` varchar(255) COLLATE utf8_bin NOT NULL,
`rewrites` text COLLATE utf8_bin NOT NULL,
`loaded` text COLLATE utf8_bin NOT NULL,
`ignore` TINYINT(1) NOT NULL DEFAULT \'0\',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;';

$sql[] = "
ALTER TABLE `queue` CHANGE `task`
`task` ENUM('ExtensionInstall','ExtensionOpensource','MagentoDownload','MagentoInstall','MagentoRemove','RevisionCommit','RevisionDeploy','RevisionRollback','RevisionInit','PapertrailUserCreate','PapertrailUserRemove','PapertrailSystemCreate','PapertrailSystemRemove','MagentoHourlyrevert','MagentoReindex','ExtensionConflict')
CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;
";

$sql[] = "
ALTER TABLE `store` CHANGE `status`
`status` ENUM('ready','removing-magento','error','installing-extension','installing-magento','downloading-magento','committing-revision','deploying-revision','rolling-back-revision','creating-papertrail-user','creating-papertrail-system','removing-papertrail-user','removing-papertrail-system','hourly-reverting-magento','reindexing-magento','extension-conflict')
CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'ready';
";

