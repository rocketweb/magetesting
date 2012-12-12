<?php

$sql[] = "ALTER TABLE `user` 
    CHANGE `papertrial_api_token` `papertrail_api_token` VARCHAR( 30 ) NULL,
    CHANGE `has_papertrial_account` `has_papertrail_account` TINYINT( 1 ) UNSIGNED NULL DEFAULT '0'";
