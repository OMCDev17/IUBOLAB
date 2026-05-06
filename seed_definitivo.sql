SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `mayhem_db`;

INSERT INTO `employees` (`id`,`nombre`,`apellidos`,`dni_pasaporte`,`username`,`password`,`fecha_nacimiento`,`email`,`foto_url`,`rol`) VALUES
(1,'Francisco','García Martínez','12345678X','francisco.garcia','franco123','1990-05-14','francisco.garcia@universidad.es','https://i.pravatar.cc/160?img=12','coordinador'),
(2,'María','López','87654321L','maria.lopez','maria123','1993-08-09','marialopez@universidad.es','https://i.pravatar.cc/160?img=15','empleado'),
(3,'Luis','Martínez','34567890P','luis.martinez','luis123','1988-11-22','luismartinez@universidad.es','https://i.pravatar.cc/160?img=48','empleado'),
(4,'Ana','Gómez','56789012Q','ana.gomez','ana123','1995-03-30','anagomez@universidad.es','https://i.pravatar.cc/160?img=33','empleado'),
(5,'Roberto','Sánchez','23456789M','roberto.sanchez','coord123','1982-10-17','roberto.sanchez@universidad.es','https://i.pravatar.cc/160?img=5','coordinador'),
(6,'Laura','Pérez','45678901N','laura.perez','seguridad123','1986-07-08','laura.perez@universidad.es','https://i.pravatar.cc/160?img=30','seguridad'),
(7,'Admin','Principal','00000000T','admin','admin123','1980-01-01','admin@universidad.es','https://i.pravatar.cc/160?img=1','admin'),
(8,'Jairo','Afonso','78787878J','j.afonso','$2y$10$kw4emAelDLZDjTnLDiZR5eRxpU5PSlOgVEWaVqpztAE6GyO6VyMwS','1991-11-14','jairito@gmail.com','https://i.pravatar.cc/160?u=jairilicious%40gmail.com','coordinador'),
(9,'Carla','Padilla','79797979X','carlita','$2y$10$8syN.mbLZW8S32sn9ema5Ogy3lfzr3p2hMCE69g3J6fPNLT9ZFzo6','2003-10-31','carlita@gmail.com','https://i.pravatar.cc/160?u=carlita%40gmail.com','empleado'),
(10,'Alexander','Piña','80808080F','alefriki','$2y$10$BFFBADkV1kEX7dVhju47N.4RUJ/8NGBSFtEXXtce6cjxl1wD4J/5i','2002-02-12','Alexito@gmail.com','https://i.pravatar.cc/160?u=Alexito%40gmail.com','empleado'),
(11,'Petra','Ioana','80808080L','petra.ioana','$2y$10$YsOU1.l3ehg2oZoVgRtSHu9/xBsqRoknmJvGxjfot8buhdrlQl1I.','1998-01-11','jairilicious@gmail.com','https://i.pravatar.cc/160?u=jairilicious%40gmail.com','empleado'),
(12,'Tere','Teresita','77447744T','tere.sita','$2y$10$LeX6W/kIjj.xKxlz3v7I6egLhjbNyRG7t3JkQOeSYw8lrhEaH10Q.','2026-04-08','teresita@gmail.com','https://i.pravatar.cc/160?u=teresita%40gmail.com','empleado'),
(14,'Nayara','Ramirez','90909090X','nayi.nayi','$2y$10$Fvax4CQc4DLnqj5cP0UcLOV8VheXeMEXAiOUL1k2mdbtok7fG8M9K','2002-02-15','nayita@gmail.com','https://i.pravatar.cc/160?u=nayita%40gmail.com','empleado'),
(15,'Octavio','Manrique','46464646J','octavio','$2y$10$gYA37ESnTtmle8.2u3qOXeL2E0O13B7nRv27VsJk.By2qTVNicy.y','1991-05-15','octavio@inventado.com','https://i.pravatar.cc/160?u=octavio%40inventado.com','empleado'),
(16,'Pikachu','Pokemon','78785252K','pikachu','$2y$10$3v68APhJIEwB7rEtfc5mSuglGz4/YOXWlLa2QpVmEjyCKC96c55fW','2026-04-02','pikachu@inventadellas.com','https://i.pravatar.cc/160?u=pikachu%40inventadellas.com','empleado'),
(17,'Fer','Suarez','12121212M','fersito','$2y$10$o0j5IPWdZYZ1OL1VmuC4Y.m5J9YDzxurZA7UZQpEnbUsQ2X13Q02u','1991-06-06','fersito@inventado.com','https://i.pravatar.cc/160?u=fersito%40inventado.com','empleado'),
(18,'Adri','Suarez','12131212N','adri','$2y$10$Mrh.hD.SyV/IGGt91xcDVO6dkbJns7IreC9knZ2eP/q8zelJhpw.O','2025-11-05','omcdentaltrainer@gmail.com','https://i.pravatar.cc/160?u=omcdentaltrainer%40gmail.com','empleado');

