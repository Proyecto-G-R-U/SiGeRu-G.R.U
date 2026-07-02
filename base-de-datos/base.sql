-- ============================================================
-- SiGeRU — Primera versión del modelo físico de la base de datos
-- Requisito Full Stack 1ra entrega: DDL + dump de estructura.
-- Motor: MySQL 8.
--
-- NOTA: en la 1ra entrega el backend NO se conecta a esta base todavía
-- (trabaja en memoria). Este script es el diseño físico que se empieza
-- a usar en la 2da entrega. Corresponde al pasaje a tablas del MER (3FN).
-- ============================================================

CREATE DATABASE IF NOT EXISTS sigeru
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sigeru;

-- ------------------------------------------------------------
-- USUARIOS
-- Modelamos la herencia (Usuario -> Administrador/Operario/Vecino)
-- con la estrategia de "tabla única + columna rol" (single table),
-- que es la más simple para empezar. Los campos propios del operario
-- (especialidad, cuadrilla) quedan NULL para los demás roles.
-- ------------------------------------------------------------
CREATE TABLE usuario (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    rol           ENUM('administrador','operario','vecino') NOT NULL,
    -- Campos específicos del operario (NULL para admin y vecino):
    especialidad  ENUM('recoleccion','clasificacion','vertedero') NULL,
    id_cuadrilla  INT NULL,
    creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- CUADRILLAS (3.1.1.1.3 / 3.1.1.1.4)
-- Una cuadrilla agrupa varios operarios. Relación 1..N con usuario
-- a través de usuario.id_cuadrilla.
-- ------------------------------------------------------------
CREATE TABLE cuadrilla (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nombre  VARCHAR(100) NOT NULL,
    zona    VARCHAR(100) NULL,
    estado  ENUM('activa','inactiva') NOT NULL DEFAULT 'activa'
);

ALTER TABLE usuario
    ADD CONSTRAINT fk_usuario_cuadrilla
    FOREIGN KEY (id_cuadrilla) REFERENCES cuadrilla(id)
    ON DELETE SET NULL;

-- ------------------------------------------------------------
-- CONTENEDORES (3.1.1.2.1 / 2.2 / 2.3)
-- ------------------------------------------------------------
CREATE TABLE contenedor (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(20)  NOT NULL UNIQUE,
    ubicacion    VARCHAR(200) NOT NULL,
    tipo_residuo ENUM('mezclado','reciclable') NOT NULL,
    estado       ENUM('operativo','roto','desbordado') NOT NULL DEFAULT 'operativo'
);

-- ------------------------------------------------------------
-- CAMIONES / FLOTA (3.1.1.2.4 / 2.5)
-- ------------------------------------------------------------
CREATE TABLE camion (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    patente        VARCHAR(20)  NOT NULL UNIQUE,
    modelo         VARCHAR(100) NOT NULL,
    estado         ENUM('operativo','mantenimiento') NOT NULL DEFAULT 'operativo',
    disponibilidad ENUM('disponible','en_ruta') NOT NULL DEFAULT 'disponible'
);
