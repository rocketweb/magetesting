<?php

$sql[]="UPDATE revision SET extension_id=NULL WHERE extension_id=0;";
$sql[]="ALTER TABLE `revision` CHANGE `extension_id` `extension_id` INT( 11 ) NULL DEFAULT NULL; ";