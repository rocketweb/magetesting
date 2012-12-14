<?php

$sql[] = "ALTER TABLE  `store_extension` ADD  `braintree_transaction_id` VARCHAR( 10 ) NULL DEFAULT NULL";
$sql[] = "
ALTER TABLE  `payment` 
    CHANGE  `subscr_id`  `braintree_transaction_id` VARCHAR( 10 ) NOT NULL,
    CHANGE  `plan_name`  `transaction_name` VARCHAR( 45 ) NOT NULL,
    ADD  `transaction_type` ENUM(  'subscription',  'extension' ) NOT NULL DEFAULT 'subscription'
";
$sql[] = "
ALTER TABLE `plan`
    DROP `paypal_id`,
    DROP `braintree_id`;
";
$sql[] = "
ALTER TABLE  `user`
    CHANGE  `subscr_id`  `braintree_transaction_confirmed` int(1) NULL DEFAULT 0,
    CHANGE  `braintree_subscription_id`  `braintree_transaction_id` VARCHAR( 10 ) NULL DEFAULT NULL
";