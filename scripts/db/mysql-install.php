<?php

$version = '1.0.0';

$sql[] = '
CREATE TABLE sql_updater(
    version VARCHAR(5) NOT NULL
)
';

// -----------------------------------------------------
// Table `user`
// -----------------------------------------------------
$sql[] = "
DROP TABLE IF EXISTS `user`
";

$sql[] = "
CREATE  TABLE IF NOT EXISTS `user` (
    `id` INT(11) NOT NULL AUTO_INCREMENT ,
    `login` VARCHAR(50) NOT NULL ,
    `password` CHAR(40) NOT NULL ,
    `email` VARCHAR(50) NOT NULL ,
    `firstname` VARCHAR(50) NOT NULL ,
    `lastname` VARCHAR(50) NOT NULL ,
    `street` VARCHAR(50) NULL ,
    `postal_code` VARCHAR(10) NULL ,
    `city` VARCHAR(50) NULL ,
    `state` VARCHAR(50) NULL ,
    `country` VARCHAR(50) NULL ,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'inactive' ,
    `added_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    `group` ENUM('admin','free-user','awaiting-user','commercial-user') NOT NULL DEFAULT 'free-user' ,
    `has_system_account` TINYINT(1) NOT NULL DEFAULT 0 ,
    `system_account_name` VARCHAR(55) NULL ,
    `plan_id` INT NULL DEFAULT 0 ,
    `subscr_id` VARCHAR(19) NULL ,
    `plan_active_to` DATETIME NULL ,
    `downgraded` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`) ,
    UNIQUE INDEX `login_UNIQUE` (`login` ASC)
) ENGINE = InnoDB
";


// -----------------------------------------------------
// Table `version`
// -----------------------------------------------------
$sql[] = "
DROP TABLE IF EXISTS `version`
";

$sql[] = "
CREATE  TABLE IF NOT EXISTS `version` (
    `id` INT(11) NOT NULL AUTO_INCREMENT ,
    `edition` ENUM('CE','PE','EE') NOT NULL DEFAULT 'CE' ,
    `version` VARCHAR(15) NOT NULL ,
    `sample_data_version` VARCHAR(10) NOT NULL ,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
";

// -----------------------------------------------------
// Table `queue`
// -----------------------------------------------------
$sql[] = "
DROP TABLE IF EXISTS `queue`
";

$sql[] = "
CREATE  TABLE IF NOT EXISTS `queue` (
    `id` INT(11) NOT NULL AUTO_INCREMENT ,
    `edition` ENUM('CE','PE','EE') NOT NULL DEFAULT 'CE' ,
    `status` ENUM('ready','installing','closed','pending') NOT NULL DEFAULT 'pending' ,
    `version_id` INT(11) NOT NULL ,
    `user_id` INT(11) NOT NULL ,
    `domain` VARCHAR(10) NOT NULL ,
    `instance_name` VARCHAR(100) NULL ,
    `backend_password` VARCHAR(12) NOT NULL,
    `sample_data` INT(1) UNSIGNED NOT NULL DEFAULT 0 ,
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
        ON UPDATE NO ACTION
) ENGINE = InnoDB
";


// -----------------------------------------------------
// Table `log`
// -----------------------------------------------------
$sql[] = "
DROP TABLE IF EXISTS `log`
";

$sql[] = "
CREATE  TABLE IF NOT EXISTS `log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT ,
    `lvl` TINYINT(1) NOT NULL ,
    `type` ENUM('emerg','alert','crit','err','warn','notice','info','debug') NOT NULL ,
    `msg` TEXT NOT NULL ,
    `info` TEXT NULL ,
    `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
PRIMARY KEY (`id`)
) ENGINE = InnoDB
";

// -----------------------------------------------------
// Table `session`
// -----------------------------------------------------
$sql[] = "
DROP TABLE IF EXISTS `session`
";

