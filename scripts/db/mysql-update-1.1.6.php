<?php
$sql[] = "
ALTER TABLE `queue` CHANGE `task`
`task` ENUM('ExtensionInstall','ExtensionOpensource','MagentoDownload','MagentoInstall','MagentoInstall2','MagentoRemove','RevisionCommit','RevisionDeploy','RevisionRollback','RevisionInit','PapertrailUserCreate','PapertrailUserRemove','PapertrailSystemCreate','PapertrailSystemRemove','MagentoHourlyrevert','MagentoReindex','ExtensionConflict')
CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;
";
