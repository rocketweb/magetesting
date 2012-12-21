<?php

/* get rid of normal index, we have it on foreign key already */
$sql[]="ALTER TABLE `extension` DROP INDEX `category_id`";

$sql[]="ALTER TABLE `store` DROP INDEX `store_to_version`";
$sql[]="ALTER TABLE `store` DROP INDEX `store_to_user`";

