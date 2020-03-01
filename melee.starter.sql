-- MySQL dump 10.17  Distrib 10.3.18-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: yourdatabasenamehere
-- ------------------------------------------------------
-- Server version	10.3.18-MariaDB-0+deb10u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `members`
--
DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `members` (
  `uniq` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(200) DEFAULT '',
  `passwd` varchar(255) NOT NULL DEFAULT '87654321ABCDEF',
  `level` int(5) NOT NULL DEFAULT 0,
  `name` varchar(80) NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT '1971-01-01 00:00:00',
  `lastmod` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `publickey` text DEFAULT NULL,
  `fingerprint` varchar(100) default '',
  `status` varchar(10) DEFAULT 'active',
  `digest` int(1) DEFAULT 0,
  `asshole` int(1) DEFAULT 0,
  `recv` int(10) DEFAULT 0,
  `sent` int(10) DEFAULT 0,
  `bounced` int(10) DEFAULT 0,
  `hush` int(1) DEFAULT 0,
  `sign` int(1) DEFAULT 0,
  `encrypt` int(1) DEFAULT 0,
  `plain` int(1) DEFAULT 0,
  PRIMARY KEY (`uniq`),
  UNIQUE KEY `email` (`email`),
  KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
-- Dump completed on 2020-02-08 14:15:32