INSERT INTO `groups` (`id`,`name`,`deleted_at`,`created_at`,`updated_at`) VALUES
(1,'AFM-NANO',NULL,'2026-04-07 11:49:47','2026-04-07 14:48:27'),
(2,'Otros usuarios',NULL,'2026-04-07 11:49:47','2026-04-07 11:49:47'),
(4,'BIOLAB',NULL,'2026-04-07 12:26:02','2026-04-07 14:08:06'),
(5,'AMBILAB',NULL,'2026-04-07 12:29:39','2026-04-07 12:29:39'),
(6,'GEO-GLOBAL',NULL,'2026-04-07 12:29:39','2026-04-07 12:29:39'),
(7,'PRODMAR',NULL,'2026-04-07 12:29:39','2026-04-07 12:29:39'),
(8,'QUIBIONAT',NULL,'2026-04-07 12:29:39','2026-04-07 12:29:39'),
(9,'QUIMIOPLAN',NULL,'2026-04-07 12:29:39','2026-04-07 12:29:39'),
(10,'SINTESTER',NULL,'2026-04-07 12:29:39','2026-04-07 12:29:39'),
(11,'PTGAS',NULL,'2026-04-07 12:29:39','2026-04-07 12:29:39'),
(12,'ECOBERTURA',NULL,'2026-04-07 12:29:39','2026-04-07 12:29:39'),
(67,'DIRECCIONES','2026-04-08 14:21:00','2026-04-08 13:52:36','2026-04-08 14:21:00'),
(71,'DIRECCION','2026-04-09 09:19:45','2026-04-09 09:18:23','2026-04-09 09:19:45');

INSERT INTO `stays` (`id`,`employee_id`,`fecha_inicio`,`fecha_fin`,`motivo`,`group_id`,`horario`,`institucion`,`pais`,`status`,`archived_at`,`created_at`,`updated_at`) VALUES
(1,1,'2026-01-15','2027-01-14','Postdoctoral',12,0,'Universidad de Ejemplo','España','active',NULL,'2026-04-09 09:58:16','2026-04-09 13:20:16'),
(2,2,'2025-09-01','2026-04-07','Predoctoral',9,0,'Universidad de Ejemplo','España','archived','2026-04-09 12:17:35','2026-04-09 09:58:16','2026-04-09 13:20:06'),
(3,3,'2025-02-10','2026-04-07','PDI',1,1,'Universidad de Ejemplo','España','archived','2026-04-09 10:14:29','2026-04-09 09:58:16','2026-04-09 10:14:29'),
(4,4,'2026-04-07','2026-04-08','Vistante',6,1,'Universidad de Minnesota','EEUU','archived','2026-04-09 13:58:36','2026-04-09 09:58:16','2026-04-09 13:19:31'),
(5,5,'2024-09-01','2026-04-30','Coordinador de grupo',4,0,'Universidad de Ejemplo','España','active',NULL,'2026-04-09 09:58:16','2026-04-09 12:15:33'),
(6,6,'2024-01-15','2026-09-16','Seguridad',11,0,'Universidad de Ejemplo','España','active',NULL,'2026-04-09 09:58:16','2026-04-10 09:30:32'),
(7,7,'2023-01-01','2030-01-01','Administración',2,1,'Universidad de Ejemplo','España','active',NULL,'2026-04-09 09:58:16','2026-04-09 09:58:16'),
(8,8,'2026-04-01','2026-06-30','TFG',11,1,'IES César Manrique','España','active',NULL,'2026-04-09 09:58:16','2026-04-10 09:16:48'),
(9,9,'2026-04-02','2026-04-29','PDI',8,0,'Mc University','España','active',NULL,'2026-04-09 09:58:16','2026-04-09 13:17:47'),
(10,10,'2026-01-01','2026-04-01','PDI',12,1,'IES BOBOLIA','Alemania','archived','2026-04-10 11:01:58','2026-04-09 09:58:16','2026-04-10 10:01:58'),
(11,11,'2026-04-08','2026-04-30','Otros',11,0,'IES PORTEZUELO','España','active',NULL,'2026-04-09 09:58:16','2026-04-10 09:31:17'),
(12,12,'2026-04-01','2026-04-30','ERASMUS',4,0,'IES OFRA','España','active',NULL,'2026-04-09 09:58:16','2026-04-09 13:18:33'),
(13,14,'2026-04-08','2026-04-30','Predoctoral',7,1,'IES OROTAVA','España','active',NULL,'2026-04-09 09:58:16','2026-04-09 13:18:48'),
(23,4,'2026-04-09','2026-04-30','Postdoctoral',6,1,'Universidad de Minnesota','EEUU','active',NULL,'2026-04-09 12:03:48','2026-04-09 13:18:59'),
(24,2,'2026-04-09','2026-04-30','Visitante',10,1,'Universidad de Pamplona','España','active',NULL,'2026-04-09 12:19:16','2026-04-09 13:19:04'),
(25,15,'2026-04-09','2100-01-01','Predoctoral',1,1,'Universidad de Massachusetts','EEUU','active',NULL,'2026-04-09 12:34:55','2026-04-09 13:19:10'),
(26,16,'2026-04-09','2026-04-30','Predoctoral',8,1,'Universidad Pokemon','Kanto','active',NULL,'2026-04-09 12:37:31','2026-04-09 13:19:16'),
(27,10,'2026-04-10','2026-04-30','TFM',2,1,'Universidad Madrid','España','active',NULL,'2026-04-10 09:26:07','2026-04-10 09:26:07'),
(28,17,'2026-04-10','2026-04-30','TFG',7,1,'Universidad de La Laguna','España','active',NULL,'2026-04-10 09:46:49','2026-04-10 09:46:49'),
(29,18,'2026-04-10','2026-04-30','Predoctoral',8,1,'ULL','España','active',NULL,'2026-04-10 09:48:56','2026-04-10 09:48:56');

-- Tablas nuevas de quimicos: seed base vacio (listas para poblar desde aplicacion)

SET FOREIGN_KEY_CHECKS = 1;
