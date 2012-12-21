<?php

$sql[] = '
ALTER TABLE  `user`
    ADD  `plan_raised_to_date` TIMESTAMP NULL DEFAULT NULL ,
    ADD  `plan_id_before_raising` INT( 1 ) NULL DEFAULT NULL
';