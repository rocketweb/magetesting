<?php

$sql[] = '
ALTER TABLE  `plan`
    ADD COLUMN `price_description` VARCHAR(20) NOT NULL DEFAULT \'\'
    AFTER `price`
';

$sql[] = '
ALTER TABLE  `plan`
    ADD COLUMN `billing_description` VARCHAR(20) NOT NULL DEFAULT \'\'
    AFTER `billing_period`
';