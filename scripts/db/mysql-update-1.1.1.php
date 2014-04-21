<?php

$sql[] = '
ALTER TABLE `payment`
  DROP FOREIGN KEY `user_has_payments`;
';

$sql[] = '
ALTER TABLE `payment`
  ADD CONSTRAINT `user_has_payments` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
';
