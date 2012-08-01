<?php

$version = '1.1.0';

$sql[] = '
ALTER TABLE queue 
MODIFY COLUMN status
ENUM(\'pending\',\'installing\',\'ready\',\'closed\',\'error\')
NOT NULL
DEFAULT \'pending\'
;
';

$sql[] = '
ALTER TABLE queue 
ADD COLUMN type
VARCHAR(45)
NULL
DEFAULT \'clean\'
AFTER backend_password
;
';

$sql[] = '
ALTER TABLE queue 
ADD COLUMN custom_protocol
VARCHAR(45)
NULL
DEFAULT \'clean\'
AFTER type
;
';

$sql[] = '
ALTER TABLE queue 
ADD COLUMN custom_host
VARCHAR(65)
NULL
AFTER custom_protocol
;
';

$sql[] = '
ALTER TABLE queue 
ADD COLUMN custom_remote_path
VARCHAR(255)
NULL
AFTER custom_host
;
';

$sql[] = '
ALTER TABLE queue 
ADD COLUMN custom_login
VARCHAR(60)
NULL
;
AFTER custom_remote_path
';

$sql[] = '
ALTER TABLE queue 
ADD COLUMN custom_pass
VARCHAR(255)
NULL
AFTER custom_login
;
';

$sql[] = '
ALTER TABLE queue 
ADD COLUMN custom_sql
VARCHAR(255)
NULL
AFTER custom_pass
;
';