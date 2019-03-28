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

--
-- Table structure for table `messages_lang`
--

DROP TABLE IF EXISTS `messages_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages_lang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(45) DEFAULT NULL,
  `es` varchar(500) DEFAULT NULL,
  `eus` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages_lang`
--

LOCK TABLES `messages_lang` WRITE;
/*!40000 ALTER TABLE `messages_lang` DISABLE KEYS */;
INSERT INTO `messages_lang` VALUES (1,'faq','FAQ: aquí están algunas de las preguntas más frecuentes.',NULL),(2,'faq_q','¿Qué tipo de TFG se puede hacer?',NULL),(3,'faq_q','¿Cuándo hay que seleccionar un TFG?',NULL),(4,'faq_q','¿Dónde está la documentación relacionada con el TFG?',NULL),(5,'faq_q','¿Qué hay que escribir en la memoria del TFG? (ejemplos)',NULL),(6,'faq_q','¿Cuál es la rúbrica de evaluación?',NULL),(7,'faq_r','Un TFG puedes ser propuesto por:\n   -Un profesor\n   -Un alumno\n   -Una empresa\n\nAdemás, los tipos de TFG que se pueden hacer son:\n   -Proyectos clásicos\n   -Memoria de desarrollo de una idea o prototipo\n   -Estudios\n\nMás informacíon en https://www.ehu.eus/documents/6810447/8782654/Normativa_bTFG+BIE_EIB_2017_cast.pdf',NULL),(8,'faq_r','Puedes seleccionar un TFG cuando quieras. Ten en cuenta que si es una propuesta propia o de una empresa, la idea tiene que ser aprobada por x profesores.\nAdemás, 2 veces al año (Octubre y Mayo) los profesores publican en egelapi https://egelapi.ehu.eus/course/view.php?id=408 algunas propuestas.',NULL),(9,'faq_r','La documentación se encuentra en https://www.ehu.eus/es/web/ingeniaritza-bilbo/graduen-araudiak .\n\nEn esta página está la normativa del TFG, las fechas de convocatorias, la portada y otros documentos relacionados con el TFG.',NULL),(10,'faq_r','En https://www.ehu.eus/documents/6810447/8782654/Normativa_bTFG+BIE_EIB_2017_cast.pdf  se explica que partes principales tiene que tener.\nAdemás, es recomendable ver proyectos anteriores que se pueden encontrar en ADDI https://addi.ehu.es/handle/10810/2017/discover?query=%22Grado+en+Ingenier%C3%ADa+Inform%C3%A1tica%22&submit=&rpp=10&sort_by=dc.date.issued_dt&order=desc.',NULL),(11,'faq_r','La rúbrica de evaluación se puede encontrar en https://www.ehu.eus/documents/6810447/8782654/Normativa_bTFG+BIE_EIB_2017_cast.pdf/447e24ec-a7e6-0e10-88ef-a4d15425beb3.',NULL),(12,'faq_q','¿Cuál es el proceso a seguir para defender el TFG?',NULL),(13,'faq_r','Flujograma',NULL),(14,'faq_q','¿Cuándo son las convocatorias?',NULL),(15,'faq_r','Al año hay 4 convocatorias: Noviembre, Febrero-Marzo, Junio-Julio y Julio-Septiembre.\nMás información en https://www.ehu.eus/documents/6810447/8782654/CONVOCATORIAS+TFG+2017-18_DEF_v2_cas.pdf/d958ee63-3759-1129-68bd-2d9603a05687.',NULL),(16,'faq_q','¿Cuándo es la siguiente convocatoria?',NULL),(17,'faq_r','La siguiente convocatoria es el ',NULL),(18,'notificacion_matricula','La fecha límite para la matrícula es ##',NULL),(19,'notificacion_secretaria','La fecha límite para la entrega en secretaría es ##',NULL),(20,'notificacion_solicitudDefensa','La fecha límite para la solicitud de la defensa es ##',NULL),(21,'notificacion_vistoBueno','La fecha límite para el visto bueno es ##',NULL),(22,'notificacion_defensa','La fecha límite para la defensa es ##',''),(23,'fws','dsfcx','zcxcz'),(24,'prueba','Esto es una prueba',''),(25,'prueba_respuesta','Esta es la respuesta\r\n1\r\n2\r\n3\r\n',''),(26,'prueba_respuesta2','Y esto sigue\r\n?',''),(27,'yop','nop','baip');
/*!40000 ALTER TABLE `messages_lang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq_question`
--

DROP TABLE IF EXISTS `faq_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faq_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` int(11) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_faq_question_text_idx` (`text`),
  CONSTRAINT `fk_faq_question_text` FOREIGN KEY (`text`) REFERENCES `messages_lang` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq_question`
--

LOCK TABLES `faq_question` WRITE;
/*!40000 ALTER TABLE `faq_question` DISABLE KEYS */;
INSERT INTO `faq_question` VALUES (1,2,1),(2,3,2),(3,4,3),(4,5,4),(5,6,5),(6,12,8),(7,14,6),(8,16,12),(9,24,100);
/*!40000 ALTER TABLE `faq_question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq_response`
--

DROP TABLE IF EXISTS `faq_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faq_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` int(11) DEFAULT NULL,
  `text` int(11) DEFAULT NULL,
  `photo` varchar(100) DEFAULT NULL,
  `video` varchar(100) DEFAULT NULL,
  `document` varchar(100) DEFAULT NULL,
  `date` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_faq_response_text_idx` (`text`),
  KEY `fk_faq_response_question_idx` (`question`),
  CONSTRAINT `fk_faq_response_question` FOREIGN KEY (`question`) REFERENCES `faq_question` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_faq_response_text` FOREIGN KEY (`text`) REFERENCES `messages_lang` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq_response`
--

LOCK TABLES `faq_response` WRITE;
/*!40000 ALTER TABLE `faq_response` DISABLE KEYS */;
INSERT INTO `faq_response` VALUES (6,1,7,'','','',NULL),(7,2,8,NULL,'',NULL,NULL),(8,3,9,NULL,NULL,NULL,NULL),(9,4,10,NULL,NULL,NULL,NULL),(10,5,11,NULL,NULL,NULL,NULL),(11,6,13,'flujograma.jpg',NULL,NULL,NULL),(13,7,15,NULL,NULL,NULL,NULL),(15,8,17,NULL,NULL,NULL,'matricula'),(16,9,25,'','','',''),(17,9,26,'','','','');
/*!40000 ALTER TABLE `faq_response` ENABLE KEYS */;
UNLOCK TABLES;
