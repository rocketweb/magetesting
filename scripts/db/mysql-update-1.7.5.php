<?php

$sql[] = "ALTER TABLE  `store_extension` ADD  `braintree_transaction_confirmed` INT(1) NULL DEFAULT 0";
$sql[] = "ALTER TABLE  `payment` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT";