<?php

$sql[] = '
ALTER TABLE payment 
    DROP FOREIGN KEY `plan_has_payments`,
    DROP INDEX `plan_has_payments`,
    CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHANGE `plan_id` `plan_name` VARCHAR(45) NOT NULL
';

$sql[] = '
    UPDATE payment, plan SET plan_name = name WHERE plan_name =  plan.id
';