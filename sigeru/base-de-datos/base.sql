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
-- INSTALACION — centros de acopio, plantas de clasificación y vertederos
-- Estrategia: tabla única + columna "tipo" (single table inheritance),
-- igual que usuario/rol. Las clases CentroAcopio, PlantaClasificacion y
-- Vertedero heredan de Instalacion y se guardan todas acá.
-- ------------------------------------------------------------
CREATE TABLE instalacion (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(120) NOT NULL,
    direccion        VARCHAR(180) NOT NULL,
    tipo             ENUM('acopio','clasificacion','vertedero') NOT NULL,
    capacidad_maxima INT NOT NULL,                     -- tope en toneladas
    ocupacion_actual INT NOT NULL DEFAULT 0,           -- toneladas ocupadas actualmente
    tipo_residuo     ENUM('mezclado','reciclable','ambos') NOT NULL DEFAULT 'mezclado',
    estado           ENUM('operativa','mantenimiento','fuera_servicio') NOT NULL DEFAULT 'operativa',
    lat              DECIMAL(10,7) NULL,                -- ubicación para el mapa
    lng              DECIMAL(10,7) NULL
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
    instalacion_id INT NULL,                    -- instalación asignada (clasificación/vertedero)
    CONSTRAINT fk_usuario_cuadrilla
        FOREIGN KEY (cuadrilla_id) REFERENCES cuadrilla(id) ON DELETE SET NULL,
    CONSTRAINT fk_usuario_instalacion
        FOREIGN KEY (instalacion_id) REFERENCES instalacion(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- CONTENEDOR — con ubicación en texto y coordenadas para el mapa
-- ------------------------------------------------------------
CREATE TABLE contenedor (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(20)  NOT NULL UNIQUE,
    direccion    VARCHAR(200) NOT NULL,
    tipo_residuo ENUM('mezclado','reciclable') NOT NULL,
    estado        ENUM('operativo','roto') NOT NULL DEFAULT 'operativo',   -- integridad física
    nivel_llenado ENUM('vacio','lleno','desbordado') NOT NULL DEFAULT 'vacio', -- cuánto tiene
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


-- ------------------------------------------------------------
-- MAQUINARIA — inventario de equipos, herramientas y repuestos.
-- Cada ítem pertenece a una instalación (si se elimina la instalación,
-- el ítem queda sin asignar en vez de borrarse).
-- ------------------------------------------------------------
CREATE TABLE maquinaria (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(120) NOT NULL,
    categoria      ENUM('maquinaria','herramienta','repuesto') NOT NULL DEFAULT 'maquinaria',
    estado         ENUM('operativa','mantenimiento','rota') NOT NULL DEFAULT 'operativa',
    cantidad       INT NOT NULL DEFAULT 1,
    instalacion_id INT NULL,
    CONSTRAINT fk_maquinaria_instalacion
        FOREIGN KEY (instalacion_id) REFERENCES instalacion(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- INCIDENCIA — problemas reportados sobre un contenedor.
-- La puede crear cualquier usuario desde el mapa; el admin le hace el
-- seguimiento (estado, cuadrilla responsable) hasta resolverla.
-- Al resolverse sale del mapa activo y queda en el historial.
-- ------------------------------------------------------------
CREATE TABLE incidencia (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    contenedor_id        INT NOT NULL,
    tipo                 ENUM('rotura','desborde','falta_recoleccion','otro') NOT NULL DEFAULT 'otro',
    descripcion          TEXT NOT NULL,
    gravedad             ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
    estado               ENUM('abierta','en_curso','resuelta') NOT NULL DEFAULT 'abierta',
    cuadrilla_id         INT NULL,
    reportado_por        INT NULL,
    reportado_por_nombre VARCHAR(100) NOT NULL DEFAULT 'Anónimo',
    fecha_reporte        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion     DATETIME NULL,
    CONSTRAINT fk_incidencia_contenedor
        FOREIGN KEY (contenedor_id) REFERENCES contenedor(id) ON DELETE CASCADE,
    CONSTRAINT fk_incidencia_cuadrilla
        FOREIGN KEY (cuadrilla_id) REFERENCES cuadrilla(id) ON DELETE SET NULL
);


-- ------------------------------------------------------------
-- RUTA — recorrido de recolección que la administración asigna a
-- una cuadrilla (requerimiento 3.1.2.1.1).
-- ------------------------------------------------------------
CREATE TABLE ruta (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(120) NOT NULL,
    zona         VARCHAR(120) NULL,
    dia_semana   ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL DEFAULT 'lunes',
    turno        ENUM('matutino','vespertino','nocturno') NOT NULL DEFAULT 'matutino',
    cuadrilla_id INT NULL,
    CONSTRAINT fk_ruta_cuadrilla
        FOREIGN KEY (cuadrilla_id) REFERENCES cuadrilla(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- RUTA_CONTENEDOR — qué contenedores recorre cada ruta y en qué orden.
-- Es una tabla intermedia: una ruta tiene muchos contenedores y un
-- contenedor puede estar en varias rutas.
-- ------------------------------------------------------------
CREATE TABLE ruta_contenedor (
    ruta_id       INT NOT NULL,
    contenedor_id INT NOT NULL,
    orden         INT NOT NULL DEFAULT 1,
    PRIMARY KEY (ruta_id, contenedor_id),
    CONSTRAINT fk_rc_ruta
        FOREIGN KEY (ruta_id) REFERENCES ruta(id) ON DELETE CASCADE,
    CONSTRAINT fk_rc_contenedor
        FOREIGN KEY (contenedor_id) REFERENCES contenedor(id) ON DELETE CASCADE
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

INSERT INTO contenedor (id, codigo, direccion, tipo_residuo, estado, nivel_llenado, lat, lng) VALUES
    (1, 'CNT-102', 'Av. Italia y Bulevar Artigas', 'reciclable', 'operativo', 'vacio',      -34.9020, -56.1550),
    (2, 'CNT-403', 'Rambla y Buceo',               'mezclado',   'operativo', 'desbordado', -34.9110, -56.1360),
    (3, 'CNT-210', 'Pocitos, 26 de Marzo',         'reciclable', 'roto',      'lleno',      -34.9095, -56.1520);

INSERT INTO camion (id, patente, modelo, estado, disponibilidad, flota_id, cuadrilla_id) VALUES
    (1, 'STP 1234', 'Volvo FE',       'operativo',     'en_ruta',    1, 1),
    (2, 'STP 5678', 'Mercedes Atego', 'operativo',     'disponible', 1, NULL),
    (3, 'STP 9012', 'Iveco Tector',   'mantenimiento', 'disponible', 2, NULL);

INSERT INTO instalacion (id, nombre, direccion, tipo, capacidad_maxima, ocupacion_actual, tipo_residuo, estado, lat, lng) VALUES
    (1, 'Centro de Acopio Centro',   'Av. Rondeau 1580',         'acopio',        150,  40, 'reciclable', 'operativa', -34.8890, -56.1880),
    (2, 'Planta Clasificadora Este', 'Camino Maldonado 5400',    'clasificacion', 300, 185, 'ambos',      'operativa', -34.8450, -56.1050),
    (3, 'Vertedero Felipe Cardoso',  'Camino Felipe Cardoso s/n','vertedero',     900, 720, 'mezclado',   'operativa', -34.8280, -56.0910);

INSERT INTO maquinaria (id, nombre, categoria, estado, cantidad, instalacion_id) VALUES
    (1, 'Cinta transportadora',      'maquinaria',  'operativa',     1, 2),
    (2, 'Prensa compactadora',       'maquinaria',  'mantenimiento', 1, 2),
    (3, 'Pala mecánica',             'maquinaria',  'operativa',     2, 3),
    (4, 'Contenedores de repuesto',  'repuesto',    'operativa',    12, 1),
    (5, 'Carretillas',               'herramienta', 'operativa',     6, 1);

INSERT INTO incidencia (id, contenedor_id, tipo, descripcion, gravedad, estado, cuadrilla_id, reportado_por_nombre, fecha_reporte) VALUES
    (1, 2, 'desborde', 'El contenedor está desbordado hace dos días y hay bolsas en la vereda.', 'alta', 'abierta',  NULL, 'Vecino de Prueba', NOW()),
    (2, 1, 'rotura',   'La tapa está rota y no cierra.',                                          'media', 'en_curso', 1,   'Marta Pérez',      NOW());

INSERT INTO ruta (id, nombre, zona, dia_semana, turno, cuadrilla_id) VALUES
    (1, 'Ruta Pocitos Mañana', 'Pocitos',   'lunes',  'matutino',   1),
    (2, 'Ruta Buceo Tarde',    'Buceo',     'martes', 'vespertino', NULL);

INSERT INTO ruta_contenedor (ruta_id, contenedor_id, orden) VALUES
    (1, 3, 1),
    (1, 1, 2),
    (2, 2, 1);
