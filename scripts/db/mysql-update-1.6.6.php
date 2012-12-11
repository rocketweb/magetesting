<?php 

$sql[]="ALTER TABLE `instance` CHANGE `status` `status` ENUM( 'ready', 'removing-magento', 'error', 'installing-extension', 'installing-magento', 'downloading-magento', 'committing-revision', 'deploying-revision', 'rolling-back-revision', 'creating-papertrail-user', 'creating-papertrail-system' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'ready'";