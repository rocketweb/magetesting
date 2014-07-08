<?php
$sql[] = '
CREATE TABLE IF NOT EXISTS `store_conflict` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`store_id` int(11) NOT NULL,
`module` varchar(255) COLLATE utf8_bin NOT NULL,
`class` varchar(255) COLLATE utf8_bin NOT NULL,
`rewrites` text COLLATE utf8_bin NOT NULL,
`ignore` tinyint(1) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;';

