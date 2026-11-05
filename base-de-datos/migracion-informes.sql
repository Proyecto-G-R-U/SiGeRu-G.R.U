-- =====================================================================
--  MIGRACIÓN: tabla "recoleccion" para el informe de volumen (3.1.4.2)
-- =====================================================================
--  Se puede ejecutar las veces que quieras: si la tabla ya existe no la
--  recrea, y si los datos ya están no los duplica (los INSERT llevan id
--  explícito y van con IGNORE).
--
--  Sólo hace falta si YA tenés la base "sigeru" cargada y no querés
--  reimportar base.sql. Si importás de cero, base.sql ya hace todo esto.
--
--  Cómo ejecutarlo en XAMPP:
--    phpMyAdmin -> base "sigeru" -> pestaña SQL -> pegar todo y ejecutar.
--
--  Depende de que existan las tablas "ruta" y "camion". Si tu base es
--  anterior al mapa de rutas, reimportá base.sql directamente.
-- =====================================================================

USE sigeru;

-- 1) La tabla, sólo si todavía no está.
SET @hay := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'recoleccion'
);

SET @paso1 := IF(@hay = 0,
    'CREATE TABLE recoleccion (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        ruta_id   INT NOT NULL,
        fecha     DATE NOT NULL,
        kilos     DECIMAL(10,2) NOT NULL,
        camion_id INT NULL,
        CONSTRAINT fk_recoleccion_ruta
            FOREIGN KEY (ruta_id) REFERENCES ruta(id) ON DELETE CASCADE,
        CONSTRAINT fk_recoleccion_camion
            FOREIGN KEY (camion_id) REFERENCES camion(id) ON DELETE SET NULL
    )',
    'SELECT ''La tabla recoleccion ya existía: no se toca.'' AS aviso');

PREPARE stmt FROM @paso1;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Los datos simulados. IGNORE + id explícito = correrlo dos veces no
--    duplica los kilos ni infla el informe.
INSERT IGNORE INTO recoleccion (id, ruta_id, fecha, kilos, camion_id) VALUES
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

-- 3) Comprobación.
SELECT COUNT(*) AS recolecciones,
       MIN(fecha) AS desde,
       MAX(fecha) AS hasta,
       ROUND(SUM(kilos)) AS kilos_totales
FROM recoleccion;
