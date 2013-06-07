<?php

$version = '1.0.0';


$schema = "
DROP TABLE IF EXISTS `coupon`;
CREATE TABLE IF NOT EXISTS `coupon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  `used_date` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `plan_id` int(11) unsigned NOT NULL,
  `duration` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `active_to` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `edition`;
CREATE TABLE IF NOT EXISTS `edition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` enum('CE','PE','EE','GO') COLLATE utf8_bin DEFAULT NULL,
  `name` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `extension`;
CREATE TABLE IF NOT EXISTS `extension` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `logo` varchar(150) DEFAULT NULL,
  `version` varchar(11) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `extension` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `extension_encoded` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `extension_key` varchar(255) DEFAULT NULL,
  `from_version` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `to_version` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `edition` varchar(5) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `is_dev` tinyint(4) NOT NULL DEFAULT '0',
  `price` decimal(5,2) unsigned NOT NULL DEFAULT '0.00',
  `sort` int(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `extension_release` (`extension_key`,`edition`,`version`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `extension_category`;
CREATE TABLE IF NOT EXISTS `extension_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_bin NOT NULL,
  `class` varchar(15) COLLATE utf8_bin NOT NULL,
  `logo` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `extension_screenshot`;
CREATE TABLE IF NOT EXISTS `extension_screenshot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `extension_id` int(11) NOT NULL,
  `image` varchar(500) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `log`;
CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lvl` tinyint(1) NOT NULL,
  `type` enum('emerg','alert','crit','err','warn','notice','info','debug') COLLATE utf8_bin NOT NULL,
  `msg` text COLLATE utf8_bin NOT NULL,
  `info` text COLLATE utf8_bin,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `payment`;
CREATE TABLE IF NOT EXISTS `payment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `price` decimal(5,2) NOT NULL,
  `first_name` varchar(50) COLLATE utf8_bin NOT NULL,
  `last_name` varchar(50) COLLATE utf8_bin NOT NULL,
  `street` varchar(50) COLLATE utf8_bin NOT NULL,
  `postal_code` varchar(10) COLLATE utf8_bin NOT NULL,
  `city` varchar(50) COLLATE utf8_bin NOT NULL,
  `state` varchar(50) COLLATE utf8_bin NOT NULL,
  `country` varchar(50) COLLATE utf8_bin NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `transaction_name` varchar(45) COLLATE utf8_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `braintree_transaction_id` varchar(10) COLLATE utf8_bin NOT NULL,
  `transaction_type` enum('subscription','extension') COLLATE utf8_bin NOT NULL DEFAULT 'subscription',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `plan`;
CREATE TABLE IF NOT EXISTS `plan` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_bin NOT NULL,
  `stores` int(3) unsigned NOT NULL DEFAULT '0',
  `price` decimal(5,2) unsigned DEFAULT NULL,
  `price_description` varchar(20) COLLATE utf8_bin NOT NULL DEFAULT '',
  `billing_period` varchar(20) COLLATE utf8_bin DEFAULT '7 days',
  `billing_description` varchar(20) COLLATE utf8_bin NOT NULL DEFAULT '',
  `ftp_access` tinyint(1) DEFAULT '0',
  `phpmyadmin_access` tinyint(1) DEFAULT '0',
  `can_add_custom_store` int(3) unsigned NOT NULL DEFAULT '0',
  `is_hidden` int(1) unsigned NOT NULL DEFAULT '0',
  `auto_renew` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `can_do_db_revert` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `queue`;
CREATE TABLE IF NOT EXISTS `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `status` enum('pending','processing','ready') COLLATE utf8_bin DEFAULT 'pending',
  `user_id` int(11) NOT NULL,
  `extension_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL COMMENT 'parent id from this table, telling us if we should wait until parent_id is finished',
  `server_id` int(11) unsigned NOT NULL,
  `task` enum('ExtensionInstall','ExtensionOpensource','MagentoDownload','MagentoInstall','MagentoRemove','RevisionCommit','RevisionDeploy','RevisionRollback','RevisionInit','PapertrailUserCreate','PapertrailUserRemove','PapertrailSystemCreate','PapertrailSystemRemove','MagentoHourlyrevert') COLLATE utf8_bin DEFAULT NULL,
  `task_params` text COLLATE utf8_bin COMMENT 'additional task parameters, e.g: commit comment',
  `retry_count` tinyint(1) unsigned DEFAULT '0',
  `added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `revision`;
CREATE TABLE IF NOT EXISTS `revision` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `extension_id` int(11) DEFAULT NULL,
  `type` varchar(30) COLLATE utf8_bin NOT NULL,
  `comment` text COLLATE utf8_bin NOT NULL,
  `hash` varchar(32) COLLATE utf8_bin NOT NULL,
  `filename` varchar(255) COLLATE utf8_bin NOT NULL,
  `db_before_revision` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `server`;
CREATE TABLE IF NOT EXISTS `server` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `domain` varchar(60) COLLATE utf8_bin DEFAULT NULL,
  `ip` varchar(15) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `session`;
