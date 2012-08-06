<?php


$version = '1.1.0';

$sql[] = '
DROP TABLE IF EXISTS `extension` 
';

$sql[] = '
CREATE  TABLE IF NOT EXISTS `extension` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NULL ,
  `file_name` VARCHAR(255) NULL ,
  `from_version` VARCHAR(10) NULL ,
  `to_version` VARCHAR(10) NULL ,
  `edition` VARCHAR(5) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;
';

$sql[] = '
DROP TABLE IF EXISTS `extension_queue` ;
';

$sql[] = '
CREATE  TABLE IF NOT EXISTS `extension_queue` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `queue_id` INT(11) NOT NULL ,
  `status` ENUM(\'pending\',\'installing\',\'ready\',\'closed\',\'error\') NULL DEFAULT \'pending\' ,
  `user_id` INT(11) NOT NULL ,
  `extension_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_extension_queue_queue1` (`queue_id` ASC) ,
  INDEX `fk_extension_queue_user1` (`user_id` ASC) ,
  INDEX `fk_extension_queue_extension1` (`extension_id` ASC) ,
  CONSTRAINT `fk_extension_queue_queue1`
    FOREIGN KEY (`queue_id` )
    REFERENCES `queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_extension_queue_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_extension_queue_extension1`
    FOREIGN KEY (`extension_id` )
    REFERENCES `extension` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
    PRIMARY KEY (`id`)    
),    
ENGINE = InnoDB;
';