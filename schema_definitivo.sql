SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `mayhem_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `mayhem_db`;

DROP VIEW IF EXISTS `vista_usuario_estancias`;
DROP TABLE IF EXISTS `chemicals_log`;
DROP TABLE IF EXISTS `chemicals`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `stays`;
DROP TABLE IF EXISTS `groups`;
DROP TABLE IF EXISTS `employees`;

CREATE TABLE `employees` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(120) NOT NULL,
  `apellidos` VARCHAR(160) NOT NULL,
  `dni_pasaporte` VARCHAR(32) NOT NULL,
  `username` VARCHAR(80) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `fecha_nacimiento` DATE DEFAULT NULL,
  `email` VARCHAR(180) NOT NULL,
  `foto_url` VARCHAR(500) DEFAULT NULL,
  `rol` ENUM('admin','coordinador','supervisor','seguridad','empleado') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_employees_dni_pasaporte` (`dni_pasaporte`),
  UNIQUE KEY `ux_employees_username` (`username`),
  UNIQUE KEY `ux_employees_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `groups` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name_ci` VARCHAR(100) GENERATED ALWAYS AS (LCASE(`name`)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_groups_name` (`name`),
  UNIQUE KEY `ux_groups_name_ci` (`name_ci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stays` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `motivo` VARCHAR(150) DEFAULT NULL,
  `group_id` INT DEFAULT NULL,
  `horario` TINYINT(1) NOT NULL DEFAULT 1,
  `institucion` VARCHAR(255) DEFAULT NULL,
  `pais` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
  `archived_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stays_employee` (`employee_id`),
  KEY `idx_stays_status` (`status`),
  KEY `idx_stays_group` (`group_id`),
  CONSTRAINT `fk_stays_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stays_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `code` VARCHAR(4) DEFAULT NULL,
  `token` VARCHAR(64) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_user_id` (`user_id`),
  KEY `idx_password_resets_code` (`code`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chemicals` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `cas_nr` VARCHAR(64) NOT NULL,
  `proveedor` VARCHAR(150) NOT NULL,
  `nombre` VARCHAR(180) NOT NULL,
  `grupo_owner_id` INT NOT NULL,
  `localizacion` VARCHAR(180) NOT NULL,
  `cantidad` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `acceso` ENUM('publico','privado') NOT NULL DEFAULT 'publico',
  `grupo_privado_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chemicals_nombre` (`nombre`),
  KEY `idx_chemicals_grupo_owner_id` (`grupo_owner_id`),
  KEY `idx_chemicals_grupo_privado_id` (`grupo_privado_id`),
  CONSTRAINT `fk_chemicals_owner_group` FOREIGN KEY (`grupo_owner_id`) REFERENCES `groups` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_chemicals_private_group` FOREIGN KEY (`grupo_privado_id`) REFERENCES `groups` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chemicals_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `chemical_id` INT NOT NULL,
  `action_type` ENUM('update_cantidad','prestamo','create','update_full') NOT NULL,
  `cantidad_anterior` DECIMAL(12,2) DEFAULT NULL,
  `cantidad_nueva` DECIMAL(12,2) DEFAULT NULL,
  `cantidad_modificada` DECIMAL(12,2) DEFAULT NULL,
  `usuario_id` INT DEFAULT NULL,
  `usuario_nombre` VARCHAR(180) DEFAULT NULL,
  `usuario_rol` VARCHAR(50) DEFAULT NULL,
  `detalle` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chemicals_log_chemical` (`chemical_id`),
  KEY `idx_chemicals_log_created` (`created_at`),
  CONSTRAINT `fk_chemicals_log_chemical` FOREIGN KEY (`chemical_id`) REFERENCES `chemicals` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE VIEW `vista_usuario_estancias` AS
SELECT
  `e`.`id` AS `id_usuario`,
  `e`.`nombre` AS `nombre_usuario`,
  `s`.`fecha_inicio` AS `fecha_inicio`,
  `s`.`fecha_fin` AS `fecha_fin`,
  `s`.`status` AS `estado`
FROM `employees` `e`
LEFT JOIN `stays` `s` ON `e`.`id` = `s`.`employee_id`
ORDER BY `e`.`id`;

SET FOREIGN_KEY_CHECKS = 1;
