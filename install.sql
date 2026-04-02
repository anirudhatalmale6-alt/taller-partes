CREATE DATABASE IF NOT EXISTS taller CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE taller;

CREATE TABLE operadores (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    pin         CHAR(4) NOT NULL,
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pin (pin)
) ENGINE=InnoDB;

CREATE TABLE partes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_nombre  VARCHAR(150) NOT NULL,
    cliente_apellidos VARCHAR(150) NOT NULL DEFAULT '',
    vehiculo_marca  VARCHAR(80) NOT NULL DEFAULT '',
    vehiculo_modelo VARCHAR(80) NOT NULL DEFAULT '',
    matricula       VARCHAR(20) NOT NULL DEFAULT '',
    telefono        VARCHAR(20) NOT NULL DEFAULT '',
    operador_id     INT UNSIGNED DEFAULT NULL,
    estado          ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operador_id) REFERENCES operadores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE tareas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parte_id        INT UNSIGNED NOT NULL,
    descripcion     TEXT NOT NULL,
    tiempo_estimado DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    tiempo_real     DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    observaciones   TEXT,
    cerrada         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parte_id) REFERENCES partes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE registros_tiempo (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tarea_id    INT UNSIGNED NOT NULL,
    operador_id INT UNSIGNED NOT NULL,
    minutos     DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    nota        VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tarea_id) REFERENCES tareas(id) ON DELETE CASCADE,
    FOREIGN KEY (operador_id) REFERENCES operadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE articulos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parte_id        INT UNSIGNED NOT NULL,
    descripcion     VARCHAR(255) NOT NULL,
    precio_coste    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    precio_venta    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cantidad        INT NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parte_id) REFERENCES partes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample operator for testing
INSERT INTO operadores (nombre, pin) VALUES ('Operario Demo', '1234');
