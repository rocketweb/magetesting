<?php

$sql[] = '
ALTER TABLE  `plan` AUTO_INCREMENT = 5
';

$sql[] = '
ALTER TABLE  `plan` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT
';