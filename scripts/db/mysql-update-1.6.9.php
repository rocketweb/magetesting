<?php

$sql[] = "ALTER TABLE `user` 
    ADD `has_papertrial_account` TINYINT( 1 ) UNSIGNED NULL DEFAULT '0',
    ADD `papertrial_api_token` VARCHAR( 30 ) NULL";
