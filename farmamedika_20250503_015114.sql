/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.10-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: farmamedika
-- ------------------------------------------------------
-- Server version	10.11.10-MariaDB-log

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
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`doctor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES
(1,'dr. maun leman','Umum','D-12345','081234567890','2025-04-17 17:33:59','2025-04-18 05:43:59'),
(2,'dr. kak dar','Spesialis Jantung','D-67890','082345678901','2025-04-17 17:33:59','2025-04-18 05:44:14'),
(3,'dr. pawang','Spesialis Anak','D-24680','083456789012','2025-04-17 17:33:59','2025-04-18 05:44:29');
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicine_types`
--

DROP TABLE IF EXISTS `medicine_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medicine_types` (
  `medicine_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`medicine_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicine_types`
--

LOCK TABLES `medicine_types` WRITE;
/*!40000 ALTER TABLE `medicine_types` DISABLE KEYS */;
INSERT INTO `medicine_types` VALUES
(1,'Tablet','Obat dalam bentuk tablet atau pil','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(2,'Kapsul','Obat dalam bentuk kapsul','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(3,'Sirup','Obat dalam bentuk cair/sirup','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(4,'Salep','Obat dalam bentuk salep untuk dioleskan','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(5,'Injeksi','Obat dalam bentuk suntikan','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(6,'Tetes','Obat dalam bentuk tetes','2025-04-17 17:33:58','2025-04-17 17:33:58');
/*!40000 ALTER TABLE `medicine_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `payment_method_id` int(11) NOT NULL AUTO_INCREMENT,
  `method_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_method_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES
(1,'Tunai','Pembayaran dengan uang tunai',1,'2025-04-17 17:33:58','2025-04-17 17:33:58'),
(2,'Kredit / Debit','Pembayaran menggunakan kartu kredit atau debit',1,'2025-04-17 17:33:58','2025-04-18 05:41:12'),
(3,'QRIS','Pembayaran menggunakan QRIS',1,'2025-04-17 17:33:58','2025-04-17 17:33:58');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_batches`
--

DROP TABLE IF EXISTS `product_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_batches` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `expiry_date` varchar(255) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `remaining_quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`batch_id`),
  KEY `product_id` (`product_id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `product_batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `product_batches_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_batches`
--

LOCK TABLES `product_batches` WRITE;
/*!40000 ALTER TABLE `product_batches` DISABLE KEYS */;
INSERT INTO `product_batches` VALUES
(8,8,'','15-08-2025',NULL,1,217,NULL,217,'2025-05-03 06:43:59','2025-05-03 07:43:06'),
(9,8,'','15-08-2025',NULL,1,217,NULL,217,'2025-05-03 06:56:36','2025-05-03 07:43:06'),
(15,14,'vtr54rr','15-01-2026',NULL,1,39,NULL,39,'2025-05-03 08:05:59','2025-05-03 08:07:48'),
(16,15,'','15-09-2027',NULL,1,4,NULL,4,'2025-05-03 08:12:21','2025-05-03 08:12:21'),
(17,16,'','15-05-2028',NULL,1,3,NULL,3,'2025-05-03 08:12:24','2025-05-03 08:12:24'),
(18,17,'','15-01-2027',NULL,1,38,NULL,38,'2025-05-03 08:12:27','2025-05-03 08:12:27'),
(19,18,'','15-09-2025',NULL,1,2,NULL,2,'2025-05-03 08:12:30','2025-05-03 08:12:30'),
(20,19,'','15-09-2028',NULL,1,1,NULL,1,'2025-05-03 08:12:33','2025-05-03 08:12:33'),
(21,20,'sdsd44','15-09-2025',NULL,1,4,NULL,4,'2025-05-03 08:12:37','2025-05-03 08:35:30'),
(22,21,'','15-01-2025',NULL,1,42,NULL,42,'2025-05-03 08:12:40','2025-05-03 08:12:40'),
(24,23,'','15-07-2026',NULL,1,5,NULL,5,'2025-05-03 08:31:11','2025-05-03 08:31:11'),
(25,24,'','15-05-2027',NULL,1,5,NULL,5,'2025-05-03 08:31:14','2025-05-03 08:31:14'),
(26,25,'','15-PCS',NULL,1,100,NULL,100,'2025-05-03 08:31:18','2025-05-03 08:31:18'),
(27,26,'','15-01-2029',NULL,1,5,NULL,5,'2025-05-03 08:31:21','2025-05-03 08:31:21'),
(28,27,'','15-01-2025',NULL,1,15,NULL,15,'2025-05-03 08:31:27','2025-05-03 08:31:27'),
(29,28,'','15-07-2025',NULL,1,28,NULL,28,'2025-05-03 08:31:30','2025-05-03 08:31:30'),
(30,29,'','15-09-2025',NULL,1,45,NULL,45,'2025-05-03 08:31:34','2025-05-03 08:31:34'),
(31,30,'','15-PCS',NULL,1,14,NULL,14,'2025-05-03 08:31:38','2025-05-03 08:31:38'),
(32,31,'','15-07-2025',NULL,1,2,NULL,2,'2025-05-03 08:31:41','2025-05-03 08:31:41'),
(33,32,'','15-09-2024',NULL,1,6,NULL,6,'2025-05-03 08:31:45','2025-05-03 08:31:45'),
(34,33,'','15-07-2024',NULL,1,3,NULL,3,'2025-05-03 08:31:49','2025-05-03 08:31:49'),
(35,34,'','15-07-2025',NULL,1,1,NULL,1,'2025-05-03 08:32:01','2025-05-03 08:32:01'),
(36,35,'','15-09-2026',NULL,1,33,NULL,33,'2025-05-03 08:32:05','2025-05-03 08:32:05'),
(37,36,'','15-07-2025',NULL,1,5,NULL,5,'2025-05-03 08:32:11','2025-05-03 08:32:11'),
(38,37,'','15-09-2026',NULL,1,4,NULL,4,'2025-05-03 08:32:15','2025-05-03 08:32:15'),
(39,38,'','15-07-2025',NULL,1,26,NULL,26,'2025-05-03 08:32:19','2025-05-03 08:32:19');
/*!40000 ALTER TABLE `product_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES
(1,'Obat Herbal','-','2025-04-28 21:53:54','2025-04-28 21:53:54'),
(3,'Alat Kesehatan','Alat Kesehatan','2025-04-28 21:54:30','2025-05-01 14:27:55'),
(4,'Obat Keras','-','2025-04-28 21:54:53','2025-04-28 21:54:53'),
(6,'Vitamin','','2025-05-02 11:56:11','2025-05-02 11:56:11');
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `kode_item` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `medicine_type_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `posisi` varchar(50) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `minimum_stock` int(11) DEFAULT 0,
  `dosage` varchar(50) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `requires_prescription` tinyint(1) DEFAULT 0,
  `side_effects` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  KEY `medicine_type_id` (`medicine_type_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`),
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`medicine_type_id`) REFERENCES `medicine_types` (`medicine_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(8,'Adem Sari 7gr','1006',1,NULL,3000.00,1800.00,'8997239630103','R11',217,10,NULL,'Sachet',0,NULL,NULL,1,'2025-05-03 06:43:59','2025-05-03 07:43:06'),
(14,'Flimty Raspberry 15gr [ECER]/Sachet','5966',1,NULL,17000.00,13000.00,'8997230500856','R11',39,3,NULL,'Botol',0,NULL,NULL,1,'2025-05-03 08:05:59','2025-05-03 08:07:00'),
(15,'Hypafix 5cm x 1m','5971',1,NULL,26000.00,21922.00,'4042809274554','R12',4,2,NULL,'',0,NULL,NULL,1,'2025-05-03 08:12:21','2025-05-03 08:12:21'),
(16,'Opsite Post OP 25cm x 10cm','5972',1,NULL,65000.00,52503.00,'5000223441395','R12',3,3,NULL,'',0,NULL,NULL,1,'2025-05-03 08:12:24','2025-05-03 08:12:24'),
(17,'Freshcare Smash Matcha 8ml','5973',1,NULL,15000.00,10471.00,'8997021873220','R07',38,1,NULL,'',0,NULL,NULL,1,'2025-05-03 08:12:27','2025-05-03 08:12:27'),
(18,'Pibaksin slp 10g','5974',1,NULL,60000.00,49006.00,'5974','RB05',2,1,NULL,'',0,NULL,NULL,1,'2025-05-03 08:12:30','2025-05-03 08:12:30'),
(19,'Durex Performa 3s','5975',1,NULL,38000.00,27992.00,'5038483193228','R12',1,6,NULL,'',0,NULL,NULL,1,'2025-05-03 08:12:33','2025-05-03 08:12:33'),
(20,'Actifed Pilek 60ml','1003',4,NULL,65000.00,54674.00,'8993478101039','R17',4,3,NULL,'Botol',0,NULL,NULL,1,'2025-05-03 08:12:37','2025-05-03 08:35:30'),
(21,'Actifed Plus Expectorant 60ml','1004',1,NULL,68000.00,55837.00,'8993478101077','R17',42,3,NULL,'',0,NULL,NULL,1,'2025-05-03 08:12:40','2025-05-03 08:12:40'),
(23,'Alco Plus 100ml','1009',1,NULL,85000.00,70075.00,'(90)DTL0317619237A1(91)250406','R17',5,2,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:11','2025-05-03 08:31:11'),
(24,'Alco Plus DMP 100ml','1012',1,NULL,75000.00,57241.00,'(90)DTL0317619337A1(91)250406','R17',5,2,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:14','2025-05-03 08:31:14'),
(25,'Alkindo Dukcbil ECERAN','1018',1,NULL,2000.00,600.00,'1018','R20',100,10,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:18','2025-05-03 08:31:18'),
(26,'Doodle Mny Telon Plus 60ml','1019',1,NULL,35000.00,25600.00,'8997220370049','R11',5,10,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:21','2025-05-03 08:31:21'),
(27,'Allerin 120ml','1020',1,NULL,27000.00,21437.00,'8992112039226','R18',15,3,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:27','2025-05-03 08:31:27'),
(28,'Allerin 60ml syr','1021',1,NULL,15000.00,11693.00,'8992112039219','R18',28,5,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:30','2025-05-03 08:31:30'),
(29,'Panadol [biru] 10kap','5631',1,NULL,13000.00,10123.00,'8992695100207','R01',45,3,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:34','2025-05-03 08:31:34'),
(30,'Korek Kuping Besi Per PCS','6158',1,NULL,6000.00,4000.00,'6158','R23',14,10,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:38','2025-05-03 08:31:38'),
(31,'Alerfed expct sy 60ml','1016',1,NULL,40000.00,32300.00,'1016','R16',2,1,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:41','2025-05-03 08:31:41'),
(32,'Alerfed sy 60ml','1017',1,NULL,37000.00,30300.00,'1017','R16',6,1,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:45','2025-05-03 08:31:45'),
(33,'Cussons BB pow mild&gentle 300g','6175',1,NULL,14000.00,11200.00,'8888103201218','R27',3,1,NULL,'',0,NULL,NULL,1,'2025-05-03 08:31:49','2025-05-03 08:31:49'),
(34,'Plosa saset 4ml panasin','6217',1,NULL,3000.00,2343.00,'8997239630486','R07',1,1,NULL,'',0,NULL,NULL,1,'2025-05-03 08:32:01','2025-05-03 08:32:01'),
(35,'Erythromycin Stearate 500mg [RAMA]','6218',1,NULL,18000.00,10000.00,'6218','RB01',33,10,NULL,'',0,NULL,NULL,1,'2025-05-03 08:32:05','2025-05-03 08:32:05'),
(36,'Norethisterone 5mg [PROMED]','6220',1,NULL,20000.00,13000.00,'6220','RB01',5,1,NULL,'',0,NULL,NULL,1,'2025-05-03 08:32:11','2025-05-03 08:32:11'),
(37,'Etoricoxib 90mg [NULAB]','6221',1,NULL,55000.00,45000.00,'6221','RB01',4,10,NULL,'',0,NULL,NULL,1,'2025-05-03 08:32:15','2025-05-03 08:32:15'),
(38,'Pacetic tab 500mg','6222',1,NULL,5000.00,2175.00,'6222','RB12',26,2,NULL,'',0,NULL,NULL,1,'2025-05-03 08:32:19','2025-05-03 08:32:19');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `purchase_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `item_total` decimal(10,2) NOT NULL,
  `received_quantity` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`purchase_item_id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`purchase_id`),
  CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_payments`
--

DROP TABLE IF EXISTS `purchase_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount_paid` decimal(15,2) NOT NULL,
  `proof_document_path` varchar(512) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `purchase_id` (`purchase_id`),
  CONSTRAINT `purchase_payments_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_payments`
--

LOCK TABLES `purchase_payments` WRITE;
/*!40000 ALTER TABLE `purchase_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `purchase_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','partially_paid') DEFAULT 'pending',
  `received_status` enum('pending','received','partially_received') DEFAULT 'pending',
  `user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`purchase_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_items` (
  `sale_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `item_total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sale_item_id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  KEY `batch_id` (`batch_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`),
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `sale_items_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(20) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `prescription_number` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `sale_date` timestamp NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method_id` int(11) DEFAULT NULL,
  `payment_status` enum('pending','paid','partially_paid') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sale_id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `user_id` (`user_id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`),
  CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES
(1,'PT Kimia Farma','Dek gemoy','082213811382',1,'2025-04-17 17:33:59','2025-05-01 18:50:23'),
(2,'PT Kalbe Farma','cutbul','0812121212121',1,'2025-04-17 17:33:59','2025-05-01 18:53:49'),
(3,'PT Sanbe Farmas','apa karya','022-1234-5678',1,'2025-04-17 17:33:59','2025-05-02 12:09:24'),
(5,'PT trtr','nunu','08221391324',1,'2025-05-03 06:06:14','2025-05-03 06:06:14');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `unit_id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES
(1,'PCS','2025-04-28 14:36:18'),
(2,'Botol','2025-04-28 14:58:22'),
(3,'Sachet','2025-04-28 15:06:40');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('admin','staff','cashier') DEFAULT 'cashier',
  `is_active` tinyint(1) DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin','$2a$12$iL/kH0WjSP3qbPSPom/aXevtUj0te.5fSARHYwBSmHUrHbTViqya6','Bang Put','admin',1,NULL,1,1,'2025-05-03 05:46:29','2025-04-28 21:50:57','2025-05-03 05:46:29'),
(3,'ed','$2y$10$F/RK3Pgjw9ziUIpUqfJwHurhrShKn7V8cYXeqZDqB9RNnM.MKTXsu','ed','cashier',1,NULL,1,NULL,'2025-05-02 20:40:12','2025-05-02 20:39:55','2025-05-02 20:40:12');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'farmamedika'
--

--
-- Dumping routines for database 'farmamedika'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-03  1:51:15
