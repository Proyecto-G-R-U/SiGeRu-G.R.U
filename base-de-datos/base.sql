-- ============================================================
-- SiGeRU — Base de datos completa (modelo físico)
-- Motor: MySQL 8 / MariaDB (XAMPP)
--
-- Este script crea la base y todas las tablas del sistema, alineadas
-- con el código real (usuarios con herencia, cuadrillas, contenedores,
-- camiones y flotas, con todas sus relaciones).
--
-- Para cargarlo en XAMPP:
--   Opción A (phpMyAdmin): pestaña "Importar" y elegir este archivo.
--   Opción B (consola):    mysql -u root < base.sql
-- ============================================================

DROP DATABASE IF EXISTS sigeru;
CREATE DATABASE sigeru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigeru;

-- ------------------------------------------------------------
-- FLOTA — agrupa camiones
-- ------------------------------------------------------------
CREATE TABLE flota (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nombre  VARCHAR(100) NOT NULL,
    zona    VARCHAR(100) NULL
);

-- ------------------------------------------------------------
-- CUADRILLA — agrupa operarios de recolección
-- ------------------------------------------------------------
CREATE TABLE cuadrilla (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nombre  VARCHAR(100) NOT NULL,
    zona    VARCHAR(100) NULL
);

-- ------------------------------------------------------------
-- USUARIO — jerarquía Usuario/Administrador/Operario/Vecino
-- Estrategia: tabla única + columna "rol" (single table inheritance).
-- Los campos "especialidad" y "cuadrilla_id" solo aplican a operarios
-- (quedan NULL en los demás roles).
-- ------------------------------------------------------------
CREATE TABLE usuario (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    rol           ENUM('administrador','operario','vecino') NOT NULL,
    especialidad  ENUM('recoleccion','clasificacion','vertedero') NULL,
    cuadrilla_id  INT NULL,
    CONSTRAINT fk_usuario_cuadrilla
        FOREIGN KEY (cuadrilla_id) REFERENCES cuadrilla(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- CONTENEDOR — con ubicación en texto y coordenadas para el mapa
-- ------------------------------------------------------------
CREATE TABLE contenedor (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(20)  NOT NULL UNIQUE,
    direccion    VARCHAR(200) NOT NULL,
    tipo_residuo ENUM('mezclado','reciclable') NOT NULL,
    estado       ENUM('operativo','roto','desbordado') NOT NULL DEFAULT 'operativo',
    lat          DECIMAL(10,7) NULL,
    lng          DECIMAL(10,7) NULL
);

-- ------------------------------------------------------------
-- CAMION — pertenece a una flota y puede tener una cuadrilla asignada
-- ------------------------------------------------------------
CREATE TABLE camion (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    patente        VARCHAR(20)  NOT NULL UNIQUE,
    modelo         VARCHAR(100) NOT NULL,
    estado         ENUM('operativo','mantenimiento') NOT NULL DEFAULT 'operativo',
    disponibilidad ENUM('disponible','en_ruta') NOT NULL DEFAULT 'disponible',
    flota_id       INT NULL,
    cuadrilla_id   INT NULL,
    CONSTRAINT fk_camion_flota
        FOREIGN KEY (flota_id) REFERENCES flota(id) ON DELETE SET NULL,
    CONSTRAINT fk_camion_cuadrilla
        FOREIGN KEY (cuadrilla_id) REFERENCES cuadrilla(id) ON DELETE SET NULL
);

-- ============================================================
-- DATOS DE PRUEBA de las tablas sin contraseñas.
-- Los USUARIOS no se insertan acá: los crea el código PHP la primera
-- vez (si la tabla usuario está vacía), para que el hash de la
-- contraseña '1234' sea válido y generado por password_hash().
-- Ver RepositorioUsuarios::sembrarSiVacio().
-- ============================================================

INSERT INTO flota (id, nombre, zona) VALUES
    (1, 'Flota Norte', 'Zona Norte'),
    (2, 'Flota Sur',   'Zona Sur');

INSERT INTO cuadrilla (id, nombre, zona) VALUES
    (1, 'Cuadrilla Norte', 'Zona Norte');

INSERT INTO contenedor (id, codigo, direccion, tipo_residuo, estado, lat, lng) VALUES
    (1, 'CNT-102', 'Av. Italia y Bulevar Artigas', 'reciclable', 'operativo',  -34.9020, -56.1550),
    (2, 'CNT-403', 'Rambla y Buceo',               'mezclado',   'desbordado', -34.9110, -56.1360),
    (3, 'CNT-210', 'Pocitos, 26 de Marzo',         'reciclable', 'operativo',  -34.9095, -56.1520);

INSERT INTO camion (id, patente, modelo, estado, disponibilidad, flota_id, cuadrilla_id) VALUES
    (1, 'STP 1234', 'Volvo FE',       'operativo',     'en_ruta',    1, 1),
    (2, 'STP 5678', 'Mercedes Atego', 'operativo',     'disponible', 1, NULL),
    (3, 'STP 9012', 'Iveco Tector',   'mantenimiento', 'disponible', 2, NULL);
