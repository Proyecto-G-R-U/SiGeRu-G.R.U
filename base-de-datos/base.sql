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
    zona             VARCHAR(80)  NULL,                 -- municipio, igual que el contenedor
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
    token         VARCHAR(64) NULL,             -- token de sesión: se genera al iniciar sesión
                                                -- y se borra al cerrarla. Autentica cada pedido.
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
    zona         VARCHAR(80)  NULL,                                        -- municipio al que pertenece (req. 3.1.1.3.4, 3.1.4.1, 3.1.4.3)
    capacidad_litros INT NOT NULL DEFAULT 1100,                            -- 780 / 1100 / 2400: las que usa la IM (req. 3.1.4.2)
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
-- Una incidencia refiere a UN contenedor O a UNA instalación, nunca a las
-- dos ni a ninguna. Los operarios de recolección reportan sobre contenedores
-- (req. 3.1.2.1.3) y los de clasificación sobre las instalaciones donde
-- trabajan (req. 3.1.2.2.3). El CHECK de abajo impide que entre una
-- incidencia huérfana o apuntando a dos cosas a la vez.
CREATE TABLE incidencia (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    contenedor_id        INT NULL,
    instalacion_id       INT NULL,
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
    CONSTRAINT fk_incidencia_instalacion
        FOREIGN KEY (instalacion_id) REFERENCES instalacion(id) ON DELETE CASCADE,
    CONSTRAINT fk_incidencia_cuadrilla
        FOREIGN KEY (cuadrilla_id) REFERENCES cuadrilla(id) ON DELETE SET NULL,
    CONSTRAINT chk_incidencia_objeto CHECK (
        (contenedor_id IS NOT NULL AND instalacion_id IS NULL) OR
        (contenedor_id IS NULL AND instalacion_id IS NOT NULL)
    )
);


-- ------------------------------------------------------------
-- RUTA — recorrido de recolección que la administración asigna a
-- una cuadrilla (requerimiento 3.1.2.1.1).
-- ------------------------------------------------------------
-- ============================================================
--  NOTIFICACIONES
--  Avisos dentro del sistema: no hay mail ni push, el usuario los
--  ve en la campana del encabezado. Una fila por destinatario, no
--  por evento: si a una cuadrilla de tres se le asigna una
--  incidencia se crean tres notificaciones, y cada operario marca
--  la suya como leída sin afectar a los demás.
-- ============================================================
CREATE TABLE notificacion (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT NOT NULL,                      -- a quién le llega
    tipo          ENUM('incidencia_nueva','incidencia_asignada','incidencia_resuelta','aviso')
                      NOT NULL DEFAULT 'aviso',
    titulo        VARCHAR(120) NOT NULL,
    mensaje       VARCHAR(400) NOT NULL,
    gravedad      ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
    incidencia_id INT NULL,                          -- a qué incidencia refiere, si aplica
    leida         TINYINT(1) NOT NULL DEFAULT 0,
    fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE,
    -- Si se borra la incidencia, el aviso sobre ella deja de tener sentido:
    -- se va con ella en vez de quedar apuntando a la nada.
    CONSTRAINT fk_notif_incidencia
        FOREIGN KEY (incidencia_id) REFERENCES incidencia(id) ON DELETE CASCADE,
    INDEX idx_notif_usuario (usuario_id, leida)
);

