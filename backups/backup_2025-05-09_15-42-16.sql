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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `desperdicio`
--

LOCK TABLES `desperdicio` WRITE;
/*!40000 ALTER TABLE `desperdicio` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fornecedores`
--

LOCK TABLES `fornecedores` WRITE;
/*!40000 ALTER TABLE `fornecedores` DISABLE KEYS */;
INSERT INTO `fornecedores` VALUES (2,'João Santos Berger','avenida estrela do sul 65','(61) 94876-5461','43.125.675/4345-23','523.481.723-66','rolondoalves@gmail.com','123 teste','asdfghjk');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico_precos`
--

LOCK TABLES `historico_precos` WRITE;
/*!40000 ALTER TABLE `historico_precos` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_exportacoes`
--

LOCK TABLES `log_exportacoes` WRITE;
/*!40000 ALTER TABLE `log_exportacoes` DISABLE KEYS */;
INSERT INTO `log_exportacoes` VALUES (1,1,'vendas','csv','2025-04-08 00:18:50'),(2,1,'vendas','csv','2025-04-29 00:44:16'),(3,1,'vendas','csv','2025-05-05 03:29:24'),(4,1,'producao_planejada','csv','2025-05-05 03:29:53'),(5,1,'produtos_fornecedores','csv','2025-05-05 03:30:00'),(6,1,'pedidos_fornecedores','csv','2025-05-05 03:30:02'),(7,1,'fornecedores','csv','2025-05-05 03:30:13'),(8,1,'estoque','csv','2025-05-05 03:30:17'),(9,1,'vendas','csv','2025-05-05 03:30:28'),(10,1,'logs','csv','2025-05-05 03:30:37');
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (1,1,'Excluiu usuário \'funcionario1\' (vendedor)','2025-03-20 02:12:22'),(2,1,'Adicionou usuário \'eduardo\' (gerente)','2025-03-20 02:13:30'),(3,1,'Adicionou usuário \'marcus\' (vendedor)','2025-03-20 02:34:37'),(4,1,'Registrou venda de 100 unidade(s) de \'Pão de trigo\'','2025-03-21 02:25:09'),(5,1,'Excluiu produto \'Pão de trigo\'','2025-03-21 02:44:24'),(6,1,'Adicionou produto \'Pão de trigo\'','2025-03-21 02:45:00'),(7,1,'Adicionou produto \'pão de queijo\'','2025-03-21 02:45:27'),(8,1,'Adicionou produto \'enroladinho de salsicha\'','2025-03-21 02:46:00'),(9,1,'Adicionou produto \'sonho\'','2025-03-21 02:46:40'),(10,1,'Registrou venda de 19 unidade(s) de \'Pão de trigo\'','2025-03-21 02:47:01'),(11,1,'Registrou venda de 25 unidade(s) de \'pão de queijo\'','2025-03-21 02:47:01'),(12,1,'Registrou venda de 11 unidade(s) de \'enroladinho de salsicha\'','2025-03-21 02:47:01'),(13,1,'Registrou venda de 6 unidade(s) de \'sonho\'','2025-03-21 02:47:01'),(14,1,'Registrou produção de 3 unidade(s) de \'Pão de trigo\'','2025-03-21 04:05:42'),(15,1,'Registrou produção de 14 unidade(s) de \'Pão de trigo\'','2025-03-21 04:05:53'),(16,1,'Registrou produção de 29 unidade(s) de \'Pão de trigo\'','2025-03-21 04:06:02'),(17,1,'Registrou venda de 3 unidade(s) de \'Pão de trigo\' com Desconto de 10.00%','2025-04-03 02:26:14'),(18,1,'Registrou venda de 6 unidade(s) de \'pão de queijo\' com Desconto de 10.00%','2025-04-03 02:26:30'),(19,NULL,'Excluiu produto \'Pão de trigo\'','2025-05-04 00:59:00'),(20,NULL,'Excluiu produto \'pão de queijo\'','2025-05-04 01:04:33'),(21,NULL,'Excluiu produto \'pão de queijo\'','2025-05-04 01:04:35'),(22,NULL,'Excluiu produto \'pão de queijo\'','2025-05-04 01:04:37'),(23,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 05:21:01'),(24,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 05:21:04'),(25,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 05:21:06'),(26,NULL,'Registrou venda de 20 unidade(s) de \'pão de queijo\'','2025-05-05 06:36:32'),(27,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 06:37:12'),(28,NULL,'Excluiu produto \'pão de queijo\'','2025-05-05 06:37:15'),(29,NULL,'Adicionou produto \'pão de queijasso\' com preço inicial R$ 0,50','2025-05-05 06:37:57'),(30,NULL,'Registrou venda de 80 unidade(s) de \'pão de queijasso\'','2025-05-05 06:38:40'),(31,NULL,'Registrou produção de 40 unidade(s) de \'pão de queijasso\'','2025-05-05 06:40:30'),(32,NULL,'Excluiu produto \'Pão de trigo\'','2025-05-05 06:40:39'),(33,NULL,'Excluiu produto \'enroladinho de salsicha\'','2025-05-05 06:40:41'),(34,NULL,'Excluiu produto \'sonho\'','2025-05-05 06:40:43'),(35,NULL,'Excluiu produto \'pão de queijasso\'','2025-05-05 06:40:45'),(36,1,'Cadastrou novo fornecedor de nome Micael Fonseca','2025-05-05 08:02:32'),(37,1,'Cadastrou novo fornecedor de nome rolondo alves','2025-05-05 08:35:30'),(38,1,'Fez backup do banco de dados.','2025-05-05 08:41:08'),(39,NULL,'Adicionou produto \'Pão de trigo\' com preço inicial R$ 1,00','2025-05-05 08:54:14'),(40,NULL,'Usuário 4 cadastrou/atualizou a promoção: pão de trigo','2025-05-05 08:55:18'),(41,1,'[usuario_id] adicionou uma task: asdasd','2025-05-05 09:00:17'),(42,1,'adicionou uma task: afsda','2025-05-05 09:05:19'),(43,1,'Excluiu usuário \'eduardo\' (gerente)','2025-05-08 03:18:01'),(44,1,'Excluiu usuário \'marcus\' (vendedor)','2025-05-08 03:18:04'),(45,1,'Adicionou usuário \'rolondo alves\' (gerente)','2025-05-08 09:43:44'),(46,1,'Excluiu usuário \'rolondo alves\' (gerente)','2025-05-08 10:03:54'),(47,1,'Adicionou usuário \'rolondo alves\' (admin)','2025-05-08 10:04:26'),(48,1,'Adicionou usuário \'rolondo\' (gerente)','2025-05-09 02:52:33'),(49,1,'Editou usuário \'rolondo\' (admin)','2025-05-09 02:53:22'),(50,1,'Editou usuário \'rolondo\' (gerente)','2025-05-09 12:56:29'),(51,1,'Excluiu usuário \'rolondo\' (gerente)','2025-05-09 12:56:49'),(52,1,'Excluiu usuário \'marcus\' (vendedor)','2025-05-09 13:07:28'),(53,1,'Excluiu usuário \'eduardo\' (gerente)','2025-05-09 13:37:03'),(54,1,'Cadastrou novo fornecedor de nome João Santos Berger','2025-05-09 13:39:18');
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
INSERT INTO `pedidos_fornecedores` VALUES (1,2,'Pão de trigo',15,12.50,0.00,0.00,0.00,0.00,'confirmado','2025-05-04 00:01:21',NULL,NULL),(2,2,'pão de queijo',20,0.20,0.00,0.00,0.00,0.00,'confirmado','2025-05-04 00:58:52',NULL,NULL),(3,2,'pão de queijo',6,0.20,0.00,0.00,0.00,0.00,'confirmado','2025-05-04 01:00:20',NULL,NULL),(4,2,'pão de queijo',6,0.20,0.00,0.00,0.00,0.00,'confirmado','2025-05-04 01:04:20',NULL,NULL),(5,2,'pão de queijo',20,0.20,0.00,0.00,0.00,0.00,'confirmado','2025-05-05 02:18:28',NULL,NULL),(6,2,'pão de queijo',15,0.20,0.00,0.00,0.00,0.00,'confirmado','2025-05-05 02:19:13',NULL,NULL),(7,2,'pão de queijo',3,0.20,0.00,0.00,0.00,0.00,'confirmado','2025-05-05 02:54:22',NULL,NULL),(8,2,'pão de queijo',3,0.20,0.00,0.00,0.00,0.00,'confirmado','2025-05-05 03:06:07',NULL,NULL),(9,2,'pão de queijo',10,0.20,0.00,0.00,0.00,0.00,'confirmado','2025-05-05 05:21:42',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
INSERT INTO `produtos` VALUES (23,'Pão de trigo','',100,1.00,NULL,30,8,'2025-09-30',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos_fornecedores`
--

LOCK TABLES `produtos_fornecedores` WRITE;
/*!40000 ALTER TABLE `produtos_fornecedores` DISABLE KEYS */;
INSERT INTO `produtos_fornecedores` VALUES (2,2,'pão de queijo',0.20,NULL,NULL,NULL);
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
INSERT INTO `promocoes` VALUES (2,'pão de trigo','percentual',10.00,23,NULL,'2025-01-01','2025-12-12',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receitas`
--

LOCK TABLES `receitas` WRITE;
/*!40000 ALTER TABLE `receitas` DISABLE KEYS */;
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
  `status` enum('pendente','concluido') DEFAULT 'pendente',
  `criado_por` varchar(50) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'asdasd','qwrfadf','2025-06-15','concluido','marcus','2025-05-03 04:12:27'),(2,'asdasd','asdafdfwq','2029-06-12','concluido','admin','2025-05-05 09:00:17'),(3,'afsda','235dsfs','2027-09-09','concluido','admin','2025-05-05 09:05:19');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','123.456.789-00','admin@padaria.com','admin','$2y$10$cLZegIJV.SUQdyF.5LFgO.q5NzB5gMTxcC7o0MCYS4i/ISZjNowYG');
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

-- Dump completed on 2025-05-09 10:42:18
