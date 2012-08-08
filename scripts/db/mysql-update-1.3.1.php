<?php

/* this version adds support for extension installer for instances */
$version = '1.3.1';

$sql[] = '
ALTER TABLE  `queue` CHANGE  `status`  `status` ENUM(  \'pending\',  \'installing\',  \'ready\', \'closed\',  \'error\',  \'installing-extension\' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'pending\'
';