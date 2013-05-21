<?php

$sql[] = '
ALTER TABLE  `extension` CHANGE  `is_dev`  `is_visible` INT(1) UNSIGNED NOT NULL DEFAULT 1
';

$sql[] = '
UPDATE `extension` SET is_visible = 1
';