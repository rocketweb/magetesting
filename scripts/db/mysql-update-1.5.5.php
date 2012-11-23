<?php

$sql[] = '
ALTER TABLE  `extension` ADD  `category_id` INT UNSIGNED NOT NULL AFTER  `description` ,
    ADD  `author` VARCHAR( 100 ) NULL DEFAULT NULL AFTER  `category_id` ,
    ADD INDEX ( `category_id` )
';

$sql[] = '
CREATE TABLE IF NOT EXISTS `extension_category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `class` varchar(15) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;
';
$sql[] = "
INSERT INTO `extension_category` (`id`, `name`, `class`) VALUES
(1, 'User experience', 'cX'),
(2, 'Site management', 'site'),
(3, 'Promotion', 'promo');
";

$sql[] = 'UPDATE extension SET category_id = FLOOR( 1 + ( RAND( ) *3 ) )';