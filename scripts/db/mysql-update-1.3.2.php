<?php

/* this version adds support for extension installer for instances */
$version = '1.3.2';

//for admin purposes
$sql[] = '
ALTER TABLE  `extension` ADD  `is_dev` TINYINT NOT NULL DEFAULT  \'0\'
';

$sql[]= '
    ALTER TABLE  `extension` ADD  `description` VARCHAR( 500 ) NULL AFTER  `name`;
';

//this will be used for dependency checks and allowed package updates
$sql[]= '
    ALTER TABLE  `extension` ADD  `namespace_module` VARCHAR( 255 ) NULL AFTER  `file_name`;
';