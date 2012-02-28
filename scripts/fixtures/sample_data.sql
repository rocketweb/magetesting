/*!40000 ALTER TABLE `edition` DISABLE KEYS */;
LOCK TABLES `edition` WRITE;
INSERT INTO `edition` VALUES  (1,'CE','Community'),
 (2,'PE','Professional'),
 (3,'EE','Enterprise');
UNLOCK TABLES;
/*!40000 ALTER TABLE `edition` ENABLE KEYS */;

/*!40000 ALTER TABLE `user` DISABLE KEYS */;
LOCK TABLES `user` WRITE;
INSERT INTO `user` VALUES  (1,'admin','d033e22ae348aeb5660fc2140aec35850c4da997','jan@rocketweb.com','John','Admin','2012-01-01 00:00:00','active','admin'),
 (2,'commercial-user','616821f7a69735aacee22f88f870d00062c0f2d2','jan@rocketweb.com','John','Commercial','2012-01-01 00:00:00','active','commercial-user'),
 (3,'standard-user','d285033046d5df2851143596830bca4811bf3af8','jan@rocketweb.com','John','Standard','2012-01-01 00:00:00','active','standard-user');
UNLOCK TABLES;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;

/*!40000 ALTER TABLE `version` DISABLE KEYS */;
LOCK TABLES `version` WRITE;
INSERT INTO `version` VALUES  
(1,'CE','1.4.2.0','1.2.0'),
(2,'CE','1.5.0.1','1.2.0'),
(3,'CE','1.5.1.0','1.2.0'),
(4,'CE','1.6.0.0','1.2.0'),
(5,'CE','1.6.1.0','1.6.1.0'),
(6,'CE','1.6.2.0','1.6.1.0'),
(7,'CE','1.7.0.0-alpha1','1.6.1.0'),
(8,'PE','1.10.1.0','1.6.1.0'),
(9,'EE','1.10.1.1','1.3.1');

UNLOCK TABLES;
/*!40000 ALTER TABLE `version` ENABLE KEYS */;
