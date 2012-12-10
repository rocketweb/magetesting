<?php

$sql[] = "
CREATE  TABLE IF NOT EXISTS `store_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT ,
    `lvl` TINYINT(1) NOT NULL ,
    `type` ENUM('emerg','alert','crit','err','warn','notice','info','debug') NOT NULL ,
    `msg` TEXT NOT NULL ,
    `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    `store_id` INT(11) NOT NULL ,
PRIMARY KEY (`id`)
) ENGINE = InnoDB
";