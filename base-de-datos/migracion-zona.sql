-- =====================================================================
--  MIGRACIÓN: campo "zona" y vocabulario unificado de municipios
-- =====================================================================
--  Se puede ejecutar las veces que quieras: si la columna ya existe no
--  la vuelve a crear, avisa y sigue. No borra ni pisa datos propios.
--
--  Sirve para cuando YA tenés la base "sigeru" cargada y no querés
--  reimportar base.sql (perderías los contenedores que hayas dado de alta).
--  Si importás base.sql de cero, no hace falta correr esto: base.sql ya
--  deja todo como corresponde. Correrlo igual tampoco rompe nada.
--
--  Cómo ejecutarlo en XAMPP:
--    phpMyAdmin -> base "sigeru" -> pestaña SQL -> pegar todo y ejecutar.
--
--  Para qué sirve la columna:
--    agrupa los contenedores por municipio, que es lo que necesitan los
--    requerimientos 3.1.1.3.4 (historial por zona), 3.1.4.1 (listado de
--    incidencias filtrado por zona) y 3.1.4.3 (zonas críticas).
-- =====================================================================

USE sigeru;

-- ---------------------------------------------------------------------
-- 1) La columna, sólo si todavía no está.
--
--    MySQL no acepta "ADD COLUMN IF NOT EXISTS", así que primero
--    preguntamos al catálogo del motor (information_schema) y recién
--    ahí armamos el ALTER. Si ya existía, en vez del ALTER se ejecuta
--    un SELECT que te avisa, y la migración sigue sin cortarse.
--
--    Queda NULL para los contenedores que ya existían: "sin zona
--    asignada" es un estado válido hasta que el admin la complete.
-- ---------------------------------------------------------------------
SET @hay_zona := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'contenedor'
      AND COLUMN_NAME  = 'zona'
);

SET @paso1 := IF(@hay_zona = 0,
    'ALTER TABLE contenedor ADD COLUMN zona VARCHAR(80) NULL AFTER direccion',
    'SELECT ''La columna contenedor.zona ya existía: no se toca.'' AS aviso');

PREPARE stmt FROM @paso1;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 1b) Capacidad del contenedor, en litros (requerimiento 3.1.4.2).
--
--     Sin este dato no hay forma de estimar el volumen recolectado.
--     780 y 1100 son las capacidades de los contenedores plásticos que
--     coloca la Intendencia; 2400 es la de los metálicos grandes.
--     Los que ya existían quedan en 1100, que es la más común.
-- ---------------------------------------------------------------------
SET @hay_cap := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'contenedor'
      AND COLUMN_NAME  = 'capacidad_litros'
);

SET @paso1b := IF(@hay_cap = 0,
    'ALTER TABLE contenedor ADD COLUMN capacidad_litros INT NOT NULL DEFAULT 1100 AFTER zona',
    'SELECT ''La columna contenedor.capacidad_litros ya existía: no se toca.'' AS aviso');

PREPARE stmt FROM @paso1b;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE contenedor SET capacidad_litros = 1100 WHERE codigo = 'CNT-102';
UPDATE contenedor SET capacidad_litros = 2400 WHERE codigo = 'CNT-403';
UPDATE contenedor SET capacidad_litros =  780 WHERE codigo = 'CNT-210';

-- ---------------------------------------------------------------------
-- 2) Zona de los contenedores de prueba que trae base.sql.
--
--    Son UPDATE por código, así que se pueden repetir sin problema.
--    Si cargaste otros contenedores, asignales la zona desde la pantalla
--    de Contenedores (botón Editar), o agregá acá tus propios UPDATE.
-- ---------------------------------------------------------------------
UPDATE contenedor SET zona = 'Municipio CH' WHERE codigo = 'CNT-102';
UPDATE contenedor SET zona = 'Municipio E'  WHERE codigo = 'CNT-403';
-- Ojo: si tu CNT-403 sigue en 'Rambla y Buceo' (-34.9110, -56.1360), esas
-- coordenadas caen en Municipio CH segun los limites oficiales. Corregilo
-- desde la pantalla de Contenedores: al mover el punto, la zona se recalcula.
UPDATE contenedor SET zona = 'Municipio CH' WHERE codigo = 'CNT-210';

-- ---------------------------------------------------------------------
-- 3) Vocabulario unificado en las tablas que YA tenían zona.
--
--    cuadrilla, flota y ruta venían con nombres propios ("Zona Norte",
--    "Pocitos"). Ahora las cuatro tablas usan los municipios de
--    Montevideo, así un informe las puede cruzar sin traducir nombres.
--
--    Sólo cambia los valores viejos conocidos: si vos cargaste otras
--    zonas a mano, quedan como están y las podés corregir desde el
--    sistema, que ahora ofrece los municipios en un desplegable.
-- ---------------------------------------------------------------------
UPDATE cuadrilla SET zona = 'Municipio C'  WHERE zona = 'Zona Norte';
UPDATE cuadrilla SET zona = 'Municipio CH' WHERE zona = 'Zona Sur';

