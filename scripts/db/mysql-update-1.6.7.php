<?php
/* add foreign keys on instance_extension table */
$sql[]="ALTER TABLE `instance_extension` ADD CONSTRAINT `fk_store_extension_store`
  FOREIGN KEY (`instance_id` )
  REFERENCES `instance` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION
, ADD INDEX `fk_store_extension_store` (`instance_id` ASC) ;
";

$sql[]="ALTER TABLE `user_extension` ADD CONSTRAINT `fk_store_extension_user`
  FOREIGN KEY (`user_id` )
  REFERENCES `user` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION
, ADD INDEX `fk_store_extension_user` (`user_id` ASC) ;
";

/* remove extension remove task from possible enums */
$sql[] = "ALTER TABLE `queue` 
    CHANGE `task` `task` ENUM( 'ExtensionInstall', 'ExtensionOpensource', 'MagentoDownload', 'MagentoInstall', 'MagentoRemove', 'RevisionCommit', 'RevisionDeploy', 'RevisionRollback', 'RevisionInit' ) NULL DEFAULT NULL ";

