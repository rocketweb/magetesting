<?php

/* this version adds version number to indicate extension version */
$version = '1.4.1';

//for admin purposes
$sql[] = '
ALTER TABLE  `extension` ADD  `version` VARCHAR( 11 ) NULL AFTER  `name`
';

$sql[] = '
ALTER TABLE  `dev_extension` ADD  `version` VARCHAR( 11 ) NULL AFTER  `name`
';
