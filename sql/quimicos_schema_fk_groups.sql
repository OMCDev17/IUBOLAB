-- Migracion chemicals: grupo_owner -> grupo_owner_id (FK a groups.id)
USE mayhem_db;

CREATE TABLE IF NOT EXISTS chemicals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cas_nr VARCHAR(64) NOT NULL,
    proveedor VARCHAR(150) NOT NULL,
    nombre VARCHAR(180) NOT NULL,
    grupo_owner_id INT NOT NULL,
    localizacion VARCHAR(180) NOT NULL,
    cantidad DECIMAL(12,2) NOT NULL DEFAULT 0,
    acceso ENUM('publico','privado') NOT NULL DEFAULT 'publico',
    grupo_privado_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_grupo_owner_id (grupo_owner_id),
    INDEX idx_grupo_privado_id (grupo_privado_id),
    CONSTRAINT fk_chemicals_owner_group FOREIGN KEY (grupo_owner_id) REFERENCES groups(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_chemicals_private_group FOREIGN KEY (grupo_privado_id) REFERENCES groups(id)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chemicals_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chemical_id INT NOT NULL,
    action_type ENUM('update_cantidad','prestamo','create','update_full') NOT NULL,
    cantidad_anterior DECIMAL(12,2) NULL,
    cantidad_nueva DECIMAL(12,2) NULL,
    cantidad_modificada DECIMAL(12,2) NULL,
    usuario_id INT NULL,
    usuario_nombre VARCHAR(180) NULL,
    usuario_rol VARCHAR(50) NULL,
    detalle TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chemical (chemical_id),
    INDEX idx_created (created_at),
    CONSTRAINT fk_chemicals_log_chemical FOREIGN KEY (chemical_id) REFERENCES chemicals(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