UPDATE flota     SET zona = 'Municipio C'  WHERE zona = 'Zona Norte';
UPDATE flota     SET zona = 'Municipio CH' WHERE zona = 'Zona Sur';

UPDATE ruta      SET zona = 'Municipio CH' WHERE zona = 'Pocitos';
UPDATE ruta      SET zona = 'Municipio E'  WHERE zona = 'Buceo';

-- ---------------------------------------------------------------------
-- 3b) Tabla de notificaciones, si todavía no está.
--     CREATE TABLE IF NOT EXISTS sí existe en MySQL, así que acá no
--     hace falta el rodeo de la sentencia preparada.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notificacion (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT NOT NULL,
    tipo          ENUM('incidencia_nueva','incidencia_asignada','incidencia_resuelta','aviso')
                      NOT NULL DEFAULT 'aviso',
    titulo        VARCHAR(120) NOT NULL,
    mensaje       VARCHAR(400) NOT NULL,
    gravedad      ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
    incidencia_id INT NULL,
    leida         TINYINT(1) NOT NULL DEFAULT 0,
    fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_incidencia
        FOREIGN KEY (incidencia_id) REFERENCES incidencia(id) ON DELETE CASCADE,
    INDEX idx_notif_usuario (usuario_id, leida)
);

-- ---------------------------------------------------------------------
-- 3c) Zona de las instalaciones.
--     Hace falta para que una incidencia sobre una planta o un vertedero
--     aparezca en los informes por zona (3.1.4.1 y 3.1.4.3). Sin esto,
--     esas incidencias caerían siempre en "Sin zona asignada".
-- ---------------------------------------------------------------------
SET @hay_izona := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'instalacion' AND COLUMN_NAME = 'zona'
);
SET @p3c := IF(@hay_izona = 0,
    'ALTER TABLE instalacion ADD COLUMN zona VARCHAR(80) NULL AFTER tipo',
    'SELECT ''La columna instalacion.zona ya existía: no se toca.'' AS aviso');
PREPARE stmt FROM @p3c; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE instalacion SET zona = 'Municipio C' WHERE nombre = 'Centro de Acopio Centro';
UPDATE instalacion SET zona = 'Municipio F' WHERE nombre = 'Planta Clasificadora Este';
UPDATE instalacion SET zona = 'Municipio F' WHERE nombre = 'Vertedero Felipe Cardoso';

-- ---------------------------------------------------------------------
-- 3d) Incidencias sobre instalaciones (requerimiento 3.1.2.2.3).
--
--     Hasta ahora una incidencia SIEMPRE colgaba de un contenedor. Los
--     operarios de clasificación necesitan reportar novedades sobre la
--     planta donde trabajan, así que contenedor_id pasa a ser opcional y
--     se agrega instalacion_id.
--
--     El CHECK del final es lo que impide que entre una incidencia
--     huérfana (sin ninguno de los dos) o apuntando a las dos cosas.
-- ---------------------------------------------------------------------

-- contenedor_id deja de ser obligatorio. Repetir este MODIFY no hace daño.
ALTER TABLE incidencia MODIFY contenedor_id INT NULL;

SET @hay_inst := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencia' AND COLUMN_NAME = 'instalacion_id'
);
SET @p3d := IF(@hay_inst = 0,
    'ALTER TABLE incidencia ADD COLUMN instalacion_id INT NULL AFTER contenedor_id',
    'SELECT ''La columna incidencia.instalacion_id ya existía: no se toca.'' AS aviso');
PREPARE stmt FROM @p3d; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @hay_fk := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencia'
      AND CONSTRAINT_NAME = 'fk_incidencia_instalacion'
);
SET @p3e := IF(@hay_fk = 0,
    'ALTER TABLE incidencia ADD CONSTRAINT fk_incidencia_instalacion
        FOREIGN KEY (instalacion_id) REFERENCES instalacion(id) ON DELETE CASCADE',
    'SELECT ''La clave foránea de instalación ya existía.'' AS aviso');
PREPARE stmt FROM @p3e; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @hay_chk := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencia'
      AND CONSTRAINT_NAME = 'chk_incidencia_objeto'
);
SET @p3f := IF(@hay_chk = 0,
    'ALTER TABLE incidencia ADD CONSTRAINT chk_incidencia_objeto CHECK (
        (contenedor_id IS NOT NULL AND instalacion_id IS NULL) OR
        (contenedor_id IS NULL AND instalacion_id IS NOT NULL))',
    'SELECT ''El CHECK de objetivo único ya existía.'' AS aviso');
PREPARE stmt FROM @p3f; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 4) Comprobación. Tendrías que ver los contenedores con su municipio,
--    y las otras tres tablas hablando el mismo idioma.
-- ---------------------------------------------------------------------
SELECT 'contenedor' AS tabla, id, codigo AS nombre, zona FROM contenedor
UNION ALL
SELECT 'cuadrilla',  id, nombre, zona FROM cuadrilla
UNION ALL
SELECT 'flota',      id, nombre, zona FROM flota
UNION ALL
SELECT 'ruta',       id, nombre, zona FROM ruta
ORDER BY tabla, id;
