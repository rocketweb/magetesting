/*!40000 ALTER TABLE `edition` DISABLE KEYS */;
LOCK TABLES `edition` WRITE;
INSERT INTO `magentointegration`.`edition` VALUES  (1,'CE','Community'),
 (2,'PE','Professional'),
 (3,'EE','Enterprise');
UNLOCK TABLES;
/*!40000 ALTER TABLE `edition` ENABLE KEYS */;

/*!40000 ALTER TABLE `user` DISABLE KEYS */;
LOCK TABLES `user` WRITE;
INSERT INTO `magentointegration`.`user` VALUES  (1,'admin','d033e22ae348aeb5660fc2140aec35850c4da997','jan@rocketweb.com','John','Admin','2012-01-01 00:00:00','active','admin'),
 (2,'commercial-user','616821f7a69735aacee22f88f870d00062c0f2d2','jan@rocketweb.com','John','Commercial','2012-01-01 00:00:00','active','commercial-user'),
 (3,'standard-user','d285033046d5df2851143596830bca4811bf3af8','jan@rocketweb.com','John','Standard','2012-01-01 00:00:00','active','standard-user');
UNLOCK TABLES;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;

/*!40000 ALTER TABLE `version` DISABLE KEYS */;
LOCK TABLES `version` WRITE;
INSERT INTO `magentointegration`.`version` VALUES  (1,'CE','1.6.0.0'),
 (2,'CE','1.6.1.0');
UNLOCK TABLES;
/*!40000 ALTER TABLE `version` ENABLE KEYS */;
