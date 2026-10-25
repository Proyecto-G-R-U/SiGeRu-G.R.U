<?php
declare(strict_types=1);

/**
 * Mini framework de pruebas — SiGeRU
 *
 * Por qué propio y no PHPUnit: PHPUnit es el estándar de la industria, pero
 * se instala con Composer, que no viene con XAMPP. Para que las pruebas
 * puedan correrse en cualquier máquina del curso sin instalar nada, se
 * implementó este runner mínimo con las mismas ideas: casos de prueba,
 * aserciones y un reporte final de pasadas/falladas.
 *
 * Uso:  php tests/correr-tests.php
 *   o:  http://localhost/sigeru/tests/correr-tests.php
 */

class Test
{
    private static int $pasadas = 0;
    private static int $falladas = 0;
    private static array $errores = [];
    private static string $grupoActual = '';

    /** Abre un grupo de pruebas relacionadas. */
    public static function grupo(string $nombre): void
    {
        self::$grupoActual = $nombre;
        echo "\n" . str_repeat('─', 62) . "\n";
        echo "  $nombre\n";
        echo str_repeat('─', 62) . "\n";
    }

    /** Comprueba que una condición sea verdadera. */
    public static function comprobar(string $descripcion, bool $condicion): void
    {
        if ($condicion) {
            self::$pasadas++;
            echo "  [OK]    $descripcion\n";
        } else {
            self::$falladas++;
            self::$errores[] = self::$grupoActual . ' → ' . $descripcion;
            echo "  [FALLA] $descripcion\n";
        }
    }

    /** Comprueba que dos valores sean iguales. */
    public static function igual(string $descripcion, mixed $esperado, mixed $obtenido): void
    {
        $ok = ($esperado === $obtenido);
        if (!$ok) {
            $descripcion .= ' (esperaba: ' . var_export($esperado, true)
                          . ', obtuvo: ' . var_export($obtenido, true) . ')';
        }
        self::comprobar($descripcion, $ok);
    }

    /** Comprueba que al ejecutar algo se lance una excepción. */
    public static function lanzaError(string $descripcion, callable $accion): void
    {
        try {
            $accion();
            self::comprobar($descripcion, false);
        } catch (\Throwable $e) {
            self::comprobar($descripcion, true);
        }
    }

    /** Imprime el resumen final y devuelve el código de salida. */
    public static function resumen(): int
    {
        $total = self::$pasadas + self::$falladas;
        echo "\n" . str_repeat('═', 62) . "\n";
        echo "  RESUMEN: $total pruebas — " . self::$pasadas . " pasadas, "
           . self::$falladas . " falladas\n";
        if (self::$falladas > 0) {
            echo "\n  Pruebas falladas:\n";
            foreach (self::$errores as $e) {
                echo "    • $e\n";
            }
        } else {
            echo "  Todas las pruebas pasaron correctamente.\n";
        }
        echo str_repeat('═', 62) . "\n";
        return self::$falladas === 0 ? 0 : 1;
    }
}
