<?php
$sql[] = '
ALTER TABLE `extension`
ADD `extension_owner_id` INT(11) UNSIGNED NOT NULL
COMMENT \'Owner of the extension\' ;';

$sql[] = "
ALTER TABLE `user` CHANGE `group` `group` ENUM('admin','free-user','awaiting-user','commercial-user','extension-owner') CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'free-user';
";