<?php

$sql[] = '
ALTER TABLE  `plan`
    ADD COLUMN `auto_renew` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'
';