<?php

$sql[] = '
ALTER TABLE user ADD COLUMN apikey VARCHAR(40) NOT NULL
';
$sql[] = '
UPDATE user SET apikey = sha1(CONCAT(CURRENT_TIMESTAMP, \' \', id))
';