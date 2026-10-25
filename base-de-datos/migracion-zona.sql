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
