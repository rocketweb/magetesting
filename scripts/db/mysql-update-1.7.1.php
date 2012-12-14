<?php

$sql[] = "ALTER TABLE `instance` 
    ADD `papertrail_syslog_hostname` VARCHAR(100) NULL,
    ADD `papertrail_syslog_port` INT(10) NULL";