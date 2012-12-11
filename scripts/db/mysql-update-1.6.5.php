<?php

$sql[]="ALTER TABLE `instance` 
    CHANGE `status` `status` ENUM('ready','removing-magento','error','installing-extension','installing-magento','downloading-magento','commiting-revision','deploying-revision','rolling-back-revision','creating-papertrail-user','creating-papertrail-system') NULL DEFAULT 'ready'";