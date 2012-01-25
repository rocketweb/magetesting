SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `user` ;

CREATE  TABLE IF NOT EXISTS `user` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `login` VARCHAR(50) NOT NULL ,
  `password` CHAR(40) NOT NULL ,
  `email` VARCHAR(50) NOT NULL ,
  `firstname` VARCHAR(50) NOT NULL ,
  `lastname` VARCHAR(50) NOT NULL ,
  `added_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' ,
  `group` ENUM('admin','standard-user','commercial-user') NOT NULL DEFAULT 'standard-user' ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `login_UNIQUE` (`login` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `version`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `version` ;

CREATE  TABLE IF NOT EXISTS `version` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `edition` ENUM('CE','PE','EE') NOT NULL DEFAULT 'CE' ,
  `version` VARCHAR(10) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `queue`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `queue` ;

CREATE  TABLE IF NOT EXISTS `queue` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `edition` ENUM('CE','PE','EE') NOT NULL DEFAULT 'CE' ,
  `status` ENUM('ready','installing','closed','pending') NOT NULL ,
  `version_id` INT(11) NOT NULL ,
  `user_id` INT(11) NOT NULL ,
  `domain` VARCHAR(10) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `queue_to_version` (`version_id` ASC) ,
  INDEX `queue_to_user1` (`user_id` ASC) ,
  CONSTRAINT `queue_to_version`
    FOREIGN KEY (`version_id` )
    REFERENCES `version` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `queue_to_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `log`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `log` ;

CREATE  TABLE IF NOT EXISTS `log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `lvl` TINYINT(1) NOT NULL ,
  `type` ENUM('emerg','alert','crit','err','warn','notice','info','debug') NOT NULL ,
  `msg` TEXT NOT NULL ,
  `info` TEXT NULL ,
  `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `session`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `session` ;

CREATE  TABLE IF NOT EXISTS `session` (
  `id` CHAR(32) NOT NULL ,
  `modified` INT(11) NULL ,
  `lifetime` INT(11) NULL ,
  `data` TEXT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `edition`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `edition` ;

CREATE  TABLE IF NOT EXISTS `edition` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `key` VARCHAR(5) NULL ,
  `name` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
