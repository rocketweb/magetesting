<?php

$sql[] = '
ALTER TABLE `plan` ADD `can_do_db_revert` TINYINT( 1 ) NULL DEFAULT \'0\'
';

$sql[] = '
ALTER TABLE `store` ADD `do_hourly_db_revert` TINYINT NULL DEFAULT \'0\',
ADD INDEX ( `do_hourly_db_revert` ) 
';

$sql[] = '
ALTER TABLE `store` CHANGE `status` `status` ENUM( \'ready\', \'removing-magento\', \'error\', \'installing-extension\', \'installing-magento\', \'downloading-magento\', \'committing-revision\', \'deploying-revision\', \'rolling-back-revision\', \'creating-papertrail-user\', \'creating-papertrail-system\', \'removing-papertrail-user\', \'removing-papertrail-system\', \'hourly-reverting-magento\' ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT \'ready\'
';

$sql[] = '
ALTER TABLE `queue` CHANGE `task` `task` ENUM( \'ExtensionInstall\', \'ExtensionOpensource\', \'MagentoDownload\', \'MagentoInstall\', \'MagentoRemove\', \'RevisionCommit\', \'RevisionDeploy\', \'RevisionRollback\', \'RevisionInit\', \'PapertrailUserCreate\', \'PapertrailUserRemove\', \'PapertrailSystemCreate\', \'PapertrailSystemRemove\', \'MagentoHourlyRevert\' ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL
';