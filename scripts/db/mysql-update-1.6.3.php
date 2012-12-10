<?php

$sql[]="ALTER TABLE `queue` 
    CHANGE `status` `status` ENUM( 'pending', 'processing', 'ready' ) NULL DEFAULT 'pending'";