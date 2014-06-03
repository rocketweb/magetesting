<?php
$sql[] = '
ALTER TABLE `version`
ADD `sorting_order` SMALLINT(2) UNSIGNED NOT NULL
COMMENT \'Sorting order index\' ;';
