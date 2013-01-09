<?php

$sql[] = "ALTER TABLE  `user` CHANGE  `status`  `status` ENUM(  'active',  'inactive',  'deleted' ) NOT NULL DEFAULT 'inactive'";