-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: panificadora_db
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
-- Current Database: `panificadora_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `panificadora_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `panificadora_db`;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_backup` datetime NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (8,'Pães'),(9,'Doces'),(10,'Salgados'),(11,'Bebidas'),(12,'Frios'),(13,'Mercearia'),(14,'Outros');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `desperdicio`
--

DROP TABLE IF EXISTS `desperdicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `desperdicio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(10) unsigned NOT NULL,
  `data` date DEFAULT curdate(),
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `desperdicio_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `desperdicio`
--

LOCK TABLES `desperdicio` WRITE;
/*!40000 ALTER TABLE `desperdicio` DISABLE KEYS */;
INSERT INTO `desperdicio` VALUES (1,25,1,'2025-05-14');
/*!40000 ALTER TABLE `desperdicio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fornecedores`
--

DROP TABLE IF EXISTS `fornecedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `endereco` text DEFAULT NULL,
  `telefone` varchar(20) NOT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `descrição` text DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fornecedores`
--

LOCK TABLES `fornecedores` WRITE;
/*!40000 ALTER TABLE `fornecedores` DISABLE KEYS */;
INSERT INTO `fornecedores` VALUES (5,'Eddie Irvine','Estrela do Sul, Chacará 10','(61) 99287-4213','43.125.675/4345-23','843.921.937-50','ed.irvine@gmail.com',NULL,NULL),(6,'Gerhard Berger','Padre Lúcio, Fazenda Boa Vista','(11) 74236-8576','32.467.543/2487-58','942.386.534-21','gr_berger@gmail.com',NULL,NULL),(7,'Alessandro Nannini','Barragem, Chacará Selvagem','(62) 98634-5876','24.976.542/3416-43','732.146.549-86','nannini1990@gmail.com',NULL,NULL),(8,'matheus','rua padre pio 123','(61) 99827-4839','82.741.997/3749-29','060.392.381-00',NULL,NULL,NULL);
/*!40000 ALTER TABLE `fornecedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historico_precos`
--

DROP TABLE IF EXISTS `historico_precos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historico_precos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `preco_antigo` decimal(10,2) NOT NULL,
  `preco_novo` decimal(10,2) NOT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `historico_precos_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historico_precos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico_precos`
--

LOCK TABLES `historico_precos` WRITE;
/*!40000 ALTER TABLE `historico_precos` DISABLE KEYS */;
INSERT INTO `historico_precos` VALUES (4,25,0.00,0.50,'2025-05-12 13:32:07',1),(5,26,0.00,0.25,'2025-05-12 13:33:41',1),(7,28,0.00,20.00,'2025-05-12 13:37:40',1),(8,29,0.00,2.00,'2025-05-12 13:38:32',1),(9,30,0.00,2.00,'2025-05-12 13:41:45',1),(10,31,0.00,1.50,'2025-05-12 13:48:42',1),(11,32,0.00,5.00,'2025-05-12 13:51:47',1),(12,33,0.00,101.00,'2025-05-12 13:57:17',1),(13,33,101.00,10.00,'2025-05-12 13:58:38',1),(14,34,0.00,3.00,'2025-05-12 14:02:34',1),(15,35,0.00,4.00,'2025-05-12 14:03:53',1),(16,36,0.00,4.00,'2025-05-12 14:06:43',1),(17,37,0.00,5.00,'2025-05-12 14:08:06',1),(18,38,0.00,4.00,'2025-05-12 14:13:04',1),(19,39,0.00,3.00,'2025-05-12 14:18:34',1),(20,40,0.00,3.00,'2025-05-12 14:19:04',1),(21,41,0.00,4.00,'2025-05-12 14:19:33',1),(22,42,0.00,2.50,'2025-05-12 14:20:46',1),(23,43,0.00,10.00,'2025-05-12 14:21:13',1),(24,44,0.00,8.00,'2025-05-12 14:22:08',1),(25,45,0.00,9.00,'2025-05-12 14:22:59',1),(26,46,0.00,1.50,'2025-05-12 14:31:51',1),(27,47,0.00,2.50,'2025-05-12 14:32:18',1),(28,48,0.00,6.00,'2025-05-12 14:34:10',1),(29,49,0.00,5.00,'2025-05-12 14:34:55',1);
/*!40000 ALTER TABLE `historico_precos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_exportacoes`
--

DROP TABLE IF EXISTS `log_exportacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_exportacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo_dados` varchar(50) NOT NULL,
  `formato` varchar(10) NOT NULL,
  `data_exportacao` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `log_exportacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_exportacoes`
--

LOCK TABLES `log_exportacoes` WRITE;
/*!40000 ALTER TABLE `log_exportacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_exportacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(255) NOT NULL,
  `data_acao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_usuario` (`usuario_id`),
  CONSTRAINT `fk_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (1,1,'Excluiu usuário \'funcionario1\' (vendedor)','2025-03-20 02:12:22'),(2,1,'Adicionou usuário \'eduardo\' (gerente)','2025-03-20 02:13:30'),(3,1,'Adicionou usuário \'marcus\' (vendedor)','2025-03-20 02:34:37'),(4,1,'Registrou venda de 100 unidade(s) de \'Pão de trigo\'','2025-03-21 02:25:09'),(5,1,'Excluiu produto \'Pão de trigo\'','2025-03-21 02:44:24'),(6,1,'Adicionou produto \'Pão de trigo\'','2025-03-21 02:45:00'),(7,1,'Adicionou produto \'pão de queijo\'','2025-03-21 02:45:27'),(8,1,'Adicionou produto \'enroladinho de salsicha\'','2025-03-21 02:46:00'),(9,1,'Adicionou produto \'sonho\'','2025-03-21 02:46:40'),(10,1,'Registrou venda de 19 unidade(s) de \'Pão de trigo\'','2025-03-21 02:47:01'),(11,1,'Registrou venda de 25 unidade(s) de \'pão de queijo\'','2025-03-21 02:47:01'),(12,1,'Registrou venda de 11 unidade(s) de \'enroladinho de salsicha\'','2025-03-21 02:47:01'),(13,1,'Registrou venda de 6 unidade(s) de \'sonho\'','2025-03-21 02:47:01'),(14,1,'Registrou produção de 3 unidade(s) de \'Pão de trigo\'','2025-03-21 04:05:42'),(15,1,'Registrou produção de 14 unidade(s) de \'Pão de trigo\'','2025-03-21 04:05:53'),(16,1,'Registrou produção de 29 unidade(s) de \'Pão de trigo\'','2025-03-21 04:06:02'),(17,1,'Registrou venda de 3 unidade(s) de \'Pão de trigo\' com Desconto de 10.00%','2025-04-03 02:26:14'),(18,1,'Registrou venda de 6 unidade(s) de \'pão de queijo\' com Desconto de 10.00%','2025-04-03 02:26:30'),(19,NULL,'Excluiu produto \'Pão de trigo\'','2025-05-04 00:59:00'),(20,NULL,'Excluiu produto \'pão de queijo\'','2025-05-04 01:04:33'),(21,NULL,'Excluiu produto \'pão de queijo\'','2025-05-04 01:04:35'),(22,NULL,'Excluiu produto \'pão de queijo\'','2025-05-04 01:04:37'),(23,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 05:21:01'),(24,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 05:21:04'),(25,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 05:21:06'),(26,NULL,'Registrou venda de 20 unidade(s) de \'pão de queijo\'','2025-05-05 06:36:32'),(27,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 06:37:12'),(28,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 06:37:15'),(29,NULL,'Adicionou produto \'pão de queijasso\' com preço inicial R$ 0,50','2025-05-05 06:37:57'),(30,NULL,'Registrou venda de 80 unidade(s) de \'pão de queijasso\'','2025-05-05 06:38:40'),(31,NULL,'Registrou produção de 40 unidade(s) de \'pão de queijasso\'','2025-05-05 06:40:30'),(32,NULL,'Excluiu produto \'Pão de trigo\'','2025-05-05 06:40:39'),(33,NULL,'Excluiu produto \'enroladinho de salsicha\'','2025-05-05 06:40:41'),(34,NULL,'Excluiu produto \'sonho\'','2025-05-05 06:40:43'),(35,NULL,'Excluiu produto \'pão de queijasso\'','2025-05-05 06:40:45'),(36,1,'Cadastrou novo fornecedor de nome Micael Fonseca','2025-05-05 08:02:32'),(37,1,'Cadastrou novo fornecedor de nome rolondo alves','2025-05-05 08:35:30'),(38,1,'Fez backup do banco de dados.','2025-05-05 08:41:08'),(39,NULL,'Adicionou produto \'Pão de trigo\' com preço inicial R$ 1,00','2025-05-05 08:54:14'),(40,NULL,'Usuário 4 cadastrou/atualizou a promoção: pão de trigo','2025-05-05 08:55:18'),(41,1,'[usuario_id] adicionou uma task: asdasd','2025-05-05 09:00:17'),(42,1,'adicionou uma task: afsda','2025-05-05 09:05:19'),(43,1,'Excluiu usuário \'eduardo\' (gerente)','2025-05-08 03:18:01'),(44,1,'Excluiu usuário \'marcus\' (vendedor)','2025-05-08 03:18:04'),(45,1,'Adicionou usuário \'rolondo alves\' (gerente)','2025-05-08 09:43:44'),(46,1,'Excluiu usuário \'rolondo alves\' (gerente)','2025-05-08 10:03:54'),(47,1,'Adicionou usuário \'rolondo alves\' (admin)','2025-05-08 10:04:26'),(48,1,'Adicionou usuário \'rolondo\' (gerente)','2025-05-09 02:52:33'),(49,1,'Editou usuário \'rolondo\' (admin)','2025-05-09 02:53:22'),(50,1,'Editou usuário \'rolondo\' (gerente)','2025-05-09 12:56:29'),(51,1,'Excluiu usuário \'rolondo\' (gerente)','2025-05-09 12:56:49'),(52,1,'Excluiu usuário \'marcus\' (vendedor)','2025-05-09 13:07:28'),(53,1,'Excluiu usuário \'eduardo\' (gerente)','2025-05-09 13:37:03'),(54,1,'Cadastrou novo fornecedor de nome João Santos Berger','2025-05-09 13:39:18'),(55,1,'Restaurou o backup do banco de dados: backup_2025-05-09_15-42-16.sql','2025-05-12 13:16:16'),(56,1,'Adicionou produto \'queijo\' com preço inicial R$ 199,00','2025-05-12 13:17:00'),(57,1,'Atualizou produto ID 24','2025-05-12 13:17:31'),(58,1,'Restaurou o backup do banco de dados: backup_2025-05-12_15-18-05.sql','2025-05-12 13:22:49'),(59,1,'Excluiu produto \'dorieoe\'','2025-05-12 13:27:13'),(60,1,'Excluiu produto \'Pão de trigo\'','2025-05-12 13:27:17'),(61,1,'Excluiu produto \'\'','2025-05-12 13:27:23'),(62,1,'Excluiu produto \'\'','2025-05-12 13:27:26'),(63,1,'Adicionou produto \'Pão de Sal\' com preço inicial R$ 0,50','2025-05-12 13:32:07'),(64,1,'Adicionou produto \'Pão de Queijo\' com preço inicial R$ 0,25','2025-05-12 13:33:41'),(65,1,'Adicionou produto \'Pão Doce\' com preço inicial R$ 0,50','2025-05-12 13:35:23'),(66,1,'Adicionou produto \'Bolos\' com preço inicial R$ 20,00','2025-05-12 13:37:40'),(67,1,'Adicionou produto \'Sonho\' com preço inicial R$ 2,00','2025-05-12 13:38:32'),(68,1,'Adicionou produto \'Empada\' com preço inicial R$ 2,00','2025-05-12 13:41:45'),(69,1,'Adicionou produto \'Rosca\' com preço inicial R$ 1,50','2025-05-12 13:48:42'),(70,1,'Atualizou produto ID 25','2025-05-12 13:50:31'),(71,1,'Adicionou produto \'Refrigerantes Lata\' com preço inicial R$ 5,00','2025-05-12 13:51:47'),(72,1,'Atualizou produto ID 31','2025-05-12 13:52:07'),(73,1,'Adicionou produto \'Refrigerantes 2L\' com preço inicial R$ 101,00','2025-05-12 13:57:18'),(74,1,'Alterou preço do produto ID 33 de R$ 101,00 para R$ 10,00','2025-05-12 13:58:38'),(75,1,'Atualizou produto ID 33','2025-05-12 13:58:38'),(76,1,'Adicionou produto \'Donuts\' com preço inicial R$ 3,00','2025-05-12 14:02:34'),(77,1,'Adicionou produto \'Brownie\' com preço inicial R$ 4,00','2025-05-12 14:03:53'),(78,1,'Adicionou produto \'Enroladinho\' com preço inicial R$ 4,00','2025-05-12 14:06:43'),(79,1,'Adicionou produto \'Enroladinho Assado\' com preço inicial R$ 5,00','2025-05-12 14:08:06'),(80,1,'Adicionou produto \'Coxinha\' com preço inicial R$ 4,00','2025-05-12 14:13:04'),(81,1,'Adicionou produto \'Presunto\' com preço inicial R$ 3,00','2025-05-12 14:18:34'),(82,1,'Adicionou produto \'Mussarela\' com preço inicial R$ 3,00','2025-05-12 14:19:04'),(83,1,'Adicionou produto \'Cheddar \' com preço inicial R$ 4,00','2025-05-12 14:19:34'),(84,1,'Adicionou produto \'Suspiro\' com preço inicial R$ 2,50','2025-05-12 14:20:46'),(85,1,'Adicionou produto \'Doritos\' com preço inicial R$ 10,00','2025-05-12 14:21:13'),(86,1,'Adicionou produto \'Cheetos\' com preço inicial R$ 8,00','2025-05-12 14:22:08'),(87,1,'Adicionou produto \'Fandangos \' com preço inicial R$ 9,00','2025-05-12 14:22:59'),(88,1,'Adicionou produto \'Café\' com preço inicial R$ 1,50','2025-05-12 14:31:51'),(89,1,'Adicionou produto \'Café Expresso\' com preço inicial R$ 2,50','2025-05-12 14:32:18'),(90,1,'Adicionou produto \'Salame\' com preço inicial R$ 6,00','2025-05-12 14:34:10'),(91,1,'Adicionou produto \'Calabresa\' com preço inicial R$ 5,00','2025-05-12 14:34:55'),(92,1,'Excluiu o fornecedor ID 2','2025-05-14 04:38:33'),(93,1,'Cadastrou novo fornecedor de nome Eddie Irvine','2025-05-14 04:41:04'),(94,1,'Cadastrou novo fornecedor de nome Gerhard Berger','2025-05-14 04:43:04'),(95,1,'Cadastrou novo fornecedor de nome Alessandro Nannini','2025-05-14 04:46:16'),(96,1,'Cadastrou novo fornecedor de nome Eddie Irvine 123','2025-05-14 04:46:42'),(97,1,'Cadastrou novo fornecedor de nome Eddie Irvine','2025-05-14 04:46:48'),(98,1,'Editou usuário \'admin\' (admin)','2025-05-14 04:48:21'),(99,1,'Adicionou usuário \'joão silva\' (gerente)','2025-05-14 04:49:48'),(100,1,'Adicionou usuário \'maria oliveira\' (admin)','2025-05-14 04:50:28'),(101,1,'Editou usuário \'maria oliveira\' (vendedor)','2025-05-14 04:50:37'),(102,1,'cadastrou uma receita: Bolo de Chocolate','2025-05-14 05:02:38'),(103,1,'Registrou desperdício de 1 unidades do produto ID 25','2025-05-14 05:03:57'),(104,1,'associou o produto Pacote de pão de queijo 2kg ao fornecedor ID 5','2025-05-14 05:05:47'),(105,1,'associou o produto Pacote de sal 30kg ao fornecedor ID 6','2025-05-14 05:07:09'),(106,1,'adicionou uma task: teste 123','2025-06-10 02:02:03'),(107,1,'editou uma task: teste 124','2025-06-10 11:51:18'),(108,1,'adicionou uma task: teste 123','2025-06-10 11:52:07'),(109,1,'editou uma task: teste 123','2025-06-10 11:52:21'),(110,1,'editou uma task: teste 123','2025-06-10 11:52:35'),(111,1,'Atualizou produto ID 25','2025-06-10 11:53:09'),(112,1,'Atualizou produto ID 25','2025-06-10 11:53:25'),(113,1,'editou uma task: teste 123','2025-06-10 11:57:46'),(114,1,'editou uma task: teste 123','2025-06-10 11:58:14'),(115,1,'Atualizou produto ID 26','2025-06-10 12:03:31'),(116,1,'Atualizou produto ID 26','2025-06-10 12:03:48'),(117,1,'adicionou uma task: test123','2025-06-10 12:24:05'),(118,1,'Cadastrou novo fornecedor de nome matheus','2025-06-10 13:21:07'),(119,1,'associou o produto Pão de Queijo ao fornecedor ID 8','2025-06-10 13:22:15'),(120,1,'Excluiu produto \'Pão Doce\'','2025-06-10 13:23:31');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos_fornecedores`
--

DROP TABLE IF EXISTS `pedidos_fornecedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedidos_fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fornecedor_id` int(11) NOT NULL,
  `nome_produto` varchar(100) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `imposto_distrital` decimal(10,2) DEFAULT 0.00,
  `imposto_nacional` decimal(10,2) DEFAULT 0.00,
  `taxa_entrega` decimal(10,2) DEFAULT 0.00,
  `outras_taxas` decimal(10,2) DEFAULT 0.00,
  `status` enum('pendente','confirmado','negado') DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_validade` date DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fornecedor_id` (`fornecedor_id`),
  KEY `fk_pedidos_categoria` (`categoria_id`),
  CONSTRAINT `fk_pedidos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedidos_fornecedores_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos_fornecedores`
--

LOCK TABLES `pedidos_fornecedores` WRITE;
/*!40000 ALTER TABLE `pedidos_fornecedores` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos_fornecedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producao_planejada`
--

DROP TABLE IF EXISTS `producao_planejada`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producao_planejada` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `quantidade_planejada` int(10) unsigned NOT NULL,
  `data_producao` date NOT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `producao_planejada_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producao_planejada`
--

LOCK TABLES `producao_planejada` WRITE;
/*!40000 ALTER TABLE `producao_planejada` DISABLE KEYS */;
/*!40000 ALTER TABLE `producao_planejada` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos`
--

DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_produto` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `quantidade` int(10) unsigned NOT NULL DEFAULT 0,
  `preco` decimal(10,2) NOT NULL,
  `imagem` varchar(255) DEFAULT NULL COMMENT 'Nome do arquivo da imagem',
  `estoque_minimo` int(10) unsigned NOT NULL DEFAULT 0,
  `categoria_id` int(11) DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_categoria` (`categoria_id`),
  KEY `fornecedor_id` (`fornecedor_id`),
  CONSTRAINT `fk_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
INSERT INTO `produtos` VALUES (25,'Pão de Sal','123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123123',100,0.50,'6821f85747c6c.jpg',20,8,'2025-10-10',NULL),(26,'Pão de Queijo','',100,0.25,'6821f8b55f74b.jpg',20,8,'2025-10-10',NULL),(28,'Bolos','',50,20.00,'6821f9a41428f.jpg',10,9,'2025-10-10',NULL),(29,'Sonho','',50,2.00,'6821f9d8ae057.jpg',10,8,'2025-10-10',NULL),(30,'Empada','',50,2.00,'6821fa99b18e3.jpg',10,10,'2025-10-10',NULL),(31,'Rosca','',80,1.50,'6821fc3a8e0b2.jpg',15,8,'2025-10-10',NULL),(32,'Refrigerantes Lata','',80,5.00,'6821fcf31baa2.jpg',15,11,'2026-02-20',NULL),(33,'Refrigerantes 2L','',40,10.00,'6821fe8e9ee88.jpg',10,11,'2026-05-10',NULL),(34,'Donuts','',50,3.00,'6821ff7a3fe91.jpg',10,9,'2025-10-10',NULL),(35,'Brownie','',50,4.00,'6821ffc91f5a3.jpg',10,9,'2025-10-10',NULL),(36,'Enroladinho','',50,4.00,'68220073b3a96.jpg',10,10,'2025-08-10',NULL),(37,'Enroladinho Assado','',30,5.00,'682200c61bbe3.jpg',10,10,'2025-10-10',NULL),(38,'Coxinha','',40,4.00,'682201efebde7.png',10,10,'2025-10-10',NULL),(39,'Presunto','',30,3.00,'6822033a8d35b.jpg',10,12,'2025-08-08',NULL),(40,'Mussarela','',30,3.00,'68220358155a2.jpg',10,12,'2025-08-08',NULL),(41,'Cheddar ','',30,4.00,'68220375d4a96.jpg',10,12,'2025-08-08',NULL),(42,'Suspiro','',50,2.50,'682203be8fc45.jpg',10,13,'2025-10-10',NULL),(43,'Doritos','',20,10.00,'682203d99be15.jpg',5,13,'2025-11-20',NULL),(44,'Cheetos','',20,8.00,'682204104bbf3.jpg',5,13,'2025-11-10',NULL),(45,'Fandangos ','',20,9.00,'6822044309a8e.jpg',5,13,'2025-11-10',NULL),(46,'Café','',30,1.50,'682206570a59e.jpg',10,11,'2026-02-10',NULL),(47,'Café Expresso','',30,2.50,'68220672165ee.jpg',10,11,'2026-02-10',NULL),(48,'Salame','',25,6.00,'682206e2331e2.jpg',10,13,'2026-02-10',NULL),(49,'Calabresa','',20,5.00,'6822070f1bf1a.jpg',10,13,'2026-02-10',NULL);
/*!40000 ALTER TABLE `produtos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos_fornecedores`
--

DROP TABLE IF EXISTS `produtos_fornecedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produtos_fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fornecedor_id` int(11) NOT NULL,
  `nome_produto` varchar(100) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `imagem` varchar(255) DEFAULT NULL COMMENT 'Nome do arquivo da imagem',
  `categoria_id` int(11) DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fornecedor_id` (`fornecedor_id`),
  KEY `fk_categoria_fornecedor` (`categoria_id`),
  CONSTRAINT `fk_categoria_fornecedor` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `produtos_fornecedores_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos_fornecedores`
--

LOCK TABLES `produtos_fornecedores` WRITE;
/*!40000 ALTER TABLE `produtos_fornecedores` DISABLE KEYS */;
INSERT INTO `produtos_fornecedores` VALUES (3,5,'Pacote de pão de queijo 2kg',15.00,NULL,12,'2025-07-07'),(4,6,'Pacote de sal 30kg',40.90,NULL,14,NULL),(5,8,'Pão de Queijo',1.50,NULL,8,'2025-09-09');
/*!40000 ALTER TABLE `produtos_fornecedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promocoes`
--

DROP TABLE IF EXISTS `promocoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promocoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('percentual','leve_pague') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `ativa` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `fk_promocoes_produto` (`produto_id`),
  CONSTRAINT `fk_promocoes_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promocoes_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promocoes_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promocoes`
--

LOCK TABLES `promocoes` WRITE;
/*!40000 ALTER TABLE `promocoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `promocoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receitas`
--

DROP TABLE IF EXISTS `receitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `ingredientes` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receitas`
--

LOCK TABLES `receitas` WRITE;
/*!40000 ALTER TABLE `receitas` DISABLE KEYS */;
INSERT INTO `receitas` VALUES (4,'Bolo de Chocolate','{\"Ovo\":\"6un\",\"Sal\":\"5g\",\"Farinha de trigo\":\"3x\\u00edc\",\"A\\u00e7\\u00facar\":\"2x\\u00edc\",\"Fermento em p\\u00f3\":\"1cs\",\"Leite condensado\":\"1lata\",\"Chocolate em p\\u00f3\":\"2cs\",\"Copo de \\u00e1gua (requeij\\u00e3o)\":\"1un\",\"Cacau em p\\u00f3 100%\":\"3cs\",\"\\u00c1gua filtrada\":\"2x\\u00edc\",\"Margarina\":\"1p\\u00e7\"}');
/*!40000 ALTER TABLE `receitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_pedido` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_entrega` date DEFAULT NULL,
  `hora_entrega` time DEFAULT NULL,
  `status` enum('pendente','concluido') DEFAULT 'pendente',
  `criado_por` varchar(50) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (4,'teste 124','111','2025-09-09','18:45:00','concluido','admin','2025-06-10 02:02:03'),(5,'teste 123','123123123123123123123123123123123123123123\r\n123123123123123123123\r\n123123123123123123123\r\n123123123123123123123\r\n123123123123123123123\r\n\r\n','2025-09-09','19:45:00','concluido','admin','2025-06-10 11:52:07'),(6,'test123','1234567890qwerty1234567890qwerty1234567890qwerty1234567890qwerty','2025-09-09','11:25:00','pendente','admin','2025-06-10 12:24:05');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `perfil` enum('admin','gerente','vendedor') NOT NULL DEFAULT 'vendedor',
  `senha` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','123.456.789-09','adminviola@padaria.com','admin','$2y$10$cLZegIJV.SUQdyF.5LFgO.q5NzB5gMTxcC7o0MCYS4i/ISZjNowYG'),(8,'joão silva','177.364.800-44','joaosilva123@gmail.com','gerente','$2y$10$wxiYzIp60h/q5ZGWvwyjtOZQHsrQXPkCZjLFbZaZV6igHO5JR1CTq'),(9,'maria oliveira','937.806.440-02','maria123qwe@gmail.com','vendedor','$2y$10$6harzv1/OUqiO3wDgIWe7OJ4VcTz4uM.vdISJGUQUEno2iOOcThIW');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendas`
--

DROP TABLE IF EXISTS `vendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `quantidade_vendida` int(10) unsigned NOT NULL,
  `data_venda` timestamp NOT NULL DEFAULT current_timestamp(),
  `preco_unitario_venda` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_produto_id` (`produto_id`),
  CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendas`
--

LOCK TABLES `vendas` WRITE;
/*!40000 ALTER TABLE `vendas` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-10 10:26:14
