<?php

$sql[] = '
ALTER TABLE  `extension` CHANGE  `version`  `version` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL
';