$sql[] = "
CREATE  TABLE IF NOT EXISTS `session` (
    `id` CHAR(32) NOT NULL ,
    `modified` INT(11) NULL ,
    `lifetime` INT(11) NULL ,
    `data` TEXT NULL ,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
";

// -----------------------------------------------------
// Table `edition`
// -----------------------------------------------------
$sql[] = "
DROP TABLE IF EXISTS `edition`
";

$sql[] = "
CREATE  TABLE IF NOT EXISTS `edition` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `key` VARCHAR(5) NULL ,
    `name` VARCHAR(45) NULL ,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
";

// -----------------------------------------------------
// Table `plan`
// -----------------------------------------------------
$sql[] = "
DROP TABLE IF EXISTS `plan`
";

$sql[] = "
CREATE  TABLE IF NOT EXISTS `plan` (
    `id` INT(11) UNSIGNED NOT NULL ,
    `name` VARCHAR(45) NOT NULL ,
    `instances` INT(3) UNSIGNED NOT NULL DEFAULT 0 ,
    `price` DECIMAL(5,2) UNSIGNED NULL ,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
";

// -----------------------------------------------------
// Table `payment`
// -----------------------------------------------------
$sql[] = "
DROP TABLE IF EXISTS `payment`
";

$sql[] = "
CREATE  TABLE IF NOT EXISTS `payment` (
    `id` INT(11) UNSIGNED NOT NULL ,
    `price` DECIMAL(5,2) UNSIGNED NOT NULL ,
    `first_name` VARCHAR(50) NOT NULL ,
    `last_name` VARCHAR(50) NOT NULL ,
    `street` VARCHAR(50) NOT NULL ,
    `postal_code` VARCHAR(10) NOT NULL ,
    `city` VARCHAR(50) NOT NULL ,
    `state` VARCHAR(50) NOT NULL ,
    `country` VARCHAR(50) NOT NULL ,
    `date` TIMESTAMP NOT NULL ,
    `plan_id` INT(2) UNSIGNED NOT NULL ,
    `user_id` INT(11) NOT NULL ,
    `subscr_id` VARCHAR(19) NOT NULL ,
    PRIMARY KEY (`id`) ,
    INDEX `user_has_payments` (`user_id` ASC) ,
    INDEX `plan_has_payments` (`user_id` ASC) ,
    INDEX `plan_has_payments` (`plan_id` ASC) ,
    CONSTRAINT `user_has_payments`
        FOREIGN KEY (`user_id` )
        REFERENCES `user` (`id` )
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `plan_has_payments`
        FOREIGN KEY (`plan_id` )
        REFERENCES `plan` (`id` )
        ON DELETE CASCADE
        ON UPDATE NO ACTION
) ENGINE = InnoDB
";

// -----------------------------------------------------
// Table `coupon`
// -----------------------------------------------------
$sql[] = "
DROP TABLE IF EXISTS `coupon`
";

$sql[] = "
CREATE  TABLE IF NOT EXISTS `coupon` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `code` VARCHAR(45) NULL ,
    `used_date` DATETIME NULL ,
    `user_id` INT NULL ,
    `plan_id` INT NULL ,
    `duration` VARCHAR(20) NULL ,
    `active_to` DATETIME NULL ,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
";

// ----------- //
// SAMPLE DATA //
// ----------- //

$sql[] = "
INSERT INTO `edition` VALUES  (1,'CE','Community'),
(2,'PE','Professional'),
(3,'EE','Enterprise')
";

$sql[] = "
INSERT INTO `user` VALUES
(1,'admin','d033e22ae348aeb5660fc2140aec35850c4da997','jan@rocketweb.com','John','Admin', 'street', 'postal code', 'city', 'state','country','2012-01-01 00:00:00','active','admin',0,'',0),
(2,'commercial-user','616821f7a69735aacee22f88f870d00062c0f2d2','jan@rocketweb.com','John','Commercial', 'street', 'postal code', 'city', 'state','country','2012-01-01 00:00:00','active','commercial-user',0,'',0),
(3,'standard-user','d285033046d5df2851143596830bca4811bf3af8','jan@rocketweb.com','John','Standard', 'street', 'postal code', 'city', 'state','country','2012-01-01 00:00:00','active','free-user',0,'',0)
";

$sql[] = "
INSERT INTO `version` VALUES
(1,'CE','1.4.2.0','1.2.0'),
(2,'CE','1.5.0.1','1.2.0'),
(3,'CE','1.5.1.0','1.2.0'),
(4,'CE','1.6.0.0','1.2.0'),
(5,'CE','1.6.1.0','1.6.1.0'),
(6,'CE','1.6.2.0','1.6.1.0'),
(7,'CE','1.7.0.0-alpha1','1.6.1.0'),
(8,'PE','1.10.1.0','1.6.1.0'),
(9,'EE','1.10.1.1','1.3.1')
";

$sql[] = "
INSERT INTO `plan` VALUES
(1, 'Standard', 23, 5.00 ),
(2, 'Better Standard', 56, 10.00 )
";

$sql[] = "
INSERT INTO `payment` VALUES
(1, 23.50, 'John', 'Owner', 'LongStreet', '50123', 'Los Angeles', 'California', 'USA', '2012-03-04 12:13:15', 1, 3, 'adqdqwdqdwq'),
(2, 55.00, 'Michael', 'Newbie', 'NearLongStreet', '50132', 'Los Angeles', 'California', 'USA', '2012-03-13 22:23:24', 2, 3, 'sadasdwqdwq')
";

$sql[] = "
INSERT INTO `coupon` VALUES
(1,'coupon1',NULL,NULL,2,'+365 days','2013-01-01'),
(2,'coupon2',NULL,NULL,2,'+365 days','2013-01-01'),
(3,'coupon3',NULL,NULL,2,'+365 days','2013-01-01'),
(4,'coupon4',NULL,NULL,2,'+365 days','2013-01-01'),
(5,'coupon5',NULL,NULL,2,'+365 days','2013-01-01'),
(6,'coupon6',NULL,NULL,2,'+365 days','2013-01-01'),
(7,'coupon7',NULL,NULL,2,'+365 days','2013-01-01'),
(8,'coupon8',NULL,NULL,2,'+365 days','2013-01-01'),
(9,'coupon9',NULL,NULL,2,'+365 days','2013-01-01')
";