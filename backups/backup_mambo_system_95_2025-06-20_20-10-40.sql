-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mambo_system_95
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `ajustes_estoque`
--

DROP TABLE IF EXISTS `ajustes_estoque`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ajustes_estoque` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) DEFAULT NULL,
  `quantidade_ajustada` int(11) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `ajustado_por` int(11) DEFAULT NULL,
  `data_ajuste` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  KEY `ajustado_por` (`ajustado_por`),
  CONSTRAINT `ajustes_estoque_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  CONSTRAINT `ajustes_estoque_ibfk_2` FOREIGN KEY (`ajustado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ajustes_estoque`
--

LOCK TABLES `ajustes_estoque` WRITE;
/*!40000 ALTER TABLE `ajustes_estoque` DISABLE KEYS */;
INSERT INTO `ajustes_estoque` VALUES (2,128,100,'Nova entrada',12,'2025-06-09 09:48:12'),(3,430,100,'Nova entrada',12,'2025-06-09 10:00:29'),(4,129,200,'Nova entrada',12,'2025-06-09 10:01:26'),(5,129,-200,'Nova entrada',12,'2025-06-09 10:02:02'),(6,430,-50,'Nova entrada',12,'2025-06-12 06:44:36'),(7,430,100,'Nova entrada',12,'2025-06-12 06:44:51'),(8,430,-50,'Nova entrada',12,'2025-06-12 06:44:57'),(9,146,1,'alteracao de preco',12,'2025-06-15 14:24:43'),(10,131,1,'alteracao de preco',12,'2025-06-15 14:25:16'),(11,130,1,'alteracao de preco',12,'2025-06-15 14:29:18'),(12,144,1,'alteracao de preco',12,'2025-06-15 14:30:20'),(13,133,1,'alteracao de preco',12,'2025-06-15 14:31:01'),(14,137,1,'alteracao de preco',12,'2025-06-15 14:31:37'),(15,141,1,'alteracao de preco',12,'2025-06-15 14:38:55'),(16,136,1,'alteracao de preco',12,'2025-06-15 14:39:24'),(17,139,1,'alteracao de preco',12,'2025-06-15 14:39:56'),(18,345,1,'alteracao de preco',12,'2025-06-15 14:45:11'),(19,342,1,'alteracao de preco',12,'2025-06-15 14:47:57'),(20,324,1,'alteracao de preco',12,'2025-06-15 14:58:55'),(21,238,1,'alteracao de preco',12,'2025-06-15 14:59:27'),(22,192,1,'alteracao de preco',12,'2025-06-15 15:04:41'),(23,352,1,'1',12,'2025-06-15 15:05:44'),(24,340,1,'alteracao de preco',12,'2025-06-15 15:10:21'),(25,221,2,'alteracao de preco',12,'2025-06-15 15:22:34'),(26,182,1,'alteracao de preco',12,'2025-06-15 15:29:24'),(27,183,1,'alteracao de preco',12,'2025-06-15 15:48:46'),(28,371,1,'alteracao de preco',12,'2025-06-15 15:56:04'),(29,421,1,'alteracao de preco',12,'2025-06-15 15:56:19'),(30,203,1,'alteracao de preco',12,'2025-06-15 16:12:19'),(31,380,1,'alteracao de preco',12,'2025-06-15 16:16:04'),(32,360,1,'alteracao de preco',12,'2025-06-15 20:02:17'),(33,267,1,'alteracao de preco',19,'2025-06-16 07:36:27'),(34,239,1,'AJUSTE DO PRECO',12,'2025-06-16 16:12:58'),(35,228,1,'AJUSTE DO PRECO',12,'2025-06-16 16:13:20'),(36,219,1,'AJUSTE DO PRECO',12,'2025-06-16 16:13:39'),(37,244,1,'AJUSTE DO PRECO',12,'2025-06-16 16:14:56'),(38,145,1,'AJUSTE DO PRECO',12,'2025-06-16 16:15:17'),(39,376,1,'AJUSTE DO PRECO',12,'2025-06-16 16:15:37'),(40,190,1,'AJUSTE DO PRECO',12,'2025-06-16 16:21:05');
/*!40000 ALTER TABLE `ajustes_estoque` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carrinho_temp`
--

DROP TABLE IF EXISTS `carrinho_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carrinho_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`,`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carrinho_temp`
--

LOCK TABLES `carrinho_temp` WRITE;
/*!40000 ALTER TABLE `carrinho_temp` DISABLE KEYS */;
/*!40000 ALTER TABLE `carrinho_temp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Bebidas'),(2,'Refrescos'),(3,'Limpeza'),(4,'Produtos da Mercearia');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `telefone` (`telefone`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracoes`
--

DROP TABLE IF EXISTS `configuracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) DEFAULT NULL,
  `nome_admin` varchar(100) DEFAULT NULL,
  `email_admin` varchar(100) DEFAULT NULL,
  `telefone_suporte` varchar(50) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `horario_atendimento` varchar(100) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `ssl_ativado` tinyint(1) DEFAULT 0,
  `limite_conexoes` int(11) DEFAULT 100,
  `tempo_expiracao` int(11) DEFAULT 30,
  `modo_exibicao` varchar(50) DEFAULT 'padrão',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracoes`
--

LOCK TABLES `configuracoes` WRITE;
/*!40000 ALTER TABLE `configuracoes` DISABLE KEYS */;
INSERT INTO `configuracoes` VALUES (1,'Administrador','Edson Mambo','edsonmambo@epcompany.co.mz','+258 84 854 1787','Bairro Aeroporto A, Rua da Patria, Q10, Casa 531','08:00 às 22:00','https://www.epcompany.inc.co.mz',1,100,30,'normal');
/*!40000 ALTER TABLE `configuracoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fechos`
--

DROP TABLE IF EXISTS `fechos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fechos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `data_fecho` datetime DEFAULT current_timestamp(),
  `total_vendas` int(11) DEFAULT 0,
  `total_valor` decimal(10,2) DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fechos`
--

LOCK TABLES `fechos` WRITE;
/*!40000 ALTER TABLE `fechos` DISABLE KEYS */;
INSERT INTO `fechos` VALUES (1,12,'2025-06-19 00:24:58',0,0.00,NULL),(2,12,'2025-06-19 00:30:04',0,NULL,NULL),(3,12,'2025-06-19 00:30:42',0,NULL,NULL),(4,12,'2025-06-19 00:39:40',2,3160.00,NULL);
/*!40000 ALTER TABLE `fechos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fechos_dia`
--

DROP TABLE IF EXISTS `fechos_dia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fechos_dia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_fecho` datetime DEFAULT current_timestamp(),
  `total_vendas` decimal(10,2) DEFAULT NULL,
  `total_transacoes` int(11) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fechos_dia_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fechos_dia`
--

LOCK TABLES `fechos_dia` WRITE;
/*!40000 ALTER TABLE `fechos_dia` DISABLE KEYS */;
INSERT INTO `fechos_dia` VALUES (1,12,NULL,'2025-06-12 23:23:24',3760.00,7,'Fecho automático do dia'),(2,12,NULL,'2025-06-12 23:40:59',3760.00,7,'Fecho automático do dia'),(3,12,NULL,'2025-06-12 23:41:32',3760.00,7,'Fecho automático do dia'),(4,12,NULL,'2025-06-12 23:42:58',3760.00,7,'Fecho automático do dia'),(5,12,NULL,'2025-06-12 23:46:08',3760.00,7,'Fecho automático do dia'),(6,12,NULL,'2025-06-14 09:28:15',0.00,0,'Fecho automático do dia'),(7,12,NULL,'2025-06-14 18:31:43',0.00,1,'Fecho automático do dia');
/*!40000 ALTER TABLE `fechos_dia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itens_vale`
--

DROP TABLE IF EXISTS `itens_vale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `itens_vale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vale_id` int(11) DEFAULT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT NULL,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vale_id` (`vale_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `itens_vale_ibfk_1` FOREIGN KEY (`vale_id`) REFERENCES `vales` (`id`),
  CONSTRAINT `itens_vale_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itens_vale`
--

LOCK TABLES `itens_vale` WRITE;
/*!40000 ALTER TABLE `itens_vale` DISABLE KEYS */;
INSERT INTO `itens_vale` VALUES (1,2,NULL,3,NULL),(2,2,NULL,3,NULL),(3,3,NULL,1,NULL);
/*!40000 ALTER TABLE `itens_vale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itens_venda_teka_away`
--

DROP TABLE IF EXISTS `itens_venda_teka_away`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `itens_venda_teka_away` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_venda` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `nome_produto` varchar(255) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `venda_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_venda` (`id_venda`),
  KEY `id_produto` (`id_produto`),
  CONSTRAINT `itens_venda_teka_away_ibfk_1` FOREIGN KEY (`id_venda`) REFERENCES `vendas_teka_away` (`id`),
  CONSTRAINT `itens_venda_teka_away_ibfk_2` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itens_venda_teka_away`
--

LOCK TABLES `itens_venda_teka_away` WRITE;
/*!40000 ALTER TABLE `itens_venda_teka_away` DISABLE KEYS */;
/*!40000 ALTER TABLE `itens_venda_teka_away` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_login`
--

DROP TABLE IF EXISTS `logs_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `data_logout` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `logs_login_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_login`
--

LOCK TABLES `logs_login` WRITE;
/*!40000 ALTER TABLE `logs_login` DISABLE KEYS */;
INSERT INTO `logs_login` VALUES (1,NULL,'2025-06-10 10:39:32','2025-06-12 23:23:24',NULL),(2,NULL,'2025-06-10 10:39:44','2025-06-12 23:23:24',NULL),(3,NULL,'2025-06-10 10:41:07','2025-06-12 23:23:24',NULL),(4,12,'2025-06-10 10:50:21','2025-06-12 23:23:24',NULL),(5,12,'2025-06-10 10:55:08','2025-06-10 10:56:27',NULL),(6,12,'2025-06-10 10:56:29','2025-06-10 10:56:32',NULL),(7,12,'2025-06-10 10:56:33','2025-06-10 10:58:35',NULL),(8,17,'2025-06-10 10:58:41','2025-06-10 10:59:20',NULL),(9,17,'2025-06-10 10:59:22','2025-06-10 11:08:36',NULL),(10,17,'2025-06-10 11:08:38','2025-06-10 11:11:17',NULL),(11,17,'2025-06-10 11:12:38','2025-06-10 11:14:16',NULL),(12,17,'2025-06-10 11:50:38','2025-06-12 23:23:24',NULL),(13,17,'2025-06-10 11:54:59','2025-06-10 11:55:02',NULL),(14,22,'2025-06-10 11:56:02','2025-06-10 13:26:17',NULL),(15,19,'2025-06-10 13:26:26','2025-06-10 13:37:45',NULL),(16,19,'2025-06-10 13:37:47','2025-06-10 13:45:45',NULL),(17,19,'2025-06-10 17:02:26','2025-06-12 23:23:24',NULL),(18,19,'2025-06-10 17:02:43','2025-06-12 23:23:24',NULL),(19,12,'2025-06-10 17:03:16','2025-06-12 23:23:24',NULL),(20,12,'2025-06-10 17:06:11','2025-06-12 23:23:24',NULL),(21,12,'2025-06-10 17:06:14','2025-06-10 17:06:16',NULL),(22,19,'2025-06-10 17:06:22','2025-06-12 23:23:24',NULL),(23,19,'2025-06-10 19:12:23','2025-06-12 23:23:24',NULL),(24,12,'2025-06-10 20:11:25','2025-06-12 23:23:24',NULL),(25,19,'2025-06-11 09:14:08','2025-06-12 23:23:24',NULL),(26,19,'2025-06-11 09:15:27','2025-06-12 23:23:24',NULL),(27,19,'2025-06-11 09:43:02','2025-06-12 23:23:24',NULL),(28,19,'2025-06-11 09:43:16','2025-06-12 23:23:24',NULL),(29,19,'2025-06-11 09:57:34','2025-06-11 13:13:32',NULL),(30,12,'2025-06-11 13:13:37','2025-06-11 13:14:24',NULL),(31,17,'2025-06-11 13:14:27','2025-06-12 23:23:24',NULL),(32,17,'2025-06-11 18:24:51','2025-06-12 23:23:24',NULL),(33,17,'2025-06-11 19:03:09','2025-06-11 20:01:16',NULL),(34,19,'2025-06-11 20:01:23','2025-06-12 23:23:24',NULL),(35,19,'2025-06-11 20:02:26','2025-06-12 23:23:24',NULL),(36,19,'2025-06-12 09:24:19','2025-06-12 23:23:24',NULL),(37,19,'2025-06-12 09:24:44','2025-06-12 23:23:24',NULL),(38,19,'2025-06-12 09:25:20','2025-06-12 23:23:24',NULL),(39,19,'2025-06-12 09:26:15','2025-06-12 09:26:21',NULL),(40,12,'2025-06-12 09:26:27','2025-06-12 23:23:24',NULL),(41,12,'2025-06-12 11:05:47','2025-06-12 11:06:03',NULL),(42,19,'2025-06-12 11:06:08','2025-06-12 11:54:56',NULL),(43,19,'2025-06-12 11:54:58','2025-06-12 12:07:30',NULL),(44,19,'2025-06-12 12:07:35','2025-06-12 08:59:15',NULL),(45,17,'2025-06-12 13:36:34','2025-06-12 23:23:24',NULL),(46,17,'2025-06-12 13:42:54','2025-06-12 13:43:13',NULL),(47,12,'2025-06-12 13:43:17','2025-06-12 13:45:31',NULL),(48,12,'2025-06-12 13:47:03','2025-06-12 23:23:24',NULL),(49,12,'2025-06-12 08:39:11','2025-06-12 23:23:24',NULL),(50,19,'2025-06-12 08:53:13','2025-06-12 23:23:24',NULL),(51,12,'2025-06-12 08:55:00','2025-06-12 23:23:24',NULL),(52,19,'2025-06-12 08:56:13','2025-06-12 23:23:24',NULL),(53,17,'2025-06-12 20:52:13','2025-06-12 20:56:27',NULL),(54,12,'2025-06-12 20:56:31','2025-06-12 23:23:24',NULL),(55,19,'2025-06-12 22:44:14','2025-06-12 23:23:24',NULL),(56,12,'2025-06-12 22:45:48','2025-06-12 23:23:24',NULL),(57,12,'2025-06-12 23:33:29','2025-06-12 23:40:59',NULL),(58,12,'2025-06-12 23:41:20','2025-06-12 23:41:32',NULL),(59,12,'2025-06-12 23:41:54','2025-06-12 23:42:58',NULL),(60,12,'2025-06-12 23:41:58','2025-06-12 23:42:58',NULL),(61,12,'2025-06-12 23:42:33','2025-06-12 23:42:58',NULL),(62,12,'2025-06-12 23:46:02','2025-06-12 23:46:08',NULL),(63,12,'2025-06-13 20:32:15','2025-06-14 08:36:30',NULL),(64,19,'2025-06-14 08:36:42','2025-06-14 09:28:15',NULL),(65,19,'2025-06-14 08:37:19','2025-06-14 08:37:51',NULL),(66,12,'2025-06-14 08:38:26','2025-06-14 09:28:15',NULL),(67,12,'2025-06-14 09:28:59','2025-06-14 09:34:42',NULL),(68,19,'2025-06-14 09:34:53','2025-06-14 11:07:21',NULL),(69,12,'2025-06-14 11:07:25','2025-06-14 12:40:22',NULL),(70,12,'2025-06-14 16:28:35','2025-06-14 18:31:43',NULL),(71,12,'2025-06-14 18:02:07','2025-06-14 18:31:43',NULL),(72,19,'2025-06-14 18:33:33','2025-06-15 16:10:42',NULL),(73,12,'2025-06-14 18:33:50','2025-06-14 18:34:06',NULL),(74,20,'2025-06-14 18:34:19','2025-06-14 18:34:57',NULL),(75,12,'2025-06-15 16:12:55','2025-06-15 16:20:13',NULL),(76,19,'2025-06-15 16:20:15','2025-06-15 16:22:19',NULL),(77,12,'2025-06-15 16:23:06',NULL,NULL),(78,12,'2025-06-15 16:27:16',NULL,NULL),(79,19,'2025-06-15 17:08:38',NULL,NULL),(80,12,'2025-06-15 17:09:42',NULL,NULL),(81,19,'2025-06-15 17:40:01',NULL,NULL),(82,12,'2025-06-15 17:48:21','2025-06-15 21:30:17',NULL),(83,19,'2025-06-15 21:30:55',NULL,NULL),(84,12,'2025-06-15 22:01:25','2025-06-15 23:27:56',NULL),(85,12,'2025-06-15 23:28:32','2025-06-15 23:37:04',NULL),(86,19,'2025-06-16 06:34:39','2025-06-16 09:44:19',NULL),(87,19,'2025-06-16 09:44:50',NULL,NULL),(88,19,'2025-06-16 13:02:58',NULL,NULL),(89,19,'2025-06-16 13:08:28','2025-06-16 18:27:23',NULL),(90,12,'2025-06-16 15:08:57',NULL,NULL),(91,12,'2025-06-16 18:07:08',NULL,NULL),(92,19,'2025-06-16 18:54:17',NULL,NULL),(93,19,'2025-06-16 19:09:39',NULL,NULL),(94,19,'2025-06-16 21:20:25','2025-06-17 09:22:04',NULL),(95,19,'2025-06-17 09:41:31',NULL,NULL),(96,19,'2025-06-17 15:02:54',NULL,NULL),(97,19,'2025-06-17 20:34:32',NULL,NULL),(98,19,'2025-06-18 12:23:38',NULL,NULL),(99,12,'2025-06-18 18:01:03',NULL,NULL),(100,19,'2025-06-18 18:42:04',NULL,NULL),(101,19,'2025-06-18 19:47:51','2025-06-18 19:47:55',NULL),(102,19,'2025-06-18 19:47:57','2025-06-18 19:47:59',NULL),(103,12,'2025-06-18 19:48:04','2025-06-18 19:48:17',NULL),(104,20,'2025-06-18 19:48:21','2025-06-18 20:11:22',NULL),(105,12,'2025-06-18 20:11:25','2025-06-18 20:21:41',NULL),(106,22,'2025-06-18 20:22:36','2025-06-18 20:31:20',NULL),(107,12,'2025-06-19 00:30:01','2025-06-20 15:56:57',NULL),(108,19,'2025-06-20 15:57:17','2025-06-20 16:28:41',NULL),(109,12,'2025-06-20 16:54:23',NULL,NULL),(110,12,'2025-06-20 17:18:58','2025-06-20 17:27:15',NULL),(111,19,'2025-06-20 17:27:23','2025-06-20 17:28:50',NULL),(112,26,'2025-06-20 17:42:32',NULL,NULL),(113,26,'2025-06-20 17:42:39',NULL,NULL),(114,26,'2025-06-20 17:46:02','2025-06-20 17:54:34',NULL),(115,12,'2025-06-20 17:54:42',NULL,NULL),(116,19,'2025-06-20 19:30:50','2025-06-20 19:37:43',NULL),(117,12,'2025-06-20 19:37:48',NULL,NULL),(118,19,'2025-06-20 20:00:13','2025-06-20 20:03:35',NULL),(119,26,'2025-06-20 20:03:39','2025-06-20 20:06:28',NULL);
/*!40000 ALTER TABLE `logs_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimento_estoque`
--

DROP TABLE IF EXISTS `movimento_estoque`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimento_estoque` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_barra` varchar(50) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `tipo_movimento` varchar(50) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_hora` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimento_estoque`
--

LOCK TABLES `movimento_estoque` WRITE;
/*!40000 ALTER TABLE `movimento_estoque` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimento_estoque` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos`
--

DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `codigo_barra` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `quantidade_estoque` int(11) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `estoque` int(11) NOT NULL DEFAULT 0,
  `quantidade` int(11) NOT NULL DEFAULT 0,
  `imagem` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_barra` (`codigo_barra`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=556 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
INSERT INTO `produtos` VALUES (128,'Test1','60000',1500.00,NULL,100,'2024-09-21 18:23:28',-6,-9,NULL),(129,'Massa Cotovelo','6009802818251',50.00,4,100,'2024-09-22 16:13:53',-28,-9,NULL),(130,'2M Txoti 270ml','6009653531002',55.00,1,1,'2024-11-06 10:17:39',0,-1,NULL),(131,'2M Lata 330ml','6009653530753',55.00,1,101,'2024-11-06 10:22:02',-16,-2,NULL),(132,'L. Preta Txoti 270ml','6009653531194',60.00,1,0,'2024-11-06 10:22:42',-2,-4,NULL),(133,'L. Preta Grande 550ml','Preta Grande',70.00,1,1,'2024-11-06 10:23:27',0,0,NULL),(134,'Lite Lata 550ml','6003326014885',65.00,1,0,'2024-11-06 10:24:12',-1,-2,NULL),(135,'Lite Mini 250 ML','6003326013758',50.00,1,0,'2024-11-06 10:28:40',0,0,NULL),(136,'Brutal 330ml','6003326013949',95.00,1,1,'2024-11-06 10:29:17',0,0,NULL),(137,'Hunters Gold 330ml','6001108055231',80.00,1,1,'2024-11-06 10:30:28',0,0,NULL),(138,'Hunters Dry 330ml','6001108055187',90.00,1,0,'2024-11-06 10:31:04',0,0,NULL),(139,'Bernini Lata 500ml','6001108108487',110.00,1,1,'2024-11-06 10:39:55',0,0,NULL),(140,'Flying Fish  Lemon 300ml','6003326009584',90.00,1,0,'2024-11-06 10:40:58',0,-5,NULL),(141,'Corona 335ml','6003326014526',90.00,1,1,'2024-11-06 10:41:34',0,0,NULL),(142,'Savana Lemon 300ml','6001108077981',90.00,1,0,'2024-11-06 10:42:15',0,-7,NULL),(143,'Impala Lata 330ml','Impala Lata',55.00,1,-1,'2024-11-06 10:42:46',0,-1,NULL),(144,'HEINIKEN TXOTI 275ml','9504000241087',85.00,1,1,'2024-11-06 10:44:21',0,89,NULL),(145,'Heiniken Lata 330ml','9504000241353',80.00,1,1,'2024-11-06 10:45:58',0,-4,NULL),(146,'Manica Lata 330ml','6009653531064',65.00,1,1,'2024-11-06 10:46:53',0,-2,NULL),(147,'Myfair 500ml','6009881127268',100.00,1,0,'2024-11-06 10:57:31',0,0,NULL),(148,'Schweps Gingeral 330ml','Gingeral',60.00,1,0,'2024-11-06 11:00:18',0,-1,NULL),(149,'Creme Soda Lata 330ml','Creme Soda Lata',60.00,1,0,'2024-11-06 11:00:43',0,0,NULL),(150,'Dragon 550ml','6009694633116',55.00,1,0,'2024-11-06 11:11:01',0,0,NULL),(151,'Monster 550ml','5060166698874',90.00,1,0,'2024-11-06 11:15:59',0,0,NULL),(152,'Switch 550ml','Switch',65.00,1,0,'2024-11-06 11:16:49',0,0,NULL),(153,'Coca Cola Lata 330ml','5449000000996',60.00,1,0,'2024-11-06 11:17:39',0,0,NULL),(154,'Fanta Uva 330ml','5449000069429',60.00,1,0,'2024-11-06 11:17:58',0,0,NULL),(155,'Sprit Lata 330ml','Sprit Lata ',60.00,1,0,'2024-11-06 11:18:12',0,0,NULL),(156,'Fanta Maca Lata 330ml','Fanta Maca Lata',60.00,1,0,'2024-11-06 11:18:43',0,0,NULL),(157,'Sparleta Morango Lata 330ml','5449000106322',60.00,1,0,'2024-11-06 11:23:44',0,0,NULL),(158,'Agua Tonica Lata 330ml','Agua Tonica',60.00,1,0,'2024-11-06 11:24:28',0,0,NULL),(159,'Schwepps Lemon Lata 330ml','Soda Lata',60.00,1,0,'2024-11-06 11:25:08',0,0,NULL),(160,'Coca Cola Garrafa 300ml','Coca Cola Garrafa',30.00,1,0,'2024-11-06 11:26:11',0,0,NULL),(161,'Sprit Garrafa 300ml','34',30.00,1,0,'2024-11-06 11:26:57',0,0,NULL),(162,'Fata Uva Garrafa 300ml','35',30.00,1,0,'2024-11-06 11:27:12',0,0,NULL),(163,'Fata Maca Garrafa 300ml','36',30.00,1,0,'2024-11-06 11:27:32',0,0,NULL),(164,'Fata Ananas Garrafa 300ml','37',30.00,1,0,'2024-11-06 11:27:49',0,0,NULL),(165,'Creme Soda Garrafa 300ml','1',30.00,1,0,'2024-11-06 11:29:30',0,0,NULL),(166,'Compal Tropical 500ml','5601151974353',75.00,4,0,'2024-11-06 11:30:48',0,-1,NULL),(167,'Sprit 1L','Sprit 1L',80.00,1,0,'2024-11-06 11:34:59',0,0,NULL),(168,'Sparleta 1L','Sparleta 1L',80.00,1,0,'2024-11-06 11:35:19',0,0,NULL),(169,'Creme Soda 1L','Creme Soda 1L',80.00,1,0,'2024-11-06 11:35:36',0,0,NULL),(170,'Refresco 1L','Refresco 1L',80.00,1,0,'2024-11-06 11:36:07',0,0,NULL),(171,'Fanta Ananas Lata 330ml','5449000003201',60.00,1,0,'2024-11-06 11:36:23',0,0,NULL),(172,'Lemon Twist','Lemon Twist',60.00,1,0,'2024-11-06 11:37:31',0,0,NULL),(173,'Test','4000',0.50,4,0,'2024-11-06 11:49:53',0,0,NULL),(174,'Coca Cola Zero 330ml','5449000131805',30.00,1,0,'2024-11-06 12:26:14',0,100,NULL),(175,'Agua Vumba 330ml','Agua Vumba ',35.00,1,0,'2024-11-06 12:36:11',0,0,NULL),(176,'Agua Plus 600ml','6009832820507',30.00,1,0,'2024-11-06 12:37:49',0,0,NULL),(177,'Ceres 500ml','55',70.00,4,0,'2024-11-06 12:46:44',0,0,NULL),(178,'Ceres 1Litros','6001240100288',135.00,4,0,'2024-11-06 12:47:30',0,0,NULL),(179,'L. Preta Lata 330ml','Preta Lata',65.00,1,0,'2024-11-06 12:51:25',0,0,NULL),(180,'Txilar Lata 330ml','9504000241322',50.00,1,0,'2024-11-06 12:52:38',0,-9,NULL),(181,'Txilar Txoti 275ml','9504000241223',60.00,1,0,'2024-11-06 12:53:41',0,0,NULL),(182,'Txilar 500ml','Txilar Grande',50.00,1,1,'2024-11-06 12:58:06',0,-9,NULL),(183,'Impala 550ml','Impala Grande ',50.00,1,-6,'2024-11-06 14:05:34',0,-18,NULL),(184,'Vinagre Branco 750ml','6002833000336',60.00,4,-2,'2024-11-06 14:15:49',0,-1,NULL),(185,'Nik Naks','6009510804683',5.00,4,0,'2024-11-06 14:17:21',0,-1,NULL),(186,'Chippa Peri-peri Chicken','6009801460024',5.00,4,0,'2024-11-06 14:18:11',0,-11,NULL),(187,'Cheeps - Hello Tomato','6009645792404',5.00,4,0,'2024-11-06 14:19:03',0,0,NULL),(188,'Papel Higienico - Softuch','6004586000977',35.00,3,0,'2024-11-06 14:19:39',0,0,NULL),(189,'Jam','6009669050283',80.00,4,0,'2024-11-06 14:20:55',0,0,NULL),(190,'Penso Day to Day','6009680771853',60.00,4,1,'2024-11-06 14:24:27',0,0,NULL),(191,'Leite Clover Ultra 500ml','6001299026881',60.00,4,0,'2024-11-06 14:25:07',-6,-2,NULL),(192,'Oleo Somol 2L','6001565023835',270.00,4,1,'2024-11-06 14:30:41',0,-1,NULL),(193,'Tomate Sauce All Gold 700 ml','60019578',170.00,4,0,'2024-11-06 14:32:50',0,0,NULL),(194,'Danone Nutriday','6009708461919',30.00,4,0,'2024-11-06 14:35:05',0,0,NULL),(195,'Colgate Triple Action','6920354814891',80.00,4,-1,'2024-11-06 14:38:59',0,0,NULL),(196,'Penso Always Azul','8700216268011',130.00,4,0,'2024-11-06 14:40:16',0,0,NULL),(197,'Penso Kotex','6001019911794',100.00,4,0,'2024-11-06 14:41:02',0,0,NULL),(198,'Shibobo Mini Vanilla','6009645791339',15.00,4,0,'2024-11-06 14:43:05',0,0,NULL),(199,'Cerelac 200g','Cerelac ',170.00,4,0,'2024-11-06 14:44:39',0,0,NULL),(200,'Baby Wipes','6971003980245',100.00,4,0,'2025-01-15 14:51:12',0,0,NULL),(201,'Sal Fino 1kg','10096086',60.00,4,0,'2025-01-15 14:55:58',0,-1,NULL),(202,'Sal Grosso Kingfisher 500g','Sal Grosso',30.00,4,0,'2025-01-15 14:57:20',0,0,NULL),(203,'2M Grande 550ml','2M Grande',60.00,1,1,'2024-11-09 04:30:47',0,29,NULL),(204,'Heiniken Silver Lata','6009705712892',110.00,1,0,'2024-11-09 04:33:28',0,0,NULL),(205,'Spin','Spin',80.00,1,0,'2024-11-09 04:35:45',0,0,NULL),(206,'Red Bull','9002490100070',100.00,1,0,'2024-11-09 04:36:42',0,0,NULL),(207,'Red Bull Grande','100',110.00,1,0,'2024-11-09 04:37:19',0,0,NULL),(208,'Four Causen Pequeno','Four Causen Pequeno',400.00,1,0,'2024-11-09 04:45:21',0,0,NULL),(209,'Four Causen Grande','Four Causen Grande',600.00,1,0,'2024-11-09 04:45:48',0,0,NULL),(210,'JC Lata','6001108073587',100.00,1,0,'2024-11-09 05:06:57',0,-1,NULL),(211,'Escape','Escape',500.00,1,0,'2024-11-09 05:11:17',0,0,NULL),(212,'Ovo','ovo',15.00,4,0,'2024-11-12 21:19:33',0,-6,NULL),(213,'Bolacha Agua e Sal Bites','6009603571218',25.00,4,0,'2024-11-12 21:20:33',0,0,NULL),(214,'Bolacha Maria Pequena','6009603571195',25.00,4,0,'2024-11-12 21:20:49',0,-1,NULL),(215,'Biscoito','Biscoito',5.00,4,0,'2024-11-12 21:21:13',0,0,NULL),(216,'Compal Da Terra 500ml','5601151973318',70.00,4,0,'2024-11-12 21:22:10',0,0,NULL),(217,'Compal Da Terra 1L','Compal grande',150.00,4,0,'2024-11-12 21:22:38',0,0,NULL),(218,'MAQ 2kg','MAQ 2kg',300.00,4,0,'2024-11-12 21:24:36',0,0,NULL),(219,'Uma Duzia De Ovos','Uma Duzia',140.00,4,1,'2024-11-12 21:25:02',0,-1,NULL),(220,'Caldo Benny Embalagem','6009522305741',210.00,4,0,'2024-11-12 21:27:33',0,0,NULL),(221,'Refresco garrafa 300ml','Refresco garrafa',25.00,1,2,'2024-11-12 21:42:23',0,-8,NULL),(222,'Javel Jk Regular','6001106107031',120.00,4,0,'2024-11-12 22:33:51',0,0,NULL),(223,'Aguafresh Limao','6001076001025',80.00,4,-2,'2024-11-12 22:34:17',0,-2,NULL),(224,'Dragao Limpo','Dragao',15.00,4,0,'2025-01-15 13:57:11',0,-1,NULL),(225,'Refresco 2l','Refresco 2l',120.00,1,0,'2024-11-13 13:58:44',0,-2,NULL),(226,'Caneta Big','Caneta Big',15.00,4,0,'2024-11-13 13:59:28',0,0,NULL),(227,'Caneta Europa','Caneta Europa',15.00,4,0,'2024-11-13 13:59:53',0,0,NULL),(228,'Meia Duzia de Ovo','Meia Duzia',70.00,4,1,'2024-11-13 14:18:03',0,-2,NULL),(229,'Dragao Limpo Caixinha','Dragao caixinha',60.00,4,0,'2024-11-13 19:23:27',0,0,NULL),(230,'Manica 500ml','Manica Grande',85.00,1,0,'2024-11-14 18:12:31',0,0,NULL),(231,'Bolacha Crembi','6291003032080',30.00,4,0,'2024-11-14 18:15:09',0,0,NULL),(232,'Bolacha Maria Grande','6009603571201',50.00,4,0,'2024-11-14 18:16:07',0,0,NULL),(233,'Bolacha Aguas e Sal ','6009819740811',25.00,4,0,'2024-11-14 18:16:47',0,0,NULL),(234,'Bolacha Bites','6009819740040',20.00,4,0,'2024-11-14 18:17:42',0,0,NULL),(235,'Compal 200ml','Compal pequeno',30.00,4,0,'2024-11-14 18:23:24',0,0,NULL),(236,'Agua Plus 1Litro','6009832820576',50.00,1,0,'2024-11-14 18:27:44',0,0,NULL),(237,'So Klim','8998866624947',10.00,4,0,'2024-11-14 18:31:28',0,0,NULL),(238,'Penso Usual','793573264893',85.00,4,1,'2024-11-14 18:32:16',0,-4,NULL),(239,'Sabonete Securex','6009678264152',65.00,4,1,'2024-11-14 18:32:51',0,-2,NULL),(240,'Atum','Atum',100.00,4,0,'2024-11-14 18:41:37',0,-1,NULL),(241,'Coca Cola Txoti 300ml','42117131',35.00,1,0,'2024-11-14 19:07:58',0,0,NULL),(242,'Bon Bon','Bon Bon',20.00,4,0,'2024-11-14 19:10:11',0,0,NULL),(243,'PASTILHA','PASTILHA',2.00,4,0,'2024-11-14 19:11:27',0,-2,NULL),(244,'Menta Fabuloso','Menta',2.50,4,1,'2024-11-14 19:12:10',0,0,NULL),(245,'Doce Pin Pop','Pin Pop',5.00,4,0,'2024-11-14 19:13:00',0,-3,NULL),(246,'Arrufada','Arrufada',7.00,4,0,'2024-11-14 19:13:34',0,0,NULL),(247,'Massa Espargueta','6009808630314',50.00,4,-2,'2024-11-14 19:16:02',0,0,NULL),(248,'Teste de systema','test2',12.00,NULL,0,'2024-11-14 19:39:17',0,0,NULL),(249,'Gulabos','Gulabos',20.00,4,0,'2024-11-15 19:57:54',0,0,NULL),(250,'Fralda','Fralda',10.00,4,0,'2024-11-15 20:07:35',0,-6,NULL),(251,'Wisky Glented','Wisky Glented',560.00,1,0,'2024-11-15 20:12:18',0,0,NULL),(252,'Vinho Organic Merlot','Vinho Organic',600.00,1,0,'2024-11-16 17:53:16',0,0,NULL),(253,'Vinho Cabriz','Vinho Cabriz',900.00,1,0,'2024-11-16 17:55:37',0,0,NULL),(254,'Quinta Bolota','Vinho Quinta Bolota',1100.00,1,0,'2024-11-16 18:01:15',0,0,NULL),(255,'Vinho Nederburg','Vinho Nederburg',500.00,1,0,'2024-11-16 18:07:32',0,0,NULL),(256,'Vinho Rosso Peaks','Vinho Rosso Peaks',500.00,1,0,'2024-11-16 18:08:30',0,-1,NULL),(257,'Amarula 750 ml','Amarula Grande',800.00,1,0,'2024-11-16 18:12:26',0,0,NULL),(258,'Savana Dry','6001108028044',90.00,1,0,'2024-11-17 21:56:06',0,-2,NULL),(259,'Bernini Pink','6001108055835',90.00,1,0,'2024-11-17 21:57:37',0,0,NULL),(260,'Rachel','Rachel',20.00,4,0,'2024-11-17 22:27:29',0,0,NULL),(261,'Wisky Grants','Wisky Grants',800.00,1,0,'2024-11-20 07:50:07',0,0,NULL),(262,'Palhinha','Palhinha',5.00,4,0,'2024-11-20 08:03:58',0,0,NULL),(263,'Tigelinhas','Tigelinhas',20.00,4,0,'2024-11-20 08:04:39',0,0,NULL),(264,'Copo Pequeno','Copo Pequeno',10.00,1,0,'2024-11-20 08:06:09',0,0,NULL),(265,'Refresco Txoti','Refresco Txoti',35.00,1,0,'2024-11-20 08:10:47',0,0,NULL),(266,'Emilia Lunato','Lunato',800.00,1,0,'2024-11-21 08:39:08',0,0,NULL),(267,'Lucky Star - Sardinha','5010821122008',65.00,4,1,'2024-11-21 08:42:02',0,-2,NULL),(268,'Lite 330ml','6003326015721',65.00,1,0,'2024-11-24 16:29:23',0,0,NULL),(269,'Ceres 200ml','6001240200018',40.00,4,0,'2024-11-24 16:30:38',0,0,NULL),(270,'GT','GT',5.00,4,0,'2024-11-24 17:02:54',0,-6,NULL),(271,'Martini Rosso','Martini Rosso',750.00,1,0,'2024-11-24 17:06:48',0,0,NULL),(272,'Bolacha Coco Pequena','6009603570648',25.00,4,0,'2024-11-24 17:41:32',0,0,NULL),(273,'BOLACHA BITE TOFFCO','6009819740095',15.00,4,100,'2024-11-24 17:42:31',0,0,NULL),(274,'Takaway','Takaway',15.00,4,0,'2024-11-24 17:50:54',0,0,NULL),(275,'Coco Plus','Coco Plus',5.00,4,0,'2024-11-24 17:57:11',0,0,NULL),(276,'Nutriday Natural','6009708462671',200.00,4,0,'2024-11-25 19:06:20',0,0,NULL),(277,'Oleio D\'lite 375 ml','6001565009020',80.00,4,0,'2024-11-25 19:16:19',0,0,NULL),(278,'Bolacha Topper','6001056412919',70.00,4,0,'2024-11-25 19:35:59',0,0,NULL),(279,'Bolacha Maria Blue Label','6001056453004',120.00,4,0,'2024-11-25 19:37:08',0,-2,NULL),(280,'Royal','7622210933881',35.00,4,0,'2024-11-25 19:47:37',0,-1,NULL),(281,'Massa Polana Esparguete','6009603571997',50.00,4,0,'2024-11-30 15:22:04',0,0,NULL),(282,'Vinagre Top Branco  37ml','6002833000046',30.00,4,0,'2024-11-30 15:23:56',0,-1,NULL),(283,'Vinagre Castanho 750ml','6006414000623',60.00,4,0,'2024-11-30 15:32:36',0,0,NULL),(284,'Chippa Peri-peri','6009707012266',5.00,4,0,'2024-11-30 15:37:08',0,0,NULL),(285,'Fosforo Embalagem','6001064000207',30.00,4,0,'2024-11-30 15:38:35',0,0,NULL),(286,'Frozy','60098797913046',20.00,1,0,'2024-11-30 16:45:36',0,-1,NULL),(287,'CAPPY GUAVA330ml','5449000305862',35.00,1,0,'2024-11-30 18:30:13',0,100,NULL),(288,'Santal 500ml','6001049049276',70.00,4,0,'2024-11-30 18:33:52',0,0,NULL),(289,'Dragao Caixinha','6924012159416',60.00,4,0,'2024-12-02 17:58:36',0,0,NULL),(290,'Frozy Maca','6009879713046',20.00,1,0,'2024-12-08 11:57:28',0,0,NULL),(291,'Rachel+Pao','Rachel+Pao',100.00,4,0,'2024-12-08 14:01:40',0,0,NULL),(292,'Super Glue Cola Tudo','8901860051367',10.00,4,0,'2024-12-10 17:52:40',0,0,NULL),(293,'Amendoin torado','Amendoin torado',10.00,4,0,'2024-12-10 17:53:23',0,-1,NULL),(294,'Castanha Torrada','Castanha',50.00,4,0,'2024-12-10 17:54:02',0,0,NULL),(295,'Folha de Cha 5','Folha de Cha 5',5.00,4,0,'2024-12-10 18:55:40',0,0,NULL),(296,'Cabeca de Alho','Alho',10.00,4,0,'2024-12-10 20:05:50',0,0,NULL),(297,'Frozy Energetico','6161105450597',35.00,1,0,'2024-12-13 12:19:39',0,0,NULL),(298,'Frozy Manga','6009879713077',20.00,1,0,'2024-12-13 12:20:26',0,0,NULL),(299,'Frozy Uva','6009900076737',20.00,1,0,'2024-12-13 12:21:25',0,0,NULL),(300,'Frozy Litcha','6009900076775',20.00,1,0,'2024-12-13 12:22:31',0,0,NULL),(301,'Frozy Limao 1L','6009879713107',70.00,1,0,'2024-12-13 12:24:20',0,0,NULL),(302,'Palmar Azul','Palmar Azul',7.00,4,0,'2024-12-13 14:12:47',0,0,NULL),(303,'Cigarro Pine','Pine',5.00,4,0,'2024-12-13 14:13:44',0,0,NULL),(304,'Ceres Misturas 200 ML','6001240200667',40.00,4,0,'2024-12-14 17:02:35',0,0,NULL),(305,'Five Rose','6001156230062',120.00,4,0,'2024-12-14 18:59:50',0,0,NULL),(306,'Stella Artois Lata','6003326014793',120.00,1,0,'2024-12-14 21:10:06',0,0,NULL),(307,'Castle Double Malt','6003326015387',90.00,1,0,'2024-12-14 21:11:24',0,0,NULL),(308,'Dairly Milk Mint Crisp','6001065601076',110.00,4,0,'2024-12-16 21:20:35',0,0,NULL),(309,'Nivea Dry','42389163',150.00,4,0,'2024-12-16 21:24:34',0,0,NULL),(310,'Bayegom Limpo','6936282086002',150.00,4,0,'2024-12-16 21:26:15',0,0,NULL),(311,'TANGY Mayonase Medio','6001571122218',180.00,4,0,'2024-12-18 09:21:05',0,0,NULL),(312,'Tomate Sauce 2kg','6006414000739',120.00,4,0,'2024-12-18 09:21:52',0,0,NULL),(313,'Stayfree Penso','6003001026127',80.00,4,0,'2024-12-18 09:22:37',0,0,NULL),(314,'Sunlight 750ml','6001087358743',140.00,4,0,'2025-01-14 17:06:40',0,0,NULL),(315,'Mayonaise Tangy','6009522301262',180.00,4,0,'2025-01-14 17:07:45',0,0,NULL),(316,'Caldo Benny Saqueta','6009522305734',10.00,4,0,'2025-01-14 17:09:37',0,0,NULL),(317,'Nutriday Medio 450g','6009708463425',130.00,4,0,'2025-01-14 17:10:08',0,0,NULL),(318,'Nutriday Grande 900g','6009708463470',200.00,4,0,'2025-01-14 17:10:41',0,0,NULL),(319,'Acucar 1kg','6009674390077',110.00,4,0,'2025-01-14 17:11:07',0,0,NULL),(320,'Instantania White Star','6001571127008',120.00,4,0,'2025-01-14 17:14:05',0,0,NULL),(321,'Leite Clover Ultra 1L','6001299026973',110.00,4,0,'2025-01-14 17:20:51',0,0,NULL),(322,'Agua Namaacha 500ml','6009670370028',30.00,1,0,'2025-01-14 17:21:18',0,0,NULL),(323,'Agua Namaacha 1L','6009670370004',60.00,1,0,'2025-01-14 17:21:39',0,0,NULL),(324,'Sumo Cappy 1L','5449000306579',85.00,4,1,'2025-01-14 17:23:16',0,-1,NULL),(325,'Guardanapo Napee','258822324259',60.00,4,0,'2025-01-14 17:26:20',0,0,NULL),(326,'Fosforo Impala','60024565',5.00,4,0,'2025-01-14 17:26:47',0,-3,NULL),(327,'Baygon','6001298899714',120.00,4,0,'2025-01-14 17:28:43',0,0,NULL),(328,'Folha de Cha Europa','6291109182429',100.00,4,0,'2025-01-14 17:29:49',0,0,NULL),(329,'Sabao Bingo','Bingo',70.00,4,0,'2025-01-14 17:30:49',0,0,NULL),(330,'Danone Ultra Mel','Danone grande',40.00,4,0,'2025-01-14 17:31:18',0,0,NULL),(331,'Sal Fino Garfinha','6014418739746',60.00,4,0,'2025-01-14 17:33:15',0,0,NULL),(332,'Arroz Aroma','6009900506180',110.00,4,0,'2025-01-14 17:33:37',0,0,NULL),(333,'Tomate Natural','6009705040568',75.00,4,0,'2025-01-14 17:34:03',0,0,NULL),(334,'Sumo Cappy Tropical 300ml','54017726',40.00,4,0,'2025-01-14 17:35:32',0,0,NULL),(335,'Sprite Txoti 300ml','5449000238894',30.00,1,0,'2025-01-14 17:37:43',0,0,NULL),(336,'Agua Plus 6L','6009832820521',85.00,1,0,'2025-01-15 11:19:32',0,0,NULL),(337,'Copo 15','Copo 15',15.00,1,0,'2025-01-15 11:20:00',0,0,NULL),(338,'Chocolate','6001065601014',110.00,4,0,'2025-01-15 11:20:26',0,0,NULL),(339,'Savana 500ml','Savana grande',150.00,1,0,'2025-01-15 11:21:44',0,0,NULL),(340,'Santal 1Litro','6001049049177',140.00,4,1,'2025-01-15 11:22:15',0,-2,NULL),(341,'Cape Sweet Red','Vinho Cape',500.00,1,0,'2025-01-15 11:22:44',0,0,NULL),(342,'Silc & Spice','5601012837636',900.00,1,1,'2025-01-15 11:23:28',0,0,NULL),(343,'Vinho Mucho Mas','8410702055635',1100.00,1,0,'2025-01-15 11:24:01',0,0,NULL),(344,'Vinho Rosso Peaks','700083592583',500.00,1,0,'2025-01-15 11:24:40',0,0,NULL),(345,'Vinho Monterosso 750 ml','781159208232',550.00,1,1,'2025-01-15 11:25:15',0,0,NULL),(346,'Bernini Classic','6001108055798',90.00,1,0,'2025-01-15 11:27:03',0,0,NULL),(347,'Strongbow','6009705710478',90.00,1,0,'2025-01-15 11:27:55',0,0,NULL),(348,'Luck Satr Pilchards','5010821132007',65.00,4,0,'2025-01-15 11:31:40',0,0,NULL),(349,'Queijo','Queijo',15.00,4,0,'2025-01-15 11:33:02',0,-2,NULL),(350,'Cappy Exotico Lata','5449000305879',70.00,4,0,'2025-01-15 11:33:58',0,0,NULL),(351,'Mayo Morango','6002004001353',60.00,4,0,'2025-01-15 11:34:18',0,0,NULL),(352,'Caldo Ricci','781718159685',5.00,4,1,'2025-01-15 11:34:55',0,-2,NULL),(353,'Vinagre de Limao TOP','6002833001111',100.00,4,0,'2025-01-15 11:36:43',0,0,NULL),(354,'Pop Corn - Milho de PiPocas','6009667670230',70.00,4,0,'2025-01-15 11:37:30',0,0,NULL),(355,'Colgate Carvao','8904271313031',80.00,4,0,'2025-01-15 11:38:00',0,0,NULL),(356,'Hand Hand','6001087006057',130.00,1,0,'2025-01-15 11:38:45',0,0,NULL),(357,'Nivea Man','42354963',150.00,4,0,'2025-01-15 11:40:00',0,0,NULL),(358,'Ricoffy','6009188000349',150.00,4,0,'2025-01-15 11:40:30',0,0,NULL),(359,'Vinagre Top Castanho 375ml','6006414000609',60.00,4,0,'2025-01-15 11:54:32',0,0,NULL),(360,'Top Score','6009603570419',75.00,4,1,'2025-01-15 11:55:06',0,-1,NULL),(361,'Mayo Banana','6002004001360',60.00,4,0,'2025-01-15 11:55:42',0,0,NULL),(362,'Nestle Cacau 62,5 g','6001068002207',140.00,4,0,'2025-01-15 11:56:34',0,0,NULL),(363,'Nestle Cacau 125 g','6001068002306',180.00,4,0,'2025-01-15 11:57:16',0,0,NULL),(364,'Palito de Dente','6972766010316',25.00,4,0,'2025-01-15 11:59:49',0,0,NULL),(365,'Fying Fish Apple','6003326016438',90.00,1,0,'2025-01-15 12:00:16',0,0,NULL),(366,'Namaacha 5L','6009670370011',80.00,1,0,'2025-01-15 12:01:27',0,0,NULL),(367,'Bite Milco','6009819740088',20.00,4,0,'2025-01-15 12:02:16',0,0,NULL),(368,'Bite Lemon','6009819740057',15.00,4,0,'2025-01-15 12:02:42',0,0,NULL),(369,'Bite Banana','6009819740101',15.00,4,0,'2025-01-15 12:02:59',0,0,NULL),(370,'Escova de Dentes','6001067023302',40.00,4,0,'2025-01-15 12:03:49',0,-2,NULL),(371,'G-Vita','746747149328',7.00,4,1,'2025-01-15 12:04:55',0,0,NULL),(372,'Oleio Sunseed 5 Litros','6001461786056',750.00,4,0,'2025-01-15 12:05:30',0,0,NULL),(373,'Coca Cola 2 Litros','5449000000286',120.00,1,0,'2025-01-15 12:05:57',0,-5,NULL),(374,'Coca Cola 1L','5449000054227',80.00,1,0,'2025-01-15 12:06:23',0,0,NULL),(375,'Topper Menta','6009704170136',60.00,4,0,'2025-01-15 12:08:09',0,0,NULL),(376,'Bakers Tennis','6001056662000',120.00,4,1,'2025-01-15 12:08:40',0,0,NULL),(377,'Bakers Romany Creams','6001125001877',160.00,4,0,'2025-01-15 12:10:34',0,0,NULL),(378,'Gold Masso','6009888464038',60.00,4,0,'2025-01-15 12:19:02',0,0,NULL),(379,'Frozy Lemon','6009900076683',20.00,1,0,'2025-01-15 12:19:39',0,0,NULL),(380,'Cigaro Gold','Gold',3.00,4,1,'2025-01-15 12:20:25',0,-16,NULL),(381,'Knorr Soup Vegetable','6001087359573',35.00,4,0,'2025-01-15 12:21:09',0,0,NULL),(382,'Cotonete','Cotonete',50.00,4,0,'2025-01-15 12:33:25',0,0,NULL),(383,'Fanta Laranja Garrafa','40822921',30.00,1,0,'2025-01-15 14:26:05',0,0,NULL),(384,'Corona Lata','6003326016711',120.00,1,0,'2025-01-15 14:27:56',0,0,NULL),(385,'Yogi Yo','6002051000019',60.00,4,0,'2025-01-15 14:28:22',0,0,NULL),(386,'Fanta Laranja Txoti','90377884',35.00,1,0,'2025-01-15 14:28:49',0,0,NULL),(387,'Yogi Yo Morango','6002051000125',60.00,4,0,'2025-01-15 14:29:18',0,0,NULL),(388,'Frozy Energetico Lata','6009879713169',60.00,1,0,'2025-01-15 14:30:07',0,0,NULL),(389,'Fanta Laranja Lata','5449000011527',60.00,1,0,'2025-01-15 14:30:52',0,0,NULL),(390,'Tomate Sauce All Gold 350ml','60019585',140.00,4,0,'2025-01-15 14:32:35',0,0,NULL),(391,'Black Cat Smooth Peanut','60058461',140.00,4,0,'2025-01-15 14:36:47',0,0,NULL),(392,'Mayonase 375ml','6009522301224',140.00,4,0,'2025-01-15 14:37:55',0,0,NULL),(393,'Freshpak Rooibos','6009702443744',100.00,4,0,'2025-01-15 14:38:24',0,0,NULL),(394,'chiklete','chiklete',20.00,4,0,'2025-01-15 14:38:56',0,0,NULL),(395,'Budweiser','6003326012614',90.00,1,0,'2025-01-15 14:39:58',0,-1,NULL),(396,'Maq 1 kg','6009678261465',170.00,3,0,'2025-01-15 14:48:56',0,0,NULL),(397,'Super Glue Cola','6020223103443',20.00,4,0,'2025-01-15 14:52:05',0,0,NULL),(398,'Koo Sweetened Brine','6009522308186',100.00,4,0,'2025-01-15 14:53:01',0,0,NULL),(399,'Knorr Soup Chicken','6001038001551',35.00,4,0,'2025-01-15 14:53:48',0,0,NULL),(400,'Arroz Golden Harvest','6009801081120',110.00,4,0,'2025-01-15 14:54:22',0,0,NULL),(401,'JC Le Roux','6001497114007',550.00,1,0,'2025-01-15 14:55:03',0,0,NULL),(402,'Amarula 375ml','6001495062478',400.00,1,0,'2025-01-15 14:55:37',0,0,NULL),(403,'Monte Velho Esporao','5601989001412',700.00,1,0,'2025-01-15 14:56:01',0,0,NULL),(404,'Esfregao','Esfregao',15.00,4,-2,'2025-01-15 14:56:29',0,0,NULL),(405,'Espoja de Loica','Espoja',15.00,4,0,'2025-01-15 14:56:48',0,0,NULL),(406,'Maq Fita','6009678260260',10.00,3,0,'2025-01-15 14:57:16',0,0,NULL),(407,'Kotex Maxi Protect','5029053535760',100.00,4,0,'2025-01-15 14:57:48',0,0,NULL),(408,'Vela','Vela',20.00,4,0,'2025-01-15 14:58:09',0,0,NULL),(409,'Essence Vanila 40ml','60017024',80.00,4,0,'2025-01-15 14:58:45',0,0,NULL),(410,'Essence Vanila','6001038172558',150.00,4,0,'2025-01-15 14:59:13',0,0,NULL),(411,'Snow White Farinha','6006773000852',70.00,4,0,'2025-01-15 15:00:01',0,0,NULL),(412,'Bolacha Coco Grande','6009603571249',50.00,4,0,'2025-01-15 15:00:30',0,0,NULL),(413,'Nivea Men Stress Protect','42299813',150.00,4,0,'2025-01-15 15:01:44',0,0,NULL),(414,'Nivea Black White','42299806',150.00,4,0,'2025-01-15 15:02:30',0,0,NULL),(415,'Ballantine\'s','5010106111536',800.00,1,0,'2025-01-15 15:02:53',0,0,NULL),(416,'Fanta Laranja 2l','5449000004840',120.00,1,0,'2025-01-15 15:05:38',0,0,NULL),(417,'Sprite 2 Litros','5449000234636',120.00,1,0,'2025-01-15 15:05:58',0,0,NULL),(418,'Fanta Ananas 2 Litros','5449000003768',120.00,1,0,'2025-01-15 15:06:14',0,0,NULL),(419,'Agua Kool 1 Litros','6009802913093',60.00,1,0,'2025-01-15 15:06:41',0,0,NULL),(420,'Raja Fita','6001038256203',15.00,4,0,'2025-01-15 15:07:06',0,0,NULL),(421,'G-Vita Tropical','606110414012',7.00,4,1,'2025-01-15 15:08:05',0,0,NULL),(422,'Manteiga Rama','6009710390214',85.00,4,0,'2025-01-15 15:08:34',0,0,NULL),(423,'Faninha Flor Bela','6009603570013',85.00,4,0,'2025-01-15 15:09:12',0,-1,NULL),(424,'Plastico Medio','Plastico Medio',2.00,4,0,'2025-01-15 15:09:37',0,0,NULL),(425,'Plastico Grande','Plastico Grande',3.00,4,0,'2025-01-15 15:09:54',0,0,NULL),(426,'Sabonete Dettol','6001106123840',75.00,4,0,'2025-01-15 15:12:20',0,-1,NULL),(427,'Bom Bom','92000100',20.00,4,0,'2025-01-15 15:13:07',0,0,NULL),(428,'cafe ricofe','2020',100.00,4,0,'2025-06-09 08:55:06',0,-1,NULL),(430,'Arroz Maria','100200',1500.00,4,100,'2025-06-09 09:15:27',0,100,NULL),(431,'Cafe Gelado','1001000',250.00,1,0,'2025-06-10 10:19:39',0,100,NULL),(433,'COMPALINHO 200ML','5601151964187',40.00,1,0,'2025-06-14 14:31:00',0,99,NULL),(434,'CERELAC','7613287767684',200.00,4,0,'2025-06-14 14:31:39',0,100,NULL),(435,'EET SUM MOR','EET SUM MOR',140.00,4,0,'2025-06-14 14:32:38',0,100,NULL),(436,'CEBOLA ','CEBOLA 10',10.00,4,0,'2025-06-14 14:33:00',0,100,NULL),(437,'CABECA DE ALHO','ALHO 10',10.00,4,0,'2025-06-14 14:33:33',0,100,NULL),(438,'AGUA E SAL GRANDE','AGUA E SAL GRANDE',50.00,4,0,'2025-06-14 14:34:15',0,100,NULL),(442,'ACUCAR BRANCO','5009674390305',120.00,4,0,'2025-06-14 14:37:32',0,98,NULL),(443,'ATUM BOM AMIGO ','5602315005005',110.00,4,0,'2025-06-14 14:39:29',0,100,NULL),(444,'D.VITTA','6947042430126',60.00,4,0,'2025-06-14 14:44:00',0,99,NULL),(445,'LUCKY STAR GRANDE','6001115001535',150.00,4,0,'2025-06-14 14:44:57',0,100,NULL),(446,'MOJITTO  A LATA 500ML','6009711081562',100.00,1,0,'2025-06-14 14:45:40',0,100,NULL),(447,'OLEO DONA 1 LITRO','956000124711',170.00,4,0,'2025-06-14 14:46:28',0,98,NULL),(448,'ARROZ MARIANA 1KG','6009819740309',100.00,4,0,'2025-06-14 14:47:12',0,99,NULL),(449,'TOP TOMATE SOUCE 500 G','6006414000678',60.00,1,0,'2025-06-14 14:48:03',0,100,NULL),(451,'RICOFF 250 G','6001068323005',280.00,4,0,'2025-06-14 14:50:00',0,100,NULL),(452,'CUSTARD POWDER 250 G','600132520336',120.00,4,0,'2025-06-14 14:50:49',0,100,NULL),(453,'PROTAL LEITE CONDESSADO','401817009826',120.00,4,0,'2025-06-14 14:51:32',0,100,NULL),(454,'TRINCO  250 G','6001156232257',180.00,4,0,'2025-06-14 14:52:20',0,100,NULL),(455,'FARINHA XILUVA 1KG','6009808630109',95.00,4,0,'2025-06-14 14:53:08',0,100,NULL),(456,'MASSA ESPARGUETA MULHER','6291003059452',45.00,4,0,'2025-06-14 14:53:56',0,100,NULL),(457,'KOO BAKED BEANS','6009522300586',110.00,4,0,'2025-06-14 14:54:34',0,100,NULL),(458,'COMPAL DA TERRA - MANGA','5601151975527',140.00,4,0,'2025-06-14 14:55:19',0,98,NULL),(459,'HAND HAND ','6001087006095',140.00,3,0,'2025-06-14 14:55:52',0,100,NULL),(460,'LAMPADA LIMPO ','LAMPADA LIMPO',25.00,4,0,'2025-06-14 14:56:22',0,100,NULL),(461,'ALLYONS LAMPADA 15W','693487956031',100.00,4,0,'2025-06-14 14:57:30',0,99,NULL),(462,'COLGATE HERBAL','6920354817823',95.00,4,0,'2025-06-14 14:58:04',0,100,NULL),(463,'AQUEOUS CREAM ','600965724665',250.00,4,0,'2025-06-14 14:59:01',0,2,NULL),(464,'SOFT ZONA VITAMINA JHONSON','6009605230151',80.00,4,0,'2025-06-14 14:59:47',0,4,NULL),(466,'TETLEY TEA BLAND','6006822001694',100.00,4,0,'2025-06-14 15:01:33',0,100,NULL),(467,'CREMORA ','6009188002688',280.00,4,0,'2025-06-14 15:02:15',0,10,NULL),(468,'CREMORA ','CREMORA MEIO QUILO',140.00,4,0,'2025-06-14 15:02:48',0,100,NULL),(469,'AGUA PURA 500 ML','6009646580987',30.00,1,0,'2025-06-14 15:03:32',0,100,NULL),(470,'AGUA VUMBA 1.5 L','6009646581007',70.00,1,0,'2025-06-14 15:04:56',0,100,NULL),(471,'AGUA VUMBA 500 ML','6009646581014',40.00,1,0,'2025-06-14 15:05:50',0,100,NULL),(472,'AGUA PURA 1.5 L','6009646580994',50.00,1,0,'2025-06-14 15:06:27',0,99,NULL),(474,'BOLACHA BITE MINI','9504000181758',10.00,4,0,'2025-06-14 15:12:14',0,100,NULL),(475,'BLACK PEPER - PIMENTA PRETA','6009001014119',15.00,4,0,'2025-06-14 15:13:06',0,100,NULL),(477,'Fanta MACA 2 Litros','5449000664525',120.00,1,0,'2025-06-14 15:14:58',0,100,NULL),(478,'FANTA LARANJA 1 LITRO','5449000006271',80.00,1,0,'2025-06-14 15:16:01',0,99,NULL),(479,'FIZZ UVA','6009832820163',20.00,1,0,'2025-06-14 15:19:03',0,100,NULL),(480,'LOWVELD BANANA','6007575000569',65.00,1,0,'2025-06-14 15:20:06',0,99,NULL),(481,'CHEWPPS SODA 330 ML','5449000046437',50.00,1,0,'2025-06-14 15:23:00',0,100,NULL),(482,'AGUA TONICA 330 ML','5449000046390',50.00,1,0,'2025-06-14 15:23:34',0,100,NULL),(483,'DRY LEMON 330 ML','5449000140760',50.00,1,0,'2025-06-14 15:26:07',0,100,NULL),(484,'SWITCH ELEMENT','6009803982432',65.00,1,0,'2025-06-14 15:27:07',0,100,NULL),(486,'PURITY','6001059949849',50.00,4,0,'2025-06-14 15:32:07',0,100,NULL),(487,'SANTAL 1 LITRO','6001240240922',140.00,1,0,'2025-06-14 15:33:03',0,99,NULL),(488,'DANONE ULTRA MEL','DANONE ULTRA MEL',40.00,4,0,'2025-06-14 15:33:37',0,100,NULL),(489,'DANONE NUTRIDAY ','DANONE NUTRIDAY',25.00,4,0,'2025-06-14 15:34:17',0,100,NULL),(490,'DANOME LONG LIFE','DANONE LONG LIFE',30.00,4,0,'2025-06-14 15:34:48',0,100,NULL),(491,'CERES 200 ML','60012400200148',45.00,1,0,'2025-06-14 15:35:17',0,100,NULL),(492,'HERO NECTAR','5607238025741',40.00,1,0,'2025-06-14 15:36:11',0,100,NULL),(493,'HEINIKEN GRANDE','9504000241124',70.00,1,0,'2025-06-14 15:37:31',0,89,NULL),(494,'SUPER BOOK','56093254',75.00,1,0,'2025-06-14 15:38:57',0,99,NULL),(495,'RED BULL','90162602',100.00,1,0,'2025-06-14 15:40:27',0,99,NULL),(496,'MATEIGA RAMA 250 G','6009710390320',80.00,4,0,'2025-06-14 15:41:13',0,100,NULL),(497,'SWITCH DRY LEMON','6009803982449',65.00,1,0,'2025-06-14 15:42:38',0,100,NULL),(498,'SWITCH ORIGINAL','600980382470',65.00,1,0,'2025-06-14 15:43:23',0,100,NULL),(499,'IMPALA LATA','6009653531323',60.00,1,0,'2025-06-15 14:28:03',0,1,NULL),(500,'STELA ARTOIS','6003326012508',90.00,1,0,'2025-06-15 14:37:15',0,0,NULL),(501,'AMARULA','6001495062508',800.00,1,0,'2025-06-15 14:41:08',0,1,NULL),(502,'GATAO','5601129042114',450.00,1,0,'2025-06-15 14:43:05',0,1,NULL),(503,'CASAL GARCIA','5601096208308',450.00,1,0,'2025-06-15 14:44:08',0,550,NULL),(504,'FOUR COUSINS','6002269000573',400.00,1,0,'2025-06-15 14:46:23',0,1,NULL),(505,'DROSTDY','6001495201501',350.00,1,0,'2025-06-15 14:52:59',0,1,NULL),(506,'PE TINTO','5601989993199',450.00,1,0,'2025-06-15 14:54:44',0,1,NULL),(507,'AUTUM HARVEST','6001452376006',230.00,1,0,'2025-06-15 14:55:33',0,1,NULL),(508,'STRETTONS','6001466008856',550.00,1,0,'2025-06-15 14:56:18',0,1,NULL),(509,'IMPERIAL BLUE','8901522000108',450.00,1,0,'2025-06-15 14:57:19',0,1,NULL),(510,'GORDONS','5000289936651',380.00,1,0,'2025-06-15 14:58:16',0,1,NULL),(512,'SUPER GLUE','4719867213268',15.00,1,0,'2025-06-15 15:01:19',0,1,NULL),(513,'TSUMANI','6934149300810',50.00,4,0,'2025-06-15 15:02:05',0,1,NULL),(514,'MOLAS','6925865489248',30.00,4,0,'2025-06-15 15:03:15',0,1,NULL),(515,'CALDO BENNY SAQUETA','6009633520002',7.00,1,0,'2025-06-15 15:06:16',0,0,NULL),(516,'CIGARO MENTOL','6001060801822',7.00,1,0,'2025-06-15 15:07:07',0,-4,NULL),(518,'SPARLETAT MORANGO','5449000180056',120.00,1,0,'2025-06-15 18:39:39',0,1,NULL),(519,'COPO DE AMENDOIM','COPO DE AMENDOIM',35.00,1,0,'2025-06-15 18:47:28',0,1,NULL),(520,'COPO 20','COPO 20',20.00,1,0,'2025-06-15 18:47:54',0,1,NULL),(521,'FOLHA DE CHA','FOLHA DE CHA',5.00,1,0,'2025-06-15 18:48:14',0,1,NULL),(523,'DOCE DE CAFE','DOCE DE CAFE',2.50,4,0,'2025-06-15 18:52:29',0,1,NULL),(524,'MAYO MIXED','6002004001391',60.00,4,0,'2025-06-15 18:53:10',0,1,NULL),(525,'DANONE ULTRA MANGO','60024077',40.00,4,0,'2025-06-15 18:54:50',0,1,NULL),(528,'NESTLE MILO','6009188007669',190.00,4,0,'2025-06-16 07:21:28',0,1,NULL),(531,'SOUPA','SOUPA',60.00,4,0,'2025-06-16 07:23:13',0,0,NULL),(537,'AZEDINHA','AZEDINHA',2.50,4,0,'2025-06-16 16:10:26',0,1,NULL),(538,'MENTA HALL','MENTA HALL',2.50,4,0,'2025-06-16 16:10:53',0,1,NULL),(539,'LAMINA ','LAMINA',10.00,4,0,'2025-06-16 16:18:17',0,0,NULL),(540,'COCA COLA TXOTI','90357626',30.00,1,0,'2025-06-16 17:15:47',0,0,NULL),(541,'SIMPLES GIN','SIMPLES GIN',30.00,1,0,'2025-06-17 14:16:31',0,1,NULL),(544,'DUPLO GIN','DUPLO GIN',50.00,1,0,'2025-06-17 14:19:02',0,-11,NULL),(545,'DUPLO MACGREGOR','DUPLO MACGREGOR',75.00,1,0,'2025-06-18 19:27:19',0,1,NULL),(546,'SIMPLES MACGREGOR','SIMPLES MACGREGOR',50.00,1,0,'2025-06-18 19:27:46',0,1,NULL),(547,'SIMPLES CAPTAIN MORGAN','SIMPLES CAPTAIN MORGAN',75.00,1,0,'2025-06-18 19:34:09',0,1,NULL),(548,'DUPLO CAPTAIN MORGAN','DUPLO CAPTAIN MORGAN',150.00,1,0,'2025-06-18 19:34:24',0,1,NULL),(549,'SIMPLES AMARULA','SIMPLES AMARULA',80.00,1,0,'2025-06-18 19:34:55',0,1,NULL),(550,'COMPAL MULTIFRUTOS 1L','5601151973110',140.00,1,0,'2025-06-18 19:52:26',0,0,NULL),(551,'CHAMUSSA DE PEIXE','CHAMUSSA DE PEIXE',15.00,1,0,'2025-06-18 20:03:14',0,1,NULL),(552,'CHAMUSSA CARNE','CHAMUSSA CARNE',20.00,1,0,'2025-06-18 20:03:37',0,-3,NULL),(553,'Maga','1212',20.00,4,0,'2025-06-20 14:47:48',0,12,NULL),(555,'Maga','11111111',12.00,1,0,'2025-06-20 14:54:52',0,11,NULL);
/*!40000 ALTER TABLE `produtos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos_takeaway`
--

DROP TABLE IF EXISTS `produtos_takeaway`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produtos_takeaway` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `descricao` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos_takeaway`
--

LOCK TABLES `produtos_takeaway` WRITE;
/*!40000 ALTER TABLE `produtos_takeaway` DISABLE KEYS */;
INSERT INTO `produtos_takeaway` VALUES (1,'Dose de frango',250.00,NULL,'684a8c8a26505.jpg',NULL,'2025-06-12 08:15:06'),(2,'Dose Batata',60.00,NULL,'684a8f169fa99.jpg',NULL,'2025-06-12 08:25:58'),(3,'Frango Inteiro',750.00,NULL,'684a8f77961ed.jpg',NULL,'2025-06-12 08:27:35');
/*!40000 ALTER TABLE `produtos_takeaway` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos_vendidos`
--

DROP TABLE IF EXISTS `produtos_vendidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produtos_vendidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venda_id` int(11) DEFAULT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) DEFAULT NULL,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  `codigo_barra` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `venda_id` (`venda_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `produtos_vendidos_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`),
  CONSTRAINT `produtos_vendidos_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos_vendidos`
--

LOCK TABLES `produtos_vendidos` WRITE;
/*!40000 ALTER TABLE `produtos_vendidos` DISABLE KEYS */;
INSERT INTO `produtos_vendidos` VALUES (2,3,129,6,50.00,'6009802818251'),(3,3,131,2,65.00,'6009653530753'),(4,3,191,2,60.00,'6001299026881'),(5,4,129,2,50.00,'6009802818251'),(6,4,131,1,65.00,'6009653530753'),(7,5,131,1,65.00,'6009653530753'),(8,6,131,1,65.00,'6009653530753'),(9,7,129,1,50.00,'6009802818251'),(10,8,131,1,65.00,'6009653530753'),(11,9,132,1,60.00,'6009653531194'),(12,9,129,1,50.00,'6009802818251'),(13,10,131,1,65.00,'6009653530753'),(14,10,129,1,50.00,'6009802818251'),(15,11,131,1,65.00,'6009653530753'),(16,12,131,2,65.00,'6009653530753'),(17,13,129,1,50.00,'6009802818251'),(18,13,128,1,0.50,'60000'),(19,14,131,3,65.00,'6009653530753'),(20,14,128,1,0.50,'60000'),(21,14,132,1,60.00,'6009653531194'),(22,14,129,2,50.00,'6009802818251'),(23,14,134,1,65.00,'6003326014885'),(24,15,0,1,50.00,'6009802818251'),(25,15,0,1,65.00,'6009653530753'),(26,15,0,1,1500.00,'60000'),(27,16,0,1,50.00,'6009802818251'),(28,17,0,1,65.00,'6009653530753'),(29,17,0,1,60.00,'6001299026881'),(30,17,0,1,1500.00,'60000'),(31,18,0,1,50.00,'6009802818251'),(32,19,191,1,60.00,''),(33,19,129,4,50.00,''),(34,20,129,2,50.00,''),(35,21,129,2,50.00,''),(36,21,191,1,60.00,''),(37,22,131,1,65.00,''),(38,22,129,1,50.00,''),(39,23,191,1,60.00,''),(40,24,128,1,1500.00,''),(41,25,129,2,50.00,''),(42,25,128,1,1500.00,''),(43,26,431,1,250.00,''),(44,27,426,1,75.00,''),(46,30,134,2,65.00,''),(47,31,442,1,120.00,''),(48,32,280,1,35.00,''),(49,32,423,1,85.00,''),(50,32,447,1,170.00,''),(51,33,282,1,30.00,''),(52,33,326,3,5.00,''),(53,34,349,1,15.00,''),(54,34,270,1,5.00,''),(55,34,433,1,40.00,''),(56,34,148,1,60.00,''),(57,34,185,1,5.00,''),(58,34,228,1,80.00,''),(59,34,250,6,10.00,''),(60,34,279,1,120.00,''),(61,35,493,2,70.00,''),(62,35,145,1,90.00,''),(63,36,494,1,75.00,''),(64,36,258,2,90.00,''),(65,36,183,1,65.00,''),(66,37,340,1,140.00,''),(67,38,493,4,70.00,''),(68,39,221,1,25.00,''),(69,40,182,1,50.00,''),(70,41,340,1,140.00,''),(71,41,515,1,7.00,''),(72,41,184,1,60.00,''),(73,41,516,1,7.00,''),(74,41,186,2,5.00,''),(75,41,500,1,90.00,''),(76,42,373,1,120.00,''),(77,43,183,1,50.00,''),(78,44,270,1,5.00,''),(79,44,183,3,50.00,''),(80,45,203,2,60.00,''),(81,46,380,9,3.00,''),(82,47,516,1,7.00,''),(83,48,270,1,5.00,''),(84,49,182,1,50.00,''),(85,50,270,2,5.00,''),(86,51,221,1,25.00,''),(87,52,132,1,60.00,''),(88,52,225,1,120.00,''),(89,52,182,2,50.00,''),(90,53,203,2,60.00,''),(91,54,182,2,50.00,''),(92,55,142,4,90.00,''),(93,55,140,5,90.00,''),(94,55,210,1,100.00,''),(95,55,144,5,85.00,''),(96,56,245,1,5.00,''),(97,57,373,1,120.00,''),(98,58,516,1,7.00,''),(99,59,203,1,60.00,''),(100,60,373,1,120.00,''),(101,60,360,1,75.00,''),(102,61,182,1,50.00,''),(103,61,493,1,70.00,''),(104,62,214,1,25.00,''),(105,63,142,3,90.00,''),(106,64,186,1,5.00,''),(107,65,245,1,5.00,''),(108,66,203,1,60.00,''),(109,66,531,1,60.00,''),(110,67,267,2,65.00,''),(111,67,380,3,3.00,''),(112,68,238,1,85.00,''),(113,69,238,1,85.00,''),(114,70,373,1,120.00,''),(115,71,201,1,60.00,''),(116,72,221,1,25.00,''),(117,72,380,3,3.00,''),(118,73,395,1,90.00,''),(119,73,458,1,140.00,''),(120,73,540,1,30.00,''),(121,74,144,2,85.00,''),(122,75,286,1,20.00,''),(123,76,228,1,70.00,''),(124,76,240,1,100.00,''),(125,77,444,1,60.00,''),(126,78,166,1,75.00,''),(127,78,380,1,3.00,''),(128,78,192,1,270.00,''),(129,79,238,1,85.00,''),(130,80,145,2,80.00,''),(131,81,183,2,50.00,''),(132,82,182,2,50.00,''),(133,83,183,1,50.00,''),(134,84,145,1,80.00,''),(135,85,373,1,120.00,''),(136,85,370,1,35.00,''),(137,85,225,1,120.00,''),(138,86,219,1,140.00,''),(139,87,186,8,5.00,''),(140,88,352,2,5.00,''),(141,89,544,3,50.00,''),(142,89,478,1,80.00,''),(143,89,144,4,85.00,''),(144,89,539,1,10.00,''),(145,89,370,1,40.00,''),(146,89,180,6,50.00,''),(147,89,223,2,80.00,''),(148,89,239,2,65.00,''),(149,89,270,1,5.00,''),(150,90,279,1,120.00,''),(151,90,480,1,65.00,''),(152,90,516,2,7.00,''),(153,90,180,1,50.00,''),(154,90,324,1,85.00,''),(155,91,212,5,15.00,''),(156,92,349,1,15.00,''),(157,93,203,1,60.00,''),(158,94,180,2,50.00,''),(159,95,243,2,2.00,''),(160,96,224,1,15.00,''),(161,97,442,1,120.00,''),(162,98,183,1,50.00,''),(163,99,203,1,60.00,''),(164,100,203,2,60.00,''),(165,101,221,2,25.00,''),(166,102,495,1,100.00,''),(167,103,472,1,50.00,''),(168,104,203,1,60.00,''),(169,105,447,1,170.00,''),(170,106,256,1,500.00,''),(171,107,238,1,85.00,''),(172,108,458,1,140.00,''),(173,109,146,2,65.00,''),(174,110,203,5,60.00,''),(175,111,203,2,60.00,''),(176,111,132,2,60.00,''),(177,112,461,1,100.00,''),(178,113,544,3,50.00,''),(179,114,293,1,10.00,''),(180,115,245,1,5.00,''),(181,116,448,1,100.00,''),(182,117,493,4,70.00,''),(183,117,212,1,15.00,''),(184,118,487,1,140.00,''),(185,118,550,1,140.00,''),(186,119,203,4,60.00,''),(187,119,183,3,50.00,''),(188,120,544,6,50.00,''),(189,121,552,4,20.00,''),(190,122,221,3,25.00,''),(191,123,131,1,55.00,''),(192,123,129,1,50.00,''),(193,124,131,1,55.00,''),(194,124,128,2,1500.00,''),(195,125,191,1,60.00,''),(196,125,128,1,1500.00,''),(197,126,128,2,1500.00,''),(198,127,183,4,50.00,''),(199,127,130,1,55.00,''),(200,127,203,1,60.00,''),(201,128,183,1,50.00,''),(202,128,203,1,60.00,''),(203,129,183,1,50.00,''),(204,129,203,1,60.00,''),(205,130,128,3,1500.00,''),(206,131,128,1,1500.00,''),(207,131,129,1,50.00,''),(208,132,129,4,50.00,''),(209,132,191,1,60.00,''),(210,133,132,1,60.00,''),(211,133,428,1,100.00,''),(212,134,555,1,12.00,''),(213,134,203,21,60.00,''),(214,135,129,2,50.00,''),(215,135,143,1,55.00,''),(216,136,129,1,50.00,'');
/*!40000 ALTER TABLE `produtos_vendidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos_vendidos_takeaway`
--

DROP TABLE IF EXISTS `produtos_vendidos_takeaway`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produtos_vendidos_takeaway` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venda_id` int(11) DEFAULT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT NULL,
  `quantidade` int(11) DEFAULT NULL,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venda_id` (`venda_id`),
  CONSTRAINT `produtos_vendidos_takeaway_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas_takeaway` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos_vendidos_takeaway`
--

LOCK TABLES `produtos_vendidos_takeaway` WRITE;
/*!40000 ALTER TABLE `produtos_vendidos_takeaway` DISABLE KEYS */;
INSERT INTO `produtos_vendidos_takeaway` VALUES (1,5,1,NULL,NULL,1,250.00),(2,5,2,NULL,NULL,1,60.00),(3,5,3,NULL,NULL,1,750.00),(4,6,1,NULL,NULL,2,250.00),(5,6,2,NULL,NULL,1,60.00),(6,7,1,NULL,NULL,1,250.00),(7,7,2,NULL,NULL,1,60.00),(8,7,3,NULL,NULL,1,750.00),(9,8,1,NULL,NULL,1,250.00),(10,8,2,NULL,NULL,1,60.00),(11,8,3,NULL,NULL,1,750.00);
/*!40000 ALTER TABLE `produtos_vendidos_takeaway` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `nivel` enum('caixa','supervisor','gerente','admin','store','teka_away') NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (12,'Breezy Mambo','breezymambo@mambosystem95.com','$2y$10$oDmBac3zZpCHcpuIQX9asugg1NjS/OVt.Wvf3uaqXw0HzSfcPpQZq','admin','2025-05-26 10:07:51'),(15,'Supervisor','supervisor@mambosystem95.com','$2y$10$ti5AwvpujOGPwao7c3PHBu9dE0NWyce9wY4XsRTCjoIqwucXi1FGu','supervisor','2025-05-26 10:12:34'),(17,'gerente','gerente@mambosystem95.com','$2y$10$JnpE69nUMm7UwxmnaLNm4./FYO3Y4ew/SbGRKmEPdm8dotpjIIla.','gerente','2025-05-26 10:13:04'),(19,'Mambo','mambo@mambosystem95.com','$2y$10$cmdoiYG/GZAlrSUChPsqOezsijKuu7fxrhFTwypUuSg6Ilpmvi/gy','caixa','2025-05-26 10:14:07'),(20,'Herminio','herminio@mambosystem95.com','$2y$10$F2On2xMNmPP79dPDxMJNLez98zxaw1rVd8MhAk39jnadwK3skj4na','gerente','2025-06-09 08:14:01'),(21,'chris','chris@mambosystem95.com','$2y$10$lZpK3vnj6ehEbpywZLdc7eNpidQcm86pdo6eRLXjxONzdXuC2WTYG','gerente','2025-06-09 11:27:59'),(22,'Edilson','edilson@mambosystem95.com','$2y$10$FkpzfQvnmsXBR2hXWOxGUOpw8y2PzRD4MR1rMHzAeoTutKw7jwYku','supervisor','2025-06-10 08:03:22'),(26,'Edson Mambo','edsonmambo@mambosystem95.com','$2y$10$2/jMIk5Xj2uWe7tMRxfH6exi06oUYsCAWqT7mCo30kfgkZtJibmKe','teka_away','2025-06-20 15:42:04');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vales`
--

DROP TABLE IF EXISTS `vales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_nome` varchar(100) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_pago` decimal(10,2) DEFAULT 0.00,
  `data_registro` datetime DEFAULT current_timestamp(),
  `status` enum('aberto','pago','parcelado') DEFAULT 'aberto',
  `observacao` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `vales_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vales`
--

LOCK TABLES `vales` WRITE;
/*!40000 ALTER TABLE `vales` DISABLE KEYS */;
INSERT INTO `vales` VALUES (2,'',300.00,0.00,'2025-06-11 13:09:29','aberto',NULL,19,NULL),(3,'',65.00,0.00,'2025-06-11 13:10:50','aberto',NULL,19,NULL);
/*!40000 ALTER TABLE `vales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendas`
--

DROP TABLE IF EXISTS `vendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `metodo_pagamento` varchar(20) DEFAULT NULL,
  `data_venda` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) DEFAULT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `troco` decimal(10,2) NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendas`
--

LOCK TABLES `vendas` WRITE;
/*!40000 ALTER TABLE `vendas` DISABLE KEYS */;
INSERT INTO `vendas` VALUES (3,19,NULL,'2025-06-08 08:31:19',550.00,600.00,50.00,'2025-06-08 10:31:19'),(4,19,NULL,'2025-06-08 08:43:05',165.00,200.00,35.00,'2025-06-08 10:43:05'),(5,19,NULL,'2025-06-08 08:44:37',65.00,100.00,35.00,'2025-06-08 10:44:37'),(6,19,NULL,'2025-06-08 08:45:48',65.00,100.00,35.00,'2025-06-08 10:45:48'),(7,19,NULL,'2025-06-08 08:52:04',50.00,100.00,50.00,'2025-06-08 10:52:04'),(8,19,NULL,'2025-06-08 12:11:30',65.00,100.00,35.00,'2025-06-08 14:11:30'),(9,19,NULL,'2025-06-08 12:21:08',110.00,120.00,10.00,'2025-06-08 14:21:08'),(10,19,NULL,'2025-06-09 06:56:24',115.00,120.00,5.00,'2025-06-09 08:56:24'),(11,19,NULL,'2025-06-09 07:07:33',65.00,100.00,35.00,'2025-06-09 09:07:33'),(12,19,NULL,'2025-06-09 07:13:51',130.00,200.00,70.00,'2025-06-09 09:13:51'),(13,19,NULL,'2025-06-09 07:35:18',50.50,60.00,9.50,'2025-06-09 09:35:18'),(14,12,NULL,'2025-06-09 07:59:39',420.50,500.00,79.50,'2025-06-09 09:59:39'),(15,19,NULL,'2025-06-10 11:39:40',1615.00,1700.00,85.00,'2025-06-10 13:39:40'),(16,19,NULL,'2025-06-10 11:40:12',50.00,100.00,50.00,'2025-06-10 13:40:12'),(17,19,NULL,'2025-06-10 11:44:58',1625.00,1700.00,75.00,'2025-06-10 13:44:58'),(18,19,NULL,'2025-06-10 15:06:39',50.00,100.00,50.00,'2025-06-10 17:06:39'),(19,12,NULL,'2025-06-11 06:39:57',260.00,300.00,40.00,'2025-06-11 08:39:57'),(20,12,NULL,'2025-06-11 06:52:50',100.00,200.00,100.00,'2025-06-11 08:52:50'),(21,19,NULL,'2025-06-12 07:25:54',160.00,200.00,40.00,'2025-06-12 09:25:54'),(22,19,NULL,'2025-06-12 11:35:23',115.00,200.00,85.00,'2025-06-12 13:35:23'),(23,19,NULL,'2025-06-12 11:35:50',60.00,100.00,40.00,'2025-06-12 13:35:50'),(24,19,NULL,'2025-06-12 11:36:08',1500.00,2000.00,500.00,'2025-06-12 13:36:08'),(25,19,NULL,'2025-06-12 06:54:34',1600.00,2000.00,400.00,'2025-06-12 08:54:34'),(26,19,NULL,'2025-06-12 20:44:43',250.00,500.00,250.00,'2025-06-12 22:44:43'),(27,19,NULL,'2025-06-12 20:45:06',75.00,100.00,25.00,'2025-06-12 22:45:06'),(28,NULL,NULL,'2025-06-14 08:09:55',0.00,0.00,0.00,'2025-06-14 10:09:55'),(30,19,NULL,'2025-06-15 12:50:51',130.00,130.00,0.00,'2025-06-15 14:50:51'),(31,19,NULL,'2025-06-15 12:51:21',120.00,120.00,0.00,'2025-06-15 14:51:21'),(32,19,NULL,'2025-06-15 12:55:51',290.00,290.00,0.00,'2025-06-15 14:55:51'),(33,19,NULL,'2025-06-15 13:00:57',45.00,45.00,0.00,'2025-06-15 15:00:57'),(34,19,NULL,'2025-06-15 13:15:07',385.00,385.00,0.00,'2025-06-15 15:15:07'),(35,19,NULL,'2025-06-15 13:16:24',230.00,230.00,0.00,'2025-06-15 15:16:24'),(36,19,NULL,'2025-06-15 14:08:04',320.00,320.00,0.00,'2025-06-15 16:08:04'),(37,12,NULL,'2025-06-15 15:10:47',140.00,200.00,60.00,'2025-06-15 17:10:47'),(38,12,NULL,'2025-06-15 15:15:28',280.00,400.00,120.00,'2025-06-15 17:15:28'),(39,12,NULL,'2025-06-15 15:23:07',25.00,25.00,0.00,'2025-06-15 17:23:07'),(40,12,NULL,'2025-06-15 15:30:30',50.00,100.00,50.00,'2025-06-15 17:30:30'),(41,19,NULL,'2025-06-15 15:43:04',314.00,314.00,0.00,'2025-06-15 17:43:04'),(42,19,NULL,'2025-06-15 15:44:33',120.00,120.00,0.00,'2025-06-15 17:44:33'),(43,12,NULL,'2025-06-15 15:49:15',50.00,100.00,50.00,'2025-06-15 17:49:15'),(44,12,NULL,'2025-06-15 15:53:33',155.00,155.00,0.00,'2025-06-15 17:53:33'),(45,12,NULL,'2025-06-15 16:13:08',120.00,120.00,0.00,'2025-06-15 18:13:08'),(46,12,NULL,'2025-06-15 16:18:01',27.00,27.00,0.00,'2025-06-15 18:18:01'),(47,12,NULL,'2025-06-15 16:42:01',7.00,7.00,0.00,'2025-06-15 18:42:01'),(48,12,NULL,'2025-06-15 16:42:23',5.00,10.00,5.00,'2025-06-15 18:42:23'),(49,12,NULL,'2025-06-15 17:00:35',50.00,50.00,0.00,'2025-06-15 19:00:35'),(50,12,NULL,'2025-06-15 17:07:59',10.00,10.00,0.00,'2025-06-15 19:07:59'),(51,12,NULL,'2025-06-15 17:39:31',25.00,25.00,0.00,'2025-06-15 19:39:31'),(52,12,NULL,'2025-06-15 17:44:22',280.00,280.00,0.00,'2025-06-15 19:44:22'),(53,12,NULL,'2025-06-15 17:53:52',120.00,120.00,0.00,'2025-06-15 19:53:52'),(54,12,NULL,'2025-06-15 17:57:30',100.00,100.00,0.00,'2025-06-15 19:57:30'),(55,12,NULL,'2025-06-15 18:32:39',1335.00,1335.00,0.00,'2025-06-15 20:32:39'),(56,12,NULL,'2025-06-15 19:00:35',5.00,5.00,0.00,'2025-06-15 21:00:35'),(57,12,NULL,'2025-06-15 19:00:58',120.00,120.00,0.00,'2025-06-15 21:00:58'),(58,12,NULL,'2025-06-15 19:18:55',7.00,7.00,0.00,'2025-06-15 21:18:55'),(59,19,NULL,'2025-06-15 19:31:22',60.00,60.00,0.00,'2025-06-15 21:31:22'),(60,12,NULL,'2025-06-15 20:03:27',195.00,195.00,0.00,'2025-06-15 22:03:27'),(61,12,NULL,'2025-06-15 20:04:47',120.00,200.00,80.00,'2025-06-15 22:04:47'),(62,12,NULL,'2025-06-15 21:15:34',25.00,25.00,0.00,'2025-06-15 23:15:34'),(63,12,NULL,'2025-06-15 21:27:51',270.00,270.00,0.00,'2025-06-15 23:27:51'),(64,19,NULL,'2025-06-16 04:35:07',5.00,5.00,0.00,'2025-06-16 06:35:07'),(65,19,NULL,'2025-06-16 04:36:27',5.00,5.00,0.00,'2025-06-16 06:36:27'),(66,19,NULL,'2025-06-16 07:23:50',120.00,120.00,0.00,'2025-06-16 09:23:50'),(67,19,NULL,'2025-06-16 07:37:34',139.00,139.00,0.00,'2025-06-16 09:37:34'),(68,19,NULL,'2025-06-16 07:50:36',85.00,85.00,0.00,'2025-06-16 09:50:36'),(69,19,NULL,'2025-06-16 12:48:10',85.00,85.00,0.00,'2025-06-16 14:48:10'),(70,19,NULL,'2025-06-16 12:49:56',120.00,120.00,0.00,'2025-06-16 14:49:56'),(71,19,NULL,'2025-06-16 16:54:41',60.00,60.00,0.00,'2025-06-16 18:54:41'),(72,19,NULL,'2025-06-16 17:05:20',34.00,34.00,0.00,'2025-06-16 19:05:20'),(73,19,NULL,'2025-06-16 17:16:57',260.00,260.00,0.00,'2025-06-16 19:16:57'),(74,19,NULL,'2025-06-16 17:20:04',170.00,170.00,0.00,'2025-06-16 19:20:04'),(75,19,NULL,'2025-06-16 17:29:47',20.00,20.00,0.00,'2025-06-16 19:29:47'),(76,19,NULL,'2025-06-17 06:17:12',170.00,170.00,0.00,'2025-06-17 08:17:12'),(77,19,NULL,'2025-06-17 09:06:21',60.00,60.00,0.00,'2025-06-17 11:06:21'),(78,19,NULL,'2025-06-17 10:48:39',348.00,348.00,0.00,'2025-06-17 12:48:39'),(79,19,NULL,'2025-06-17 11:32:41',85.00,85.00,0.00,'2025-06-17 13:32:41'),(80,19,NULL,'2025-06-17 11:35:01',160.00,160.00,0.00,'2025-06-17 13:35:01'),(81,19,NULL,'2025-06-17 11:37:54',100.00,100.00,0.00,'2025-06-17 13:37:54'),(82,19,NULL,'2025-06-17 11:47:53',100.00,100.00,0.00,'2025-06-17 13:47:53'),(83,19,NULL,'2025-06-17 11:49:20',50.00,50.00,0.00,'2025-06-17 13:49:20'),(84,19,NULL,'2025-06-17 11:50:59',80.00,80.00,0.00,'2025-06-17 13:50:59'),(85,19,NULL,'2025-06-17 12:59:03',275.00,275.00,0.00,'2025-06-17 14:59:03'),(86,19,NULL,'2025-06-17 13:03:39',140.00,140.00,0.00,'2025-06-17 15:03:39'),(87,19,NULL,'2025-06-17 13:06:39',40.00,40.00,0.00,'2025-06-17 15:06:39'),(88,19,NULL,'2025-06-17 13:34:53',10.00,10.00,0.00,'2025-06-17 15:34:53'),(89,19,NULL,'2025-06-17 14:39:33',1215.00,1215.00,0.00,'2025-06-17 16:39:33'),(90,19,NULL,'2025-06-17 14:51:22',334.00,400.00,66.00,'2025-06-17 16:51:22'),(91,19,NULL,'2025-06-17 14:52:40',75.00,100.00,25.00,'2025-06-17 16:52:40'),(92,19,NULL,'2025-06-17 15:07:03',15.00,15.00,0.00,'2025-06-17 17:07:03'),(93,19,NULL,'2025-06-17 15:48:00',60.00,60.00,0.00,'2025-06-17 17:48:00'),(94,19,NULL,'2025-06-17 15:51:57',100.00,100.00,0.00,'2025-06-17 17:51:57'),(95,19,NULL,'2025-06-17 15:59:38',4.00,5.00,1.00,'2025-06-17 17:59:38'),(96,19,NULL,'2025-06-17 16:30:54',15.00,15.00,0.00,'2025-06-17 18:30:54'),(97,19,NULL,'2025-06-17 16:31:27',120.00,120.00,0.00,'2025-06-17 18:31:27'),(98,19,NULL,'2025-06-17 16:54:57',50.00,50.00,0.00,'2025-06-17 18:54:57'),(99,19,NULL,'2025-06-17 16:55:20',60.00,60.00,0.00,'2025-06-17 18:55:20'),(100,19,NULL,'2025-06-17 17:07:59',120.00,120.00,0.00,'2025-06-17 19:07:59'),(101,19,NULL,'2025-06-17 17:08:25',50.00,50.00,0.00,'2025-06-17 19:08:25'),(102,19,NULL,'2025-06-17 17:25:39',100.00,100.00,0.00,'2025-06-17 19:25:39'),(103,19,NULL,'2025-06-17 19:05:15',50.00,50.00,0.00,'2025-06-17 21:05:15'),(104,19,NULL,'2025-06-17 20:06:56',60.00,60.00,0.00,'2025-06-17 22:06:56'),(105,19,NULL,'2025-06-18 10:24:12',170.00,170.00,0.00,'2025-06-18 12:24:12'),(106,19,NULL,'2025-06-18 10:24:31',500.00,500.00,0.00,'2025-06-18 12:24:31'),(107,19,NULL,'2025-06-18 10:53:42',85.00,85.00,0.00,'2025-06-18 12:53:42'),(108,19,NULL,'2025-06-18 10:57:45',140.00,140.00,0.00,'2025-06-18 12:57:45'),(109,12,NULL,'2025-06-18 19:30:39',130.00,130.00,0.00,'2025-06-18 21:30:39'),(110,12,NULL,'2025-06-18 19:32:01',300.00,300.00,0.00,'2025-06-18 21:32:01'),(111,12,NULL,'2025-06-18 19:38:37',240.00,240.00,0.00,'2025-06-18 21:38:37'),(112,12,NULL,'2025-06-18 19:45:26',100.00,100.00,0.00,'2025-06-18 21:45:26'),(113,12,NULL,'2025-06-18 19:46:09',150.00,150.00,0.00,'2025-06-18 21:46:09'),(114,12,NULL,'2025-06-18 19:48:37',10.00,10.00,0.00,'2025-06-18 21:48:37'),(115,12,NULL,'2025-06-18 19:49:02',5.00,5.00,0.00,'2025-06-18 21:49:02'),(116,12,NULL,'2025-06-18 19:49:41',100.00,100.00,0.00,'2025-06-18 21:49:41'),(117,12,NULL,'2025-06-18 19:51:10',295.00,295.00,0.00,'2025-06-18 21:51:10'),(118,12,NULL,'2025-06-18 19:52:49',280.00,280.00,0.00,'2025-06-18 21:52:49'),(119,12,NULL,'2025-06-18 19:56:37',390.00,390.00,0.00,'2025-06-18 21:56:37'),(120,12,NULL,'2025-06-18 20:02:29',300.00,300.00,0.00,'2025-06-18 22:02:29'),(121,12,NULL,'2025-06-18 20:03:57',80.00,80.00,0.00,'2025-06-18 22:03:57'),(122,12,NULL,'2025-06-18 20:20:10',75.00,75.00,0.00,'2025-06-18 22:20:10'),(123,19,NULL,'2025-06-18 22:37:11',105.00,200.00,95.00,'2025-06-19 00:37:11'),(124,19,NULL,'2025-06-18 22:39:01',3055.00,3055.00,0.00,'2025-06-19 00:39:01'),(125,19,NULL,'2025-06-18 22:42:55',1560.00,2000.00,440.00,'2025-06-19 00:42:55'),(126,19,NULL,'2025-06-18 23:12:22',3000.00,3000.00,0.00,'2025-06-19 01:12:22'),(127,19,'dinheiro','2025-06-20 14:20:39',315.00,500.00,185.00,'2025-06-20 16:20:39'),(128,19,'mpesa','2025-06-20 14:21:19',110.00,200.00,90.00,'2025-06-20 16:21:19'),(129,19,'mpesa','2025-06-20 14:26:35',110.00,200.00,90.00,'2025-06-20 16:26:35'),(130,19,'emola','2025-06-20 14:27:08',4500.00,5000.00,500.00,'2025-06-20 16:27:08'),(131,19,'dinheiro','2025-06-20 14:28:28',1550.00,2000.00,450.00,'2025-06-20 16:28:28'),(132,19,'mpesa','2025-06-20 14:34:10',260.00,300.00,40.00,'2025-06-20 16:34:10'),(133,19,'emola','2025-06-20 14:37:34',160.00,200.00,40.00,'2025-06-20 16:37:34'),(134,19,'emola','2025-06-20 15:13:19',1272.00,2000.00,728.00,'2025-06-20 17:13:19'),(135,19,'mpesa','2025-06-20 17:31:51',155.00,200.00,45.00,'2025-06-20 19:31:51'),(136,19,'emola','2025-06-20 17:34:48',50.00,100.00,50.00,'2025-06-20 19:34:48');
/*!40000 ALTER TABLE `vendas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendas_takeaway`
--

DROP TABLE IF EXISTS `vendas_takeaway`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendas_takeaway` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_venda` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `troco` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendas_takeaway`
--

LOCK TABLES `vendas_takeaway` WRITE;
/*!40000 ALTER TABLE `vendas_takeaway` DISABLE KEYS */;
INSERT INTO `vendas_takeaway` VALUES (1,'2025-06-14 10:14:20',0.00,NULL,NULL),(2,'2025-06-14 10:14:29',0.00,NULL,NULL),(5,'2025-06-14 11:07:06',1060.00,1500.00,440.00),(6,'2025-06-14 11:25:41',560.00,600.00,40.00),(7,'2025-06-14 11:44:34',1060.00,2000.00,940.00),(8,'2025-06-20 17:54:27',1060.00,2000.00,940.00);
/*!40000 ALTER TABLE `vendas_takeaway` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendas_teka_away`
--

DROP TABLE IF EXISTS `vendas_teka_away`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendas_teka_away` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `data_venda` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendas_teka_away`
--

LOCK TABLES `vendas_teka_away` WRITE;
/*!40000 ALTER TABLE `vendas_teka_away` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendas_teka_away` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-20 20:10:41
