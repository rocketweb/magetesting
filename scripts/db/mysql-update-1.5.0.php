<?php

$sql[] = '
ALTER TABLE  `plan` ADD  `paypal_id` VARCHAR( 15 ) NULL ,
    ADD  `braintree_id` VARCHAR( 15 ) NULL ,
    ADD  `is_hidden` INT( 1 ) UNSIGNED NOT NULL DEFAULT  \'0\'
';

$sql[] = '
UPDATE  `plan` SET  `paypal_id` =  \'Y48F49QFEHAV2\',
`braintree_id` =  \'standard\' WHERE  `plan`.`id` = 1;
';

$sql[] = '
UPDATE  `plan` SET  `paypal_id` =  \'DYS5CU8LCDQ48\',
`braintree_id` =  \'business\' WHERE  `plan`.`id` = 2;
';