<?php

$sql[] = '
ALTER TABLE `plan` ADD `can_do_db_revert` TINYINT( 1 ) NULL DEFAULT \'0\'
';

$sql[] = '
ALTER TABLE `store` ADD `do_hourly_db_revert` TINYINT NULL DEFAULT \'0\',
ADD INDEX ( `do_hourly_db_revert` ) ';