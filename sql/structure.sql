/* Add language column to Longman's user table */
ALTER TABLE `user` ADD `language` char(10) DEFAULT 'es';

DROP TABLE IF EXISTS `TFG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TFG` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `lang` char(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_TFG_1_idx` (`user`),
  CONSTRAINT `fk_TFG_1` FOREIGN KEY (`user`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TFGversion`
--

DROP TABLE IF EXISTS `TFGversion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TFGversion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) DEFAULT NULL,
  `hash` varchar(100) DEFAULT NULL,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `TFGid` int(11) DEFAULT NULL,
  `docPath` varchar(200) DEFAULT NULL,
  `txtPath` varchar(200) DEFAULT NULL,
  `correction` varchar(200) DEFAULT NULL,
  `pdfPath` varchar(200) DEFAULT NULL,
  `imagesPath` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_TFGid_idx` (`TFGid`),
  CONSTRAINT `fk_TFGid` FOREIGN KEY (`TFGid`) REFERENCES `TFG` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TFGimage`
--

DROP TABLE IF EXISTS `TFGimage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TFGimage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `TFGversion_id` int(11) DEFAULT NULL,
  `hash` varchar(100) DEFAULT NULL,
  `path` varchar(200) DEFAULT NULL,
  `api_result` longtext,
  PRIMARY KEY (`id`),
  KEY `fk_TFG_image_1_idx` (`TFGversion_id`),
  CONSTRAINT `fk_TFG_image_1` FOREIGN KEY (`TFGversion_id`) REFERENCES `TFGversion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=298 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `sent` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notification_1_idx` (`user_id`),
  CONSTRAINT `fk_notification_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `special_notification`
--

DROP TABLE IF EXISTS `special_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `special_notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `notification_prep` tinyint(4) DEFAULT '0',
  `es` varchar(500) DEFAULT NULL,
  `eus` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_message`
--

DROP TABLE IF EXISTS `system_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(45) DEFAULT NULL,
  `es` varchar(500) DEFAULT NULL,
  `eus` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

/* Required system messages for the chatbot to give proper responses */
LOCK TABLES `system_message` WRITE;
/*!40000 ALTER TABLE `system_message` DISABLE KEYS */;
INSERT INTO `system_message` VALUES (1,'faq','FAQ: aquí están algunas de las preguntas más frecuentes.',NULL),(2,'notificacion_matricula','La fecha límite para la matrícula es ',NULL),(3,'notificacion_secretaria','La fecha límite para la entrega en secretaría es ',NULL),(4,'notificacion_solicitudDefensa','La fecha límite para la solicitud de la defensa y subir la memoria a ADDI es ',NULL),(5,'notificacion_vistoBueno','La fecha límite para el visto bueno es ',NULL),(6,'notificacion_defensa','LLa fecha límite para la defensa es ',NULL),(7,'addTFG_sinTFGregistrado','Primero tienes que registrar un TFG. Para ello puedes utilizar el comando /registerTFG',NULL),(8,'addTFG_pedirDocumento','Envía el documento de la nueva versión. Puede ser pdf, doc, docx o odt.',NULL),(9,'addTFG_versionRepetida','La versión enviada ya está añadida el ',NULL),(10,'addTFG_versionAnadidaCorrecta','La versión ha sido añadida.',NULL),(11,'addTFG_errorCrearTexto','Ha ocurrido un error. Asegurate que el archivo enviado este bien.',NULL),(12,'addTFG_extensionIncorrecta','El archivo enviado no es correcto.',NULL),(13,'registerTFG_yaHayTFGregistrado','Ya tienes un TFG registrado. Si quieres añadir una nueva versión utiliza el comando /addTFG',NULL),(14,'registerTFG_pedirTituloTFG','Escribe el título del TFG.',NULL),(15,'registerTFG_seleccionaIdioma','Selecciona el idioma del TFG',NULL),(16,'registerTFG_tfgAnadido','El TFG ha sido registrado.',NULL),(17,'registerTFG_errorAlregistrar','No se ha podido registrar el TFG.',NULL),(18,'correctTFG_errorAlCorregir','Ha habido algún error al corregir el texto',NULL),(19,'correctTFG_noHayVersiones','No tienes ningún TFG añadido. Si quieres añadir una nueva versión utiliza el comando /addTFG',NULL),(20,'correctTFG_ultimaVersion','La última versión es de la fecha: ',NULL),(21,'correctTFG_preguntarCorregir','¿Quieres corregir esta versión?',NULL),(22,'correctTFG_cancelar','Has cancelado el proceso de corrección.',NULL),(23,'correctTFG_empiezaCorreccion','A continuación se va a corregir el TFG. Esto puede tardar un poco.',NULL),(24,'correctTFG_idiomaNodisponible','Todavía no se puede corregir el idioma que tiene el TFG.',NULL),(25,'imagesTFG_mostrarImagenes','A continuación se mostraran posibles imágenes sacadas de internet. Asegurate de que tengas permiso para usarlas.',NULL),(26,'imagesTFG_infoImagen','Esta imagen se ha detectado como: ',NULL),(27,'imagesTFG_coincidencias','Se ha encontrado posibles coincidencias de esta imagen o alguna parecida en las siguientes páginas de internet:',NULL),(28,'imagesTFG_noCoincidencias','No se ha encontrado ninguna coincidencia',NULL),(29,'imagesTFG_noHayVersiones','No tienes ningún TFG añadido. Si quieres añadir una nueva versión utiliza el comando /addTFG',NULL),(30,'imagesTFG_ultimaVersion','La última versión es de la fecha: ',NULL),(31,'imagesTFG_preguntarCorregir','¿Quieres analizar las imágenes esta versión?',NULL),(32,'imagesTFG_cancelar','Has cancelado el proceso.',NULL),(33,'imagesTFG_empezarComprobacion','A continuación se van a analizar las imágenes. Esto puede tardar un poco...',NULL),(34,'correctTFG_mostrarErrores','A continuación se muestran algunos de los errores encontrados.',NULL);
/*!40000 ALTER TABLE `system_message` ENABLE KEYS */;
UNLOCK TABLES;
