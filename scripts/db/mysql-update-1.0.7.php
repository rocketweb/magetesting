<?php

$sql[] = '
ALTER TABLE  `queue` ADD `next_execution_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
';