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

$sql[] = '
CREATE TABLE IF NOT EXISTS `payment_additional_store` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `braintree_transaction_id` varchar(10) NOT NULL,
  `braintree_transaction_confirmed` int(1) NOT NULL DEFAULT \'0\',
  `purchased_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stores` int(3) unsigned NOT NULL,
  `downgraded` int(1) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_purchased_user_stores` FOREIGN KEY 
    (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8
';

$sql[] = '
ALTER TABLE  `payment` CHANGE  `transaction_type`  `transaction_type` ENUM(  \'subscription\',  \'extension\',  \'additional-stores\' ) NOT NULL DEFAULT  \'subscription\'
';
