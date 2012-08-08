<?php

/* this version adds support for extension installer for instances */
$version = '1.3.0';

$sql[] = '
DROP TABLE IF EXISTS `dev_extension`
';

$sql[] = '
CREATE  TABLE IF NOT EXISTS `dev_extension` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NULL,
  `repo_type` ENUM(\'git\',\'svn\') NULL ,
  `repo_url` VARCHAR(255) NULL ,
  `repo_user` VARCHAR(45) NULL ,
  `repo_password` VARCHAR(45) NULL ,
  `edition` VARCHAR(3) NULL ,
  `from_version` VARCHAR(10) NULL ,
  `to_version` VARCHAR(10) NULL ,
  `extension_config_file` VARCHAR(255) NULL DEFAULT \'relative path from main repo_url\' ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;
';

$sql[] = '
DROP TABLE IF EXISTS `dev_extension_queue`
';

$sql[] = '
CREATE  TABLE IF NOT EXISTS `dev_extension_queue` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `queue_id` INT(11) NOT NULL ,
  `status` ENUM(\'pending\',\'installing\',\'ready\',\'closed\',\'error\') NULL DEFAULT \'pending\' ,
  `user_id` INT(11) NOT NULL ,
  `dev_extension_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dev_extension_queue_queue1` (`queue_id` ASC) ,
  INDEX `fk_dev_extension_queue_user1` (`user_id` ASC) ,
  INDEX `fk_dev_extension_queue_extension1` (`dev_extension_id` ASC) ,
  CONSTRAINT `fk_dev_extension_queue_queue1`
    FOREIGN KEY (`queue_id` )
    REFERENCES `queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dev_extension_queue_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dev_extension_queue_extension1`
    FOREIGN KEY (`dev_extension_id` )
    REFERENCES `dev_extension` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION   
) ENGINE = InnoDB;
';