


DROP TABLE IF EXISTS `barcode`;
CREATE TABLE `barcode` (
  `barcode_ean` char(13) NOT NULL,
  `item_id` int NOT NULL,
  PRIMARY KEY (`barcode_ean`),
  KEY `item_id` (`item_id`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `barcode` (`barcode_ean`,`item_id`) VALUES
 ('6241527836173',1),
 ('6241574635234',2),
 ('6241527746363',3),
 ('6264537836173',3),
 ('7465743843764',4),
 ('3453458677628',5),
 ('6434564564544',6),
 ('8476736836876',7),
 ('6241234586487',8),
 ('9473625532534',8),
 ('9473627464543',8),
 ('4587263646878',9),
 ('2239872376872',11),
 ('9879879837489',11);

DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  `customer_id` int NOT NULL AUTO_INCREMENT,
  `title` char(4) DEFAULT NULL,
  `fname` varchar(32) DEFAULT NULL,
  `lname` varchar(32) NOT NULL,
  `addressline` varchar(64) DEFAULT NULL,
  `town` varchar(32) DEFAULT NULL,
  `zipcode` char(10) NOT NULL,
  `phone` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

INSERT INTO `customer` (`customer_id`,`title`,`fname`,`lname`,`addressline`,`town`,`zipcode`,`phone`) VALUES
 (1,'Miss','Jenny','Stones','27 Rowan Avenue','Hightown','NT2 1AQ','023 9876'),
 (2,'Mr','Andrew','Stones','52 The Willows','Lowtown','LT5 7RA','876 3527'),
 (3,'Miss','Alex','Matthew','4 The Street','Nicetown','NT2 2TX','010 4567'),
 (4,'Mr','Adrian','Matthew','The Barn','Yuleville','YV67 2WR','487 3871'),
 (5,'Mr','Simon','Cozens','7 Shady Lane','Oahenham','OA3 6QW','514 5926'),
 (6,'Mr','Neil','Matthew','5 Pasture Lane','Nicetown','NT3 7RT','267 1232'),
 (7,'Mr','Richard','Stones','34 Holly Way','Bingham','BG4 2WE','342 5982'),
 (8,'Mrs','Anna','Stones','34 Holly Way','Bingham','BG4 2WE','342 5982'),
 (9,'Mrs','Christine','Hickman','36 Queen Street','Histon','HT3 5EM','342 5432'),
 (10,'Mr','Mike','Howard','86 Dysart Street','Tibsville','TB3 7FG','505 5482'),
 (11,'Mr','Dave','Jones','54 Vale Rise','Bingham','BG3 8GD','342 8264'),
 (12,'Mr','Richard','Neill','42 Thached way','Winersby','WB3 6GQ','505 6482'),
 (13,'Mrs','Laura','Hendy','73 Margeritta Way','Oxbridge','OX2 3HX','821 2335'),
 (14,'Mr','Bill','Neill','2 Beamer Street','Welltown','WT3 8GM','435 1234'),
 (15,'Mr','David','Hudson','4  The Square','Milltown','MT2 6RT','961 4526'),
 (16,NULL,NULL,'Wolski',NULL,NULL,'MX2 6RV',NULL);


DROP TABLE IF EXISTS `item`;
CREATE TABLE `item` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(64) NOT NULL,
  `cost_price` decimal(7,2) DEFAULT NULL,
  `sell_price` decimal(7,2) DEFAULT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;

INSERT INTO `item` (`item_id`,`description`,`cost_price`,`sell_price`) VALUES
 (1,'Wood Puzzle','15.23','21.95'),
 (2,'Rubik Cube','7.45','11.49'),
 (3,'Linux CD','1.99','2.49'),
 (4,'Tissues','2.11','3.99'),
 (5,'Picture Frame','7.54','9.95'),
 (6,'Fan Small','9.23','15.75'),
 (7,'Fan Large','13.36','19.95'),
 (8,'Toothbrush','0.75','1.45'),
 (9,'Roman Coin','2.34','2.45'),
 (10,'Carrier Bag','0.01','0.00'),
 (11,'Speakers','19.73','25.32'),
 (12,'SQL Server 2005',NULL,NULL);

DROP TABLE IF EXISTS `orderinfo`;
CREATE TABLE `orderinfo` (
  `orderinfo_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `date_placed` date NOT NULL,
  `date_shipped` date DEFAULT NULL,
  `shipping` decimal(7,2) DEFAULT NULL,
  PRIMARY KEY (`orderinfo_id`),
  KEY `customer_id` (`customer_id`)
  
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

INSERT INTO `orderinfo` (`orderinfo_id`,`customer_id`,`date_placed`,`date_shipped`,`shipping`) VALUES
 (1,3,'2000-03-13','2000-03-17','2.99'),
 (2,8,'2000-06-23','2000-06-23','0.00'),
 (3,15,'2000-09-02','2000-09-12','3.99'),
 (4,13,'2000-09-03','2000-09-10','2.99'),
 (5,13,'2000-09-13','2000-09-14','2.99'),
 (6,8,'2000-07-21','2000-07-24','0.00'),
 (7,5,'2000-06-03','2000-08-05','3.99');

DROP TABLE IF EXISTS `orderline`;
CREATE TABLE `orderline` (
  `orderinfo_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  PRIMARY KEY (`orderinfo_id`,`item_id`),
  KEY `item_id` (`item_id`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `orderline` (`orderinfo_id`,`item_id`,`quantity`) VALUES
 (1,4,1),
 (1,7,1),
 (1,9,1),
 (2,1,1),
 (2,4,2),
 (2,7,2),
 (2,10,1),
 (3,1,1),
 (3,2,1),
 (4,5,2),
 (5,1,1),
 (5,3,1),
 (6,4,1),
 (6,2,1),
 (6,9,1),
 (7,7,1);


DROP TABLE IF EXISTS `stock`;
CREATE TABLE `stock` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `quantity` int NOT NULL,
  PRIMARY KEY (`item_id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

INSERT INTO `stock` (`item_id`,`quantity`) VALUES
 (1,12),
 (2,2),
 (4,8),
 (5,3),
 (7,8),
 (8,18),
 (10,1);