CREATE TABLE IF NOT EXISTS `session` (
  `id` char(32) COLLATE utf8_bin NOT NULL,
  `modified` int(11) DEFAULT NULL,
  `lifetime` int(11) DEFAULT NULL,
  `data` text COLLATE utf8_bin,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `sql_updater`;
CREATE TABLE IF NOT EXISTS `sql_updater` (
  `version` varchar(5) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `store`;
CREATE TABLE IF NOT EXISTS `store` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `edition` enum('CE','PE','EE') COLLATE utf8_bin NOT NULL DEFAULT 'CE',
  `status` enum('ready','removing-magento','error','installing-extension','installing-magento','downloading-magento','committing-revision','deploying-revision','rolling-back-revision','creating-papertrail-user','creating-papertrail-system','removing-papertrail-user','removing-papertrail-system','hourly-reverting-magento') COLLATE utf8_bin DEFAULT 'ready',
  `version_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `server_id` int(11) unsigned NOT NULL,
  `domain` varchar(10) COLLATE utf8_bin NOT NULL,
  `store_name` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `description` varchar(300) COLLATE utf8_bin DEFAULT NULL,
  `backend_name` varchar(50) COLLATE utf8_bin DEFAULT 'admin',
  `backend_password` varchar(255) COLLATE utf8_bin NOT NULL,
  `type` varchar(45) COLLATE utf8_bin DEFAULT 'clean',
  `custom_protocol` varchar(45) COLLATE utf8_bin DEFAULT 'clean',
  `custom_host` varchar(65) COLLATE utf8_bin DEFAULT NULL,
  `custom_port` varchar(45) COLLATE utf8_bin DEFAULT '',
  `custom_remote_path` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `custom_file` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `sample_data` int(1) unsigned NOT NULL DEFAULT '0',
  `custom_login` varchar(60) COLLATE utf8_bin DEFAULT NULL,
  `custom_pass` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `custom_sql` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `error_message` varchar(255) COLLATE utf8_bin DEFAULT NULL COMMENT 'error message shown on instance grid when error occurs',
  `revision_count` int(11) DEFAULT '1',
  `papertrail_syslog_hostname` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `papertrail_syslog_port` int(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `do_hourly_db_revert` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `store_extension`;
CREATE TABLE IF NOT EXISTS `store_extension` (
  `extension_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `braintree_transaction_id` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `braintree_transaction_confirmed` int(1) DEFAULT '0',
  `reminder_sent` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `status` enum('pending','processing','ready') COLLATE utf8_bin DEFAULT 'pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `store_log`;
CREATE TABLE IF NOT EXISTS `store_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lvl` tinyint(1) NOT NULL,
  `type` enum('emerg','alert','crit','err','warn','notice','info','debug') COLLATE utf8_bin NOT NULL,
  `msg` text COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `password` char(40) COLLATE utf8_bin NOT NULL,
  `email` varchar(50) COLLATE utf8_bin NOT NULL,
  `firstname` varchar(50) COLLATE utf8_bin NOT NULL,
  `lastname` varchar(50) COLLATE utf8_bin NOT NULL,
  `street` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `postal_code` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `city` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `state` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `country` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `status` enum('active','inactive','deleted') COLLATE utf8_bin NOT NULL DEFAULT 'inactive',
  `added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `group` enum('admin','free-user','awaiting-user','commercial-user') COLLATE utf8_bin NOT NULL DEFAULT 'free-user',
  `has_system_account` tinyint(1) NOT NULL DEFAULT '0',
  `system_account_name` varchar(55) COLLATE utf8_bin DEFAULT NULL,
  `plan_id` int(11) DEFAULT '0',
  `braintree_transaction_confirmed` int(1) DEFAULT '0',
  `plan_active_to` datetime DEFAULT NULL,
  `downgraded` tinyint(1) NOT NULL DEFAULT '0',
  `braintree_vault_id` int(11) DEFAULT NULL,
  `braintree_transaction_id` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `server_id` int(11) unsigned DEFAULT NULL,
  `has_papertrail_account` tinyint(1) unsigned DEFAULT '0',
  `papertrail_api_token` varchar(30) COLLATE utf8_bin DEFAULT NULL,
  `plan_raised_to_date` timestamp NULL DEFAULT NULL,
  `plan_id_before_raising` int(1) DEFAULT NULL,
  `preselected_plan_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_UNIQUE` (`login`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

DROP TABLE IF EXISTS `version`;
CREATE TABLE IF NOT EXISTS `version` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `edition` enum('CE','PE','EE') COLLATE utf8_bin NOT NULL DEFAULT 'CE',
  `version` varchar(15) COLLATE utf8_bin NOT NULL,
  `sample_data_version` varchar(10) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

ALTER TABLE `coupon`
  ADD CONSTRAINT `fk_coupon_plan` FOREIGN KEY (`plan_id`) REFERENCES `plan` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `extension` 
ADD CONSTRAINT `fk_extension_category`
    FOREIGN KEY (`category_id` )
    REFERENCES `extension_category` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
ADD INDEX `fk_extension_category` (`category_id` ASC);

ALTER TABLE `extension_screenshot`
  ADD CONSTRAINT `fk_screenshot_extension` FOREIGN KEY (`extension_id`) REFERENCES `extension` (`id`) ON DELETE CASCADE;

ALTER TABLE `payment`
  ADD CONSTRAINT `user_has_payments` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `queue`
  ADD CONSTRAINT `fk_queue_server1` FOREIGN KEY (`server_id`) REFERENCES `server` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_queue_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `revision`
  ADD CONSTRAINT `fk_revision_extension` FOREIGN KEY (`extension_id`) REFERENCES `extension` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_revision_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_revision_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `store`
  ADD CONSTRAINT `fk_store_server` FOREIGN KEY (`server_id`) REFERENCES `server` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_store_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_store_version` FOREIGN KEY (`version_id`) REFERENCES `version` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `store_extension`
  ADD CONSTRAINT `fk_store_extension_extension` FOREIGN KEY (`extension_id`) REFERENCES `extension` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_store_extension_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `store_log`
  ADD CONSTRAINT `fk_store_log_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
";


if (!function_exists('_remove_empty')) {
    function _remove_empty($element) {
        return strlen(trim((string)$element));
    }
}
$sql = array_filter(explode(';', $schema), '_remove_empty');