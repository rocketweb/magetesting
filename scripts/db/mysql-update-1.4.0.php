<?php

/* this version adds support for braintree payments */
$version = '1.4.0';

//for admin purposes
$sql[] = '
ALTER TABLE `user` ADD `braintree_vault_id` INT NOT NULL
';

$sql[] = '
ALTER TABLE `user` CHANGE `braintree_vault_id` `braintree_vault_id` INT( 11 ) NULL 
';

$sql[] = '
ALTER TABLE `user` ADD `braintree_subscription_id` INT NULL
';
