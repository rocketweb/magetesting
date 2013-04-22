<?php

$sql[] = '
ALTER TABLE extension ADD COLUMN `sort` INT( 2 ) UNSIGNED NOT NULL DEFAULT 0;
';