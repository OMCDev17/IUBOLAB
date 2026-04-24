CREATE DATABASE IF NOT EXISTS iubolab_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE iubolab_db;

CREATE TABLE IF NOT EXISTS app_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol VARCHAR(30) NOT NULL DEFAULT 'empleado',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chemical_products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(160) NOT NULL,
  cantidad INT NOT NULL DEFAULT 0,
  unidad VARCHAR(20) NOT NULL DEFAULT 'unidades',
  UNIQUE KEY uq_chemical_products_nombre (nombre),
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_update_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id INT NULL,
  username VARCHAR(120) NOT NULL,
  old_quantity INT NOT NULL,
  new_quantity INT NOT NULL,
  changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES chemical_products(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_users (username, password, rol) VALUES
('admin', '123456', 'admin'),
('supervisor', '123456', 'supervisor'),
('seguridad', '123456', 'seguridad'),
('empleado1', '123456', 'empleado'),
('empleado2', '123456', 'empleado')
ON DUPLICATE KEY UPDATE
  password = VALUES(password),
  rol = VALUES(rol);

INSERT INTO chemical_products (nombre, cantidad, unidad) VALUES
('Acetona', 15000, 'unidades'),
('Etanol 96%', 22000, 'unidades'),
('Metanol', 8000, 'unidades'),
('Acido clorhidrico', 12000, 'unidades'),
('Acido acetico', 10000, 'unidades'),
('Hidroxido de sodio', 5000, 'unidades'),
('Cloruro de sodio', 12000, 'unidades'),
('Sulfato de cobre', 3000, 'unidades'),
('Peroxido de hidrogeno', 9000, 'unidades'),
('Hexano', 7000, 'unidades')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
