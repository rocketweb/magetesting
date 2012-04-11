/*!40000 ALTER TABLE `edition` DISABLE KEYS */;
LOCK TABLES `edition` WRITE;
INSERT INTO `edition` VALUES  (1,'CE','Community'),
 (2,'PE','Professional'),
 (3,'EE','Enterprise');
UNLOCK TABLES;
/*!40000 ALTER TABLE `edition` ENABLE KEYS */;

/*!40000 ALTER TABLE `user` DISABLE KEYS */;
LOCK TABLES `user` WRITE;
INSERT INTO `user` VALUES  
(1,'admin','d033e22ae348aeb5660fc2140aec35850c4da997','jan@rocketweb.com','John','Admin', 'street', 'postal code', 'city', 'state','country','2012-01-01 00:00:00','active','admin',0,'',0),
 (2,'commercial-user','616821f7a69735aacee22f88f870d00062c0f2d2','jan@rocketweb.com','John','Commercial', 'street', 'postal code', 'city', 'state','country','2012-01-01 00:00:00','active','commercial-user',0,'',0),
 (3,'standard-user','d285033046d5df2851143596830bca4811bf3af8','jan@rocketweb.com','John','Standard', 'street', 'postal code', 'city', 'state','country','2012-01-01 00:00:00','active','free-user',0,'',0);
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


INSERT INTO `plan` VALUES
(1, 'Standard', 23, 5.00 ),
(2, 'Better Standard', 56, 10.00 );

INSERT INTO `payment` VALUES
(1, 23.50, 'John', 'Owner', 'LongStreet', '50123', 'Los Angeles', 'California', 'USA', '2012-03-04 12:13:15', 1, 3, 'adqdqwdqdwq'),
(2, 55.00, 'Michael', 'Newbie', 'NearLongStreet', '50132', 'Los Angeles', 'California', 'USA', '2012-03-13 22:23:24', 2, 3, 'sadasdwqdwq');


INSERT INTO `coupon` VALUES
(1,'coupon1',NULL,NULL,2,'+365 days','2013-01-01'),
(2,'coupon2',NULL,NULL,2,'+365 days','2013-01-01'),
(3,'coupon3',NULL,NULL,2,'+365 days','2013-01-01'),
(4,'coupon4',NULL,NULL,2,'+365 days','2013-01-01'),
(5,'coupon5',NULL,NULL,2,'+365 days','2013-01-01'),
(6,'coupon6',NULL,NULL,2,'+365 days','2013-01-01'),
(7,'coupon7',NULL,NULL,2,'+365 days','2013-01-01'),
(8,'coupon8',NULL,NULL,2,'+365 days','2013-01-01'),
(9,'coupon9',NULL,NULL,2,'+365 days','2013-01-01');
