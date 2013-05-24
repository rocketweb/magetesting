<?php

$sql[] = '
ALTER TABLE  `user` ADD  `active_from` DATETIME NULL
';
$sql[] = '
ALTER TABLE  `user` ADD  `active_from_reminded` INT(1) UNSIGNED NOT NULL DEFAULT 0
';

$sql[] = '
UPDATE user SET active_from = added_date
';

$sql[] = '
UPDATE user SET active_from_reminded = 1
';