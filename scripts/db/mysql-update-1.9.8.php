<?php

$sql[] = '
ALTER TABLE  `extension` CHANGE  `extension_key`  `extension_key` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
';

$sql[] = '
ALTER TABLE  `extension` CHANGE  `author`  `author` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
';

$sql[] =  '
ALTER TABLE `extension` DROP INDEX `name`
';

$sql[] = 'ALTER TABLE extension ADD FULLTEXT(name, description, author, extension_key)';