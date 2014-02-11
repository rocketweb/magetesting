<?php

$sql[] = '
ALTER TABLE  `queue` CHANGE  `task`  `task` ENUM(\'ExtensionInstall\',\'ExtensionOpensource\',\'MagentoDownload\',\'MagentoInstall\',\'MagentoRemove\',\'RevisionCommit\',\'RevisionDeploy\',\'RevisionRollback\',\'RevisionInit\',\'PapertrailUserCreate\',\'PapertrailUserRemove\',\'PapertrailSystemCreate\',\'PapertrailSystemRemove\',\'MagentoHourlyrevert\',\'MagentoReindex\') DEFAULT NULL
';

$sql[] = '
ALTER TABLE  `store` CHANGE  `status`  `status` enum(\'ready\',\'removing-magento\',\'error\',\'installing-extension\',\'installing-magento\',\'downloading-magento\',\'committing-revision\',\'deploying-revision\',\'rolling-back-revision\',\'creating-papertrail-user\',\'creating-papertrail-system\',\'removing-papertrail-user\',\'removing-papertrail-system\',\'hourly-reverting-magento\',\'reindexing-magento\') NOT NULL DEFAULT  \'ready\'
';