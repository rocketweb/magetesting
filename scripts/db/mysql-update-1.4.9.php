<?php

$sql[] = "ALTER TABLE `user` ADD COLUMN `server_id` INT(11) UNSIGNED NULL;";

$sql[] = 'UPDATE `user` SET server_id = 1';