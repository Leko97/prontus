-- MySQL dump 10.13  Distrib 8.4.8, for Linux (x86_64)
--
-- Host: localhost    Database: prontus
-- ------------------------------------------------------
-- Server version	8.4.8-0ubuntu0.25.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `adicionais`
--

DROP TABLE IF EXISTS `adicionais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adicionais` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produto_id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `adicionais_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adicionais`
--

LOCK TABLES `adicionais` WRITE;
/*!40000 ALTER TABLE `adicionais` DISABLE KEYS */;
INSERT INTO `adicionais` VALUES (1,1,'Bacon',2.50,1),(2,1,'Maionese',2.00,1),(3,2,'Bacon',5.00,1),(4,2,'Cheddar',2.50,1),(5,3,'calda de chocolate',2.50,1),(6,3,'paçoca',2.00,1),(7,5,'Queijo extra',2.50,1),(8,5,'Alface extra',1.00,1),(9,5,'Tomate extra',1.00,1),(10,6,'Bacon',4.00,1),(11,6,'Queijo',2.50,1),(12,7,'Queijo vegano',4.00,1),(13,7,'Avocado',5.00,1),(14,11,'Chantilly extra',3.00,1),(15,14,'Cheddar',4.00,1),(16,14,'Bacon',4.00,1),(17,14,'Catupiry',4.00,1),(18,15,'Leite condensado',2.00,1),(19,15,'Mel',2.00,1),(20,15,'Morango',3.00,1);
/*!40000 ALTER TABLE `adicionais` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `icone` varchar(10) DEFAULT '',
  `ativo` tinyint NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Hamburguer','🍽️',1,'2026-05-11 23:57:39'),(2,'Sobremesas','🍽️',1,'2026-05-11 23:58:06'),(3,'Bebidas','🍽️',1,'2026-05-11 23:58:10'),(4,'Lanches','🍔',1,'2026-05-12 00:47:21'),(5,'Porções','🍟',1,'2026-05-12 00:47:21'),(6,'Combos','🎁',1,'2026-05-12 00:47:21');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_item_extras`
--

DROP TABLE IF EXISTS `pedido_item_extras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_item_extras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_item_id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_item_id` (`pedido_item_id`),
  CONSTRAINT `pedido_item_extras_ibfk_1` FOREIGN KEY (`pedido_item_id`) REFERENCES `pedido_itens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_item_extras`
--

LOCK TABLES `pedido_item_extras` WRITE;
/*!40000 ALTER TABLE `pedido_item_extras` DISABLE KEYS */;
INSERT INTO `pedido_item_extras` VALUES (1,2,'Queijo extra'),(2,6,'Bacon extra'),(3,6,'Ovo frito'),(4,9,'Cheddar'),(5,9,'Bacon');
/*!40000 ALTER TABLE `pedido_item_extras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_item_remocoes`
--

DROP TABLE IF EXISTS `pedido_item_remocoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_item_remocoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_item_id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_item_id` (`pedido_item_id`),
  CONSTRAINT `pedido_item_remocoes_ibfk_1` FOREIGN KEY (`pedido_item_id`) REFERENCES `pedido_itens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_item_remocoes`
--

LOCK TABLES `pedido_item_remocoes` WRITE;
/*!40000 ALTER TABLE `pedido_item_remocoes` DISABLE KEYS */;
INSERT INTO `pedido_item_remocoes` VALUES (1,2,'Caio'),(2,1,'Cebola'),(3,5,'Maionese de ervas'),(4,15,'Alface'),(5,15,'Tomate');
/*!40000 ALTER TABLE `pedido_item_remocoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_item_restricoes`
--

DROP TABLE IF EXISTS `pedido_item_restricoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_item_restricoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_item_id` int NOT NULL,
  `restricao_slug` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_item_id` (`pedido_item_id`),
  CONSTRAINT `pedido_item_restricoes_ibfk_1` FOREIGN KEY (`pedido_item_id`) REFERENCES `pedido_itens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_item_restricoes`
--

LOCK TABLES `pedido_item_restricoes` WRITE;
/*!40000 ALTER TABLE `pedido_item_restricoes` DISABLE KEYS */;
INSERT INTO `pedido_item_restricoes` VALUES (1,2,'vegetariano'),(2,12,'vegano'),(3,12,'vegetariano'),(4,14,'sem-gluten');
/*!40000 ALTER TABLE `pedido_item_restricoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_itens`
--

DROP TABLE IF EXISTS `pedido_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `produto_id` int DEFAULT NULL,
  `produto_nome` varchar(200) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `quantidade` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  CONSTRAINT `pedido_itens_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_itens`
--

LOCK TABLES `pedido_itens` WRITE;
/*!40000 ALTER TABLE `pedido_itens` DISABLE KEYS */;
INSERT INTO `pedido_itens` VALUES (1,1,3,'Sorvete',5.00,1),(2,1,1,'X-Caio',9.99,2),(3,1,20,'Combo X-Burguer',39.90,1),(4,2,5,'X-Bacon',27.90,1),(5,2,17,'Batata Frita',18.90,1),(6,2,9,'Coca-Cola Lata',6.90,1),(7,3,21,'Combo Frango',42.90,1),(8,4,2,'X-Burguer',25.90,1),(9,4,10,'Suco de Laranja',9.90,1),(10,5,5,'X-Bacon',27.90,1),(11,5,17,'Batata Frita',18.90,1),(12,5,16,'Petit Gateau',22.90,1),(13,6,5,'X-Bacon',27.90,1),(14,7,7,'X-Salada',26.90,1),(15,7,13,'Limonada Suíça',14.90,1),(16,7,15,'Açaí 300ml',16.90,1),(17,8,2,'X-Burguer',25.90,1);
/*!40000 ALTER TABLE `pedido_itens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `senha` varchar(10) NOT NULL,
  `status` enum('recebido','em-preparo','pronto','finalizado') NOT NULL DEFAULT 'recebido',
  `pagamento` varchar(20) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `horario` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `preparado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_horario` (`horario`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,'#001','finalizado','cartao',24.98,'2026-05-12 00:46:41','2026-05-12 00:46:57'),(2,'#001','finalizado','dinheiro',39.90,'2026-05-11 10:05:00','2026-05-11 10:22:00'),(3,'#002','finalizado','cartao',53.70,'2026-05-11 10:18:00','2026-05-11 10:35:00'),(4,'#003','finalizado','pix',42.90,'2026-05-11 10:30:00','2026-05-11 10:48:00'),(5,'#004','pronto','cartao',32.80,'2026-05-11 11:00:00','2026-05-11 11:17:00'),(6,'#005','em-preparo','pix',69.70,'2026-05-11 11:15:00',NULL),(7,'#006','em-preparo','dinheiro',27.90,'2026-05-11 11:20:00',NULL),(8,'#007','recebido','cartao',58.70,'2026-05-11 11:28:00',NULL),(9,'#008','recebido',NULL,25.90,'2026-05-11 11:32:00',NULL);
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produto_restricoes`
--

DROP TABLE IF EXISTS `produto_restricoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produto_restricoes` (
  `produto_id` int NOT NULL,
  `restricao_slug` varchar(50) NOT NULL,
  PRIMARY KEY (`produto_id`,`restricao_slug`),
  CONSTRAINT `produto_restricoes_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produto_restricoes`
--

LOCK TABLES `produto_restricoes` WRITE;
/*!40000 ALTER TABLE `produto_restricoes` DISABLE KEYS */;
INSERT INTO `produto_restricoes` VALUES (1,'vegetariano'),(7,'vegano'),(7,'vegetariano'),(9,'sem-gluten'),(9,'sem-lactose'),(9,'vegano'),(9,'vegetariano'),(10,'sem-gluten'),(10,'sem-lactose'),(10,'vegano'),(10,'vegetariano'),(11,'sem-gluten'),(11,'vegetariano'),(12,'sem-amendoim'),(12,'sem-gluten'),(12,'sem-lactose'),(12,'vegano'),(12,'vegetariano'),(13,'sem-gluten'),(13,'vegetariano'),(14,'vegetariano'),(15,'sem-gluten'),(15,'vegano'),(15,'vegetariano'),(17,'sem-gluten'),(17,'vegano'),(17,'vegetariano');
/*!40000 ALTER TABLE `produto_restricoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos`
--

DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produtos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria_id` int NOT NULL,
  `nome` varchar(200) NOT NULL,
  `descricao` text,
  `preco` decimal(10,2) NOT NULL,
  `imagem` varchar(500) DEFAULT NULL,
  `ativo` tinyint NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`categoria_id`),
  CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
INSERT INTO `produtos` VALUES (1,1,'X-Caio','X-Caio\nContém Caio',9.99,NULL,1,'2026-05-11 23:59:14'),(2,1,'X-Burguer','Pão Carne e queijo\nSem ketchuuuuup',25.90,NULL,1,'2026-05-12 00:25:34'),(3,2,'Sorvete','Sorvete casquinha',5.00,NULL,1,'2026-05-12 00:26:13'),(4,3,'Coca Zero','Refrigerante Zero',5.00,NULL,1,'2026-05-12 00:26:38'),(5,4,'X-Bacon','Hambúrguer 180g com bacon crocante, queijo cheddar e cebola caramelizada',27.90,NULL,1,'2026-05-12 00:47:21'),(6,4,'X-Frango','Frango grelhado temperado com maionese de ervas e alface crespa',24.90,NULL,1,'2026-05-12 00:47:21'),(7,4,'X-Salada','Veggie burguer de grão-de-bico com mix de folhas e molho especial',26.90,NULL,1,'2026-05-12 00:47:21'),(8,4,'Cachorro-Quente','Salsicha grelhada com purê de batata, vinagrete e milho',14.90,NULL,1,'2026-05-12 00:47:21'),(9,3,'Coca-Cola Lata','Refrigerante gelado 350ml',6.90,NULL,1,'2026-05-12 00:47:21'),(10,3,'Suco de Laranja','Suco natural espremido na hora 400ml',9.90,NULL,1,'2026-05-12 00:47:21'),(11,3,'Milk Shake Chocolate','Milk shake cremoso de chocolate 400ml',18.90,NULL,1,'2026-05-12 00:47:21'),(12,3,'Água Mineral','Água sem gás 500ml',4.50,NULL,1,'2026-05-12 00:47:21'),(13,3,'Limonada Suíça','Limonada cremosa com leite condensado 400ml',14.90,NULL,1,'2026-05-12 00:47:21'),(14,2,'Brownie com Sorvete','Brownie quentinho de chocolate com sorvete e calda',19.90,NULL,1,'2026-05-12 00:47:21'),(15,2,'Açaí 300ml','Açaí batido na hora com granola e banana',16.90,NULL,1,'2026-05-12 00:47:21'),(16,2,'Petit Gateau','Bolinho quente de chocolate com sorvete de baunilha',22.90,NULL,1,'2026-05-12 00:47:21'),(17,5,'Batata Frita','Porção crocante 300g com sal e ervas',18.90,NULL,1,'2026-05-12 00:47:21'),(18,5,'Onion Rings','Anéis de cebola empanados 200g com molho barbecue',19.90,NULL,1,'2026-05-12 00:47:21'),(19,5,'Nuggets','10 unidades de frango empanado com molho mostarda-mel',21.90,NULL,1,'2026-05-12 00:47:21'),(20,6,'Combo X-Burguer','X-Burguer + Batata Frita + Coca-Cola Lata',39.90,NULL,1,'2026-05-12 00:47:21'),(21,6,'Combo Frango','X-Frango + Batata Frita + Suco de Laranja',42.90,NULL,1,'2026-05-12 00:47:21');
/*!40000 ALTER TABLE `produtos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remocoes`
--

DROP TABLE IF EXISTS `remocoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remocoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produto_id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `remocoes_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remocoes`
--

LOCK TABLES `remocoes` WRITE;
/*!40000 ALTER TABLE `remocoes` DISABLE KEYS */;
INSERT INTO `remocoes` VALUES (1,1,'Cebola'),(2,1,'Caio'),(3,1,'Maionese'),(4,1,'Ketchup'),(5,2,'Cebola'),(6,2,'Ketchup'),(7,3,'calda'),(8,5,'Cebola caramelizada'),(9,5,'Maionese'),(10,6,'Alface'),(11,6,'Maionese de ervas'),(12,7,'Cebola'),(13,7,'Molho especial'),(14,8,'Purê de batata'),(15,8,'Vinagrete'),(16,8,'Milho'),(17,14,'Sal'),(18,14,'Ervas');
/*!40000 ALTER TABLE `remocoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restricoes`
--

DROP TABLE IF EXISTS `restricoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `restricoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cor` varchar(7) DEFAULT '#F39C12',
  `ativo` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restricoes`
--

LOCK TABLES `restricoes` WRITE;
/*!40000 ALTER TABLE `restricoes` DISABLE KEYS */;
INSERT INTO `restricoes` VALUES (1,'sem-gluten','Sem Glúten','#E74C3C',1),(2,'vegetariano','Vegetariano','#27AE60',1),(3,'vegano','Vegano','#2ECC71',1),(4,'sem-lactose','Sem Lactose','#3498DB',1),(5,'sem-amendoim','Sem Amendoim','#F39C12',1);
/*!40000 ALTER TABLE `restricoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(200) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `perfil` enum('admin','cozinha') NOT NULL DEFAULT 'cozinha',
  `ativo` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@prontus.app','$2y$12$jk0mzE2vORnOmIOBoUee1eT7Nw5L4mMwWLfeVeUg7KH6gFmqO4r.i','admin',1),(2,'João Cozinha','cozinha@prontus.com','$2b$10$wqQFx3EReyeveiyZr3gyxOO/Ga/VkfHGt3aYs0BrfKS2Eygq6mTOG','cozinha',1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-12  0:48:29
