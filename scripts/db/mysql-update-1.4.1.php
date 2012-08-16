<?php

/* this version adds fix for braintree payments subscription id field */
$version = '1.4.1';

//for admin purposes
$sql[] = '
ALTER TABLE  `user` CHANGE  `braintree_subscription_id`  `braintree_subscription_id` VARCHAR( 20 ) NULL DEFAULT NULL
';