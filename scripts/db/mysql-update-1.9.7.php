<?php

$sql[] = 'CREATE TABLE extension_new AS SELECT * FROM extension';
$sql[] = 'SET foreign_key_checks = 0';
$sql[] = 'DROP TABLE extension';
$sql[] = 'SET foreign_key_checks = 1';
$sql[] = 'ALTER TABLE  `extension_new` ENGINE = MYISAM';
$sql[] = 'RENAME TABLE `extension_new` TO `extension`';

$sql[] = '
ALTER TABLE `extension` ADD PRIMARY KEY(`id`)
';

$sql[] = '
ALTER TABLE extension ADD UNIQUE INDEX extension_release (extension_key, edition, version)
';

$sql[] =  '
ALTER TABLE `extension`
    ADD CONSTRAINT `fk_extension_category`
        FOREIGN KEY (`category_id` )
        REFERENCES `extension_category` (`id` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    ADD INDEX `fk_extension_category` (`category_id` ASC)
';

$sql[] = 'ALTER TABLE `extension` CHANGE  `name`  `name` VARCHAR( 45 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL';
$sql[] = 'ALTER TABLE `extension` CHANGE  `description`  `description` VARCHAR( 500 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL';
$sql[] = 'ALTER TABLE extension ADD FULLTEXT(name, description)';
$sql[] = 'ALTER TABLE  `extension` CHANGE  `id`  `id` INT( 11 ) NOT NULL DEFAULT NULL AUTO_INCREMENT';