CREATE TABLE ruta (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(120) NOT NULL,
    zona         VARCHAR(120) NULL,
    dia_semana   ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL DEFAULT 'lunes',
    turno        ENUM('matutino','vespertino','nocturno') NOT NULL DEFAULT 'matutino',
    cuadrilla_id INT NULL,
    instalacion_id INT NULL,                     -- destino final: dónde descarga el camión
    CONSTRAINT fk_ruta_cuadrilla
        FOREIGN KEY (cuadrilla_id) REFERENCES cuadrilla(id) ON DELETE SET NULL,
    CONSTRAINT fk_ruta_instalacion
        FOREIGN KEY (instalacion_id) REFERENCES instalacion(id) ON DELETE SET NULL
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
    (1, 'Flota Norte', 'Municipio C'),
    (2, 'Flota Sur',   'Municipio CH');

INSERT INTO cuadrilla (id, nombre, zona) VALUES
    (1, 'Cuadrilla Norte', 'Municipio C');

INSERT INTO contenedor (id, codigo, direccion, zona, capacidad_litros, tipo_residuo, estado, nivel_llenado, lat, lng) VALUES
    (1, 'CNT-102', 'Av. Italia y Bulevar Artigas', 'Municipio CH', 1100, 'reciclable', 'operativo', 'vacio',      -34.9020, -56.1550),
    (2, 'CNT-403', 'Rambla Rep. de México, Malvín','Municipio E',  2400, 'mezclado',   'operativo', 'desbordado', -34.8920, -56.1150),
    (3, 'CNT-210', 'Pocitos, 26 de Marzo',         'Municipio CH',  780, 'reciclable', 'roto',      'lleno',      -34.9095, -56.1520);

INSERT INTO camion (id, patente, modelo, estado, disponibilidad, flota_id, cuadrilla_id) VALUES
    (1, 'STP 1234', 'Volvo FE',       'operativo',     'en_ruta',    1, 1),
    (2, 'STP 5678', 'Mercedes Atego', 'operativo',     'disponible', 1, NULL),
    (3, 'STP 9012', 'Iveco Tector',   'mantenimiento', 'disponible', 2, NULL);

INSERT INTO instalacion (id, nombre, direccion, tipo, zona, capacidad_maxima, ocupacion_actual, tipo_residuo, estado, lat, lng) VALUES
    (1, 'Centro de Acopio Centro',   'Av. Rondeau 1580',         'acopio',        'Municipio C', 150,  40, 'reciclable', 'operativa', -34.8890, -56.1880),
    (2, 'Planta Clasificadora Este', 'Camino Maldonado 5400',    'clasificacion', 'Municipio F', 300, 185, 'ambos',      'operativa', -34.8450, -56.1050),
    (3, 'Vertedero Felipe Cardoso',  'Camino Felipe Cardoso s/n','vertedero',     'Municipio F', 900, 720, 'mezclado',   'operativa', -34.8280, -56.0910);

INSERT INTO maquinaria (id, nombre, categoria, estado, cantidad, instalacion_id) VALUES
    (1, 'Cinta transportadora',      'maquinaria',  'operativa',     1, 2),
    (2, 'Prensa compactadora',       'maquinaria',  'mantenimiento', 1, 2),
    (3, 'Pala mecánica',             'maquinaria',  'operativa',     2, 3),
    (4, 'Contenedores de repuesto',  'repuesto',    'operativa',    12, 1),
    (5, 'Carretillas',               'herramienta', 'operativa',     6, 1);

INSERT INTO incidencia (id, contenedor_id, tipo, descripcion, gravedad, estado, cuadrilla_id, reportado_por_nombre, fecha_reporte) VALUES
    (1, 2, 'desborde', 'El contenedor está desbordado hace dos días y hay bolsas en la vereda.', 'alta', 'abierta',  NULL, 'Vecino de Prueba', NOW()),
    (2, 1, 'rotura',   'La tapa está rota y no cierra.',                                          'media', 'en_curso', 1,   'Marta Pérez',      NOW());

INSERT INTO ruta (id, nombre, zona, dia_semana, turno, cuadrilla_id, instalacion_id) VALUES
    (1, 'Ruta Pocitos Mañana', 'Municipio CH', 'lunes',  'matutino',   1,    1),
    (2, 'Ruta Malvín Tarde',   'Municipio E',  'martes', 'vespertino', NULL, 3);

INSERT INTO ruta_contenedor (ruta_id, contenedor_id, orden) VALUES
    (1, 3, 1),
    (1, 1, 2),
    (2, 2, 1);

-- ============================================================
--  RECOLECCIONES (requerimiento 3.1.4.2)
--
--  Cada fila es un recorrido hecho: qué ruta, qué día y cuántos
--  kilos se levantaron. La zona no se repite acá: sale de ruta.zona
--  con un JOIN, igual que en las incidencias.
--
--  Los kilos son SIMULADOS, como pide el propio requerimiento
--  ("basado en datos simulados"). Se generaron una sola vez con una
--  semilla fija a partir de la cantidad de contenedores de cada ruta,
--  con variación semanal de ±18% y una leve suba estacional. No se
--  calculan al vuelo: son filas de verdad, así el informe hace una
--  agregación real y el día que se carguen recolecciones reales la
--  consulta no cambia.
--
--  Cubren de mayo a octubre de 2026, una salida por semana por ruta.
-- ============================================================

CREATE TABLE recoleccion (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    ruta_id   INT NOT NULL,
    fecha     DATE NOT NULL,
    kilos     DECIMAL(10,2) NOT NULL,
    camion_id INT NULL,
    CONSTRAINT fk_recoleccion_ruta
        FOREIGN KEY (ruta_id) REFERENCES ruta(id) ON DELETE CASCADE,
    CONSTRAINT fk_recoleccion_camion
        FOREIGN KEY (camion_id) REFERENCES camion(id) ON DELETE SET NULL
);

INSERT INTO recoleccion (id, ruta_id, fecha, kilos, camion_id) VALUES
    (1, 1, '2026-05-04', 1069.98, 1),
    (2, 2, '2026-05-05', 344.05, NULL),
    (3, 1, '2026-05-11', 1241.12, 1),
    (4, 2, '2026-05-12', 365.76, NULL),
    (5, 1, '2026-05-18', 1245.28, 1),
    (6, 2, '2026-05-19', 311.52, NULL),
    (7, 1, '2026-05-25', 1400.7, 1),
    (8, 2, '2026-05-26', 262.72, NULL),
    (9, 1, '2026-06-01', 1062.62, 1),
    (10, 2, '2026-06-02', 292.19, NULL),
    (11, 1, '2026-06-08', 1116.47, 1),
    (12, 2, '2026-06-09', 316.41, NULL),
    (13, 1, '2026-06-15', 1285.1, 1),
    (14, 2, '2026-06-16', 286.8, NULL),
    (15, 1, '2026-06-22', 1265.25, 1),
    (16, 2, '2026-06-23', 305.25, NULL),
    (17, 1, '2026-06-29', 1366.5, 1),
    (18, 2, '2026-06-30', 352.01, NULL),
    (19, 1, '2026-07-06', 1261.34, 1),
    (20, 2, '2026-07-07', 332.76, NULL),
    (21, 1, '2026-07-13', 1342.91, 1),
    (22, 2, '2026-07-14', 318.64, NULL),
    (23, 1, '2026-07-20', 1359.69, 1),
    (24, 2, '2026-07-21', 256.96, NULL),
    (25, 1, '2026-07-27', 1352.06, 1),
    (26, 2, '2026-07-28', 333.53, NULL),
    (27, 1, '2026-08-03', 1278.62, 1),
    (28, 2, '2026-08-04', 294.77, NULL),
    (29, 1, '2026-08-10', 1123.91, 1),
    (30, 2, '2026-08-11', 301.32, NULL),
    (31, 1, '2026-08-17', 1290.98, 1),
    (32, 2, '2026-08-18', 298.7, NULL),
    (33, 1, '2026-08-24', 1066.23, 1),
    (34, 2, '2026-08-25', 319.57, NULL),
    (35, 1, '2026-08-31', 1381.4, 1),
    (36, 2, '2026-09-01', 309.83, NULL),
    (37, 1, '2026-09-07', 1217.53, 1),
    (38, 2, '2026-09-08', 267.2, NULL),
    (39, 1, '2026-09-14', 1380.51, 1),
    (40, 2, '2026-09-15', 310.71, NULL),
    (41, 1, '2026-09-21', 1322.66, 1),
    (42, 2, '2026-09-22', 337.92, NULL),
    (43, 1, '2026-09-28', 1320.08, 1),
    (44, 2, '2026-09-29', 321.18, NULL),
    (45, 1, '2026-10-05', 1166.18, 1),
    (46, 2, '2026-10-06', 324.45, NULL),
    (47, 1, '2026-10-12', 1185.53, 1),
    (48, 2, '2026-10-13', 374.34, NULL),
    (49, 1, '2026-10-19', 1526.94, 1),
    (50, 2, '2026-10-20', 297.22, NULL),
    (51, 1, '2026-10-26', 1503.23, 1);
