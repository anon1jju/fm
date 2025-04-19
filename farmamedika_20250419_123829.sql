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
  `expiry_date` date NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_batches`
--

LOCK TABLES `product_batches` WRITE;
/*!40000 ALTER TABLE `product_batches` DISABLE KEYS */;
INSERT INTO `product_batches` VALUES
(1,1,'BT2023-001','2025-12-31','2023-01-15',1,50,3000.00,50,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(2,1,'BT2023-002','2025-10-31','2023-03-10',1,50,3000.00,50,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(3,2,'BT2023-003','2025-08-31','2023-02-05',2,50,10000.00,50,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(4,3,'BT2023-004','2024-12-31','2023-01-20',3,30,18000.00,30,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(5,4,'BT2023-005','2025-06-30','2023-02-15',2,75,30000.00,75,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(6,5,'BT2023-006','2026-05-31','2023-03-01',1,40,14000.00,40,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(7,6,'BT2023-007','2024-09-30','2023-03-15',3,20,25000.00,20,'2025-04-17 17:33:59','2025-04-17 17:33:59');
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
(1,'Obat Bebas','Obat yang dapat dibeli tanpa resep dokter','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(2,'Obat Bebas Terbatas','Obat yang dapat dibeli tanpa resep dokter dengan peringatan','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(3,'Obat Keras','Obat yang hanya bisa didapat dengan resep dokter','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(4,'Vitamin & Suplemen','Vitamin dan suplemen kesehatan','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(5,'Alat Kesehatan','Peralatan medis dan kesehatan','2025-04-17 17:33:58','2025-04-17 17:33:58'),
(6,'Obat Herbal','Obat-obatan dari bahan herbal','2025-04-17 17:33:58','2025-04-17 17:33:58');
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
  `minimum_stock` int(11) DEFAULT 5,
  `dosage` varchar(50) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `requires_prescription` tinyint(1) DEFAULT 0,
  `side_effects` text DEFAULT NULL,
  `storage_instructions` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  KEY `medicine_type_id` (`medicine_type_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`),
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`medicine_type_id`) REFERENCES `medicine_types` (`medicine_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,'Paracetamol 500mg','Paracetamol',1,1,5000.00,3000.00,'3190050189','PCM500',65,20,'500mg','Strip',0,NULL,NULL,NULL,1,'2025-04-17 17:33:59','2025-04-18 06:47:01'),
(2,'Amoxicillin 500mg','Amoxicillin',3,2,15000.00,10000.00,'SPXID059017188724','AMX500',33,10,'500mg','Strip',1,NULL,NULL,NULL,1,'2025-04-17 17:33:59','2025-04-19 18:59:58'),
(3,'OBH Combi 100ml','Obat Batuk Hitam',2,3,25000.00,18000.00,'PRO003','OBH100',30,5,'3x1 sendok','Botol',0,NULL,NULL,NULL,1,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(4,'Vitamin C 1000mg','Ascorbic Acid',4,1,40000.00,30000.00,'PRO004','VITC1000',74,15,'1000mg','Tube',0,NULL,NULL,NULL,1,'2025-04-17 17:33:59','2025-04-18 05:36:38'),
(5,'Betadine 60ml','leman',5,4,20000.00,14000.00,'PRO005','BTD60',30,8,'Sesuai kebutuhan','Botol',0,NULL,NULL,NULL,1,'2025-04-17 17:33:59','2025-04-18 05:32:43'),
(6,'Infus NaCl 500ml','Sodium Chloride',3,5,35000.00,25000.00,'PRO006','INF500',16,10,'500ml','Botol',1,NULL,NULL,NULL,1,'2025-04-17 17:33:59','2025-04-19 18:59:58');
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
-- Table structure for table `sale_item_batches`
--

DROP TABLE IF EXISTS `sale_item_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_item_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  KEY `batch_id` (`batch_id`),
  CONSTRAINT `sale_item_batches_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`),
  CONSTRAINT `sale_item_batches_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `sale_item_batches_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_item_batches`
--

LOCK TABLES `sale_item_batches` WRITE;
/*!40000 ALTER TABLE `sale_item_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_item_batches` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES
(2,15,5,NULL,1,20000.00,0.00,20000.00,'2025-04-17 20:58:57','2025-04-17 20:58:57'),
(3,15,6,NULL,1,35000.00,0.00,35000.00,'2025-04-17 20:58:57','2025-04-17 20:58:57'),
(4,16,6,NULL,2,35000.00,0.00,70000.00,'2025-04-18 03:25:48','2025-04-18 03:25:48'),
(5,16,5,NULL,2,20000.00,0.00,40000.00,'2025-04-18 03:25:48','2025-04-18 03:25:48'),
(6,17,5,NULL,5,20000.00,0.00,100000.00,'2025-04-18 03:30:28','2025-04-18 03:30:28'),
(7,17,1,NULL,1,5000.00,0.00,5000.00,'2025-04-18 03:30:28','2025-04-18 03:30:28'),
(8,18,5,NULL,1,20000.00,0.00,20000.00,'2025-04-18 03:38:06','2025-04-18 03:38:06'),
(9,19,1,NULL,7,5000.00,0.00,35000.00,'2025-04-18 05:29:20','2025-04-18 05:29:20'),
(10,19,2,NULL,5,15000.00,0.00,75000.00,'2025-04-18 05:29:20','2025-04-18 05:29:20'),
(11,20,1,NULL,1,5000.00,0.00,5000.00,'2025-04-18 05:30:14','2025-04-18 05:30:14'),
(12,20,2,NULL,1,15000.00,0.00,15000.00,'2025-04-18 05:30:14','2025-04-18 05:30:14'),
(13,21,1,NULL,6,5000.00,0.00,30000.00,'2025-04-18 05:32:29','2025-04-18 05:32:29'),
(14,22,5,NULL,1,20000.00,0.00,20000.00,'2025-04-18 05:32:43','2025-04-18 05:32:43'),
(15,23,4,NULL,1,40000.00,0.00,40000.00,'2025-04-18 05:36:38','2025-04-18 05:36:38'),
(16,24,1,NULL,20,5000.00,0.00,100000.00,'2025-04-18 06:47:01','2025-04-18 06:47:01'),
(17,24,2,NULL,10,15000.00,0.00,150000.00,'2025-04-18 06:47:01','2025-04-18 06:47:01'),
(18,25,6,NULL,1,35000.00,0.00,35000.00,'2025-04-19 18:59:57','2025-04-19 18:59:57'),
(19,25,2,NULL,1,15000.00,0.00,15000.00,'2025-04-19 18:59:58','2025-04-19 18:59:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES
(15,'APT-20250418-2306',NULL,1,'yhbhyhb',1,'2025-04-17 20:58:57',55000.00,0.00,0.00,55000.00,1,'paid','','2025-04-17 20:58:57','2025-04-17 20:58:57'),
(16,'APT-20250418-3870',NULL,1,'vgg',1,'2025-04-18 03:25:48',110000.00,0.00,0.00,110000.00,1,'paid','','2025-04-18 03:25:48','2025-04-18 03:25:48'),
(17,'APT-20250418-1886',NULL,NULL,NULL,1,'2025-04-18 03:30:28',105000.00,0.00,5000.00,100000.00,2,'paid','','2025-04-18 03:30:28','2025-04-18 03:30:28'),
(18,'APT-20250418-7327',NULL,NULL,NULL,1,'2025-04-18 03:38:06',20000.00,0.00,0.00,20000.00,1,'paid','','2025-04-18 03:38:06','2025-04-18 03:38:06'),
(19,'APT-20250418-6893',NULL,1,'eced',1,'2025-04-18 05:29:20',110000.00,0.00,0.00,110000.00,1,'paid','','2025-04-18 05:29:20','2025-04-18 05:29:20'),
(20,'APT-20250418-6289',NULL,1,'bb',1,'2025-04-18 05:30:14',20000.00,0.00,2000.00,18000.00,1,'paid','','2025-04-18 05:30:14','2025-04-18 05:30:14'),
(21,'APT-20250418-6044',NULL,NULL,NULL,1,'2025-04-18 05:32:29',30000.00,0.00,0.00,30000.00,1,'paid','','2025-04-18 05:32:29','2025-04-18 05:32:29'),
(22,'APT-20250418-1554',NULL,NULL,NULL,1,'2025-04-18 05:32:43',20000.00,0.00,0.00,20000.00,1,'paid','','2025-04-18 05:32:43','2025-04-18 05:32:43'),
(23,'APT-20250418-6702',NULL,NULL,NULL,1,'2025-04-18 05:36:38',40000.00,0.00,20000.00,20000.00,3,'paid','','2025-04-18 05:36:38','2025-04-18 05:36:38'),
(24,'APT-20250418-1746',NULL,2,'maun',1,'2025-04-18 06:47:01',250000.00,0.00,5000.00,245000.00,1,'paid','','2025-04-18 06:47:01','2025-04-18 06:47:01'),
(25,'APT-20250420-7998',NULL,2,'vvf',1,'2025-04-19 18:59:57',50000.00,0.00,0.00,50000.00,1,'paid','','2025-04-19 18:59:57','2025-04-19 18:59:57');
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
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES
(1,'PT Kimia Farma','Budi Santoso','021-5555-1234','supplier@kimiafarma.co.id','Jl. Raya Bogor Km. 35, Jakarta',1,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(2,'PT Kalbe Farma','Dina Wijaya','021-5555-5678','supplier@kalbefarma.com','Jl. Jendral Sudirman Kav. 15, Jakarta',1,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(3,'PT Sanbe Farma','Tono Hadi','022-1234-5678','supplier@sanbe.co.id','Jl. Imam Bonjol No. 45, Bandung',1,'2025-04-17 17:33:59','2025-04-17 17:33:59');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
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
  `role` enum('admin','pharmacist','cashier') DEFAULT 'cashier',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin','$2y$10$XOPPOUChbAruEEwxhp8Mbe8bVRYkGSKLgJJ4wzLQqlLBqChhPvbm.','Administrator','admin',1,NULL,'2025-04-17 17:33:59','2025-04-17 17:33:59'),
(2,'apoteker','$2y$10$XOPPOUChbAruEEwxhp8Mbe8bVRYkGSKLgJJ4wzLQqlLBqChhPvbm.','Apoteker Utama','pharmacist',1,NULL,'2025-04-17 17:33:59','2025-04-17 17:33:59');
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

-- Dump completed on 2025-04-19 12:38:29
