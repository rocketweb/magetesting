<?php

$sql[]="ALTER TABLE `instance` ADD COLUMN `server_id` INT(11) NOT NULL DEFAULT 1 AFTER `user_id`";

$sql[]="ALTER TABLE `queue` ADD CONSTRAINT `fk_instance_server`
  FOREIGN KEY (`server_id` )
  REFERENCES `server` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION
, ADD INDEX `fk_instance_server` (`server_id` ASC) ;
";