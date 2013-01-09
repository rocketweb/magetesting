<?php

$sql[]="ALTER TABLE extension_category ADD COLUMN logo VARCHAR(50) NULL DEFAULT NULL";

$sql[]="UPDATE extension_category SET logo='user-experience.jpg' WHERE id=1";
$sql[]="UPDATE extension_category SET logo='site-management.jpg' WHERE id=2";
$sql[]="UPDATE extension_category SET logo='promotion.jpg' WHERE id=3";