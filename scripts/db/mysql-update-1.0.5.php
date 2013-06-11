<?php

$sql[] = '
ALTER TABLE  `user` ADD  `additional_stores` INT(3) UNSIGNED NOT NULL DEFAULT 0
';

$sql[] = '
ALTER TABLE  `user` ADD  `additional_stores_removed` INT(3) UNSIGNED NOT NULL DEFAULT 0
';

$sql[] = '
ALTER TABLE  `plan` ADD  `max_stores` INT(3) UNSIGNED NOT NULL DEFAULT 0
';

$sql[] = '
ALTER TABLE  `plan` ADD  `store_price` INT(3) UNSIGNED NOT NULL DEFAULT 0
';