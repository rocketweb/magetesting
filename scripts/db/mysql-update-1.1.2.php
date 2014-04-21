<?php

$sql[] = '
ALTER TABLE `payment`
  CHANGE  `user_id`  `user_id` int(11) NULL,
  DROP FOREIGN KEY `user_has_payments`;
';

$sql[] = '
ALTER TABLE `payment`
  ADD CONSTRAINT `user_has_payments` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;
';
