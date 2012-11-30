<?php

$sql[]= "ALTER TABLE `instance` ADD COLUMN `backend_name` VARCHAR(50) NULL DEFAULT 'admin' AFTER `instance_name` ";