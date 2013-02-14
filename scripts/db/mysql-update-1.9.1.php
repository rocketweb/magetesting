<?php

$sql[] = "ALTER TABLE  `store_extension` ADD  `status` ENUM(  'pending',  'processing',  'ready' ) NULL DEFAULT  'pending'";
