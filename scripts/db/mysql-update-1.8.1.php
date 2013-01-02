<?php

$sql[] = '
ALTER TABLE  `store_extension`
    ADD COLUMN `reminder_sent` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'
';