<?php

$sql[] = '
ALTER TABLE store 
ADD COLUMN custom_port
VARCHAR(45)
NULL
DEFAULT \'\'
AFTER custom_host
;
';