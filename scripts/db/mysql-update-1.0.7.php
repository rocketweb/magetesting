<?php

$sql[] = '
ALTER TABLE  `queue` ADD `next_execution_time` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\'
';