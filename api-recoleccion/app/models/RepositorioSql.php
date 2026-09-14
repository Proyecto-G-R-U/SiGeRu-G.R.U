<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * RepositorioSql: clase base ABSTRACTA de todos los repositorios (versión SQL).
 *
 * Reemplaza a la antigua RepositorioJson. En lugar de leer/escribir un archivo,
 * ahora consulta la base de datos MySQL con PDO. Concentra lo común:
 *   - la conexión PDO (una sola, compartida entre repositorios),
 *   - todos(), buscarPorId(), eliminar(),
 *   - helpers para ejecutar consultas.
 *
 * Cada repositorio hijo solo define lo específico:
 *   - tabla():    nombre de su tabla (ej. "usuario")
 *   - aObjeto():  cómo convertir una fila de la BD en su objeto
 *   - sus métodos propios (buscarPorEmail, asignarCuadrilla, etc.)
 *
 * Config de conexión: XAMPP por defecto (host localhost, usuario root, sin
 * contraseña, base "sigeru"). Si cambiás las credenciales, se editan acá.
 */
abstract class RepositorioSql
{
    // --- Configuración de conexión (XAMPP por defecto) ---
    // 127.0.0.1 en vez de 'localhost': en Windows, 'localhost' a veces
    // resuelve por IPv6 y la conexión falla aunque MySQL esté corriendo.
    private const HOST = '127.0.0.1';
    private const BASE = 'sigeru';
    private const USER = 'root';
    private const PASS = '';

    /** Conexión PDO única, reutilizada por todas las instancias. */
    private static ?PDO $pdo = null;

    protected PDO $db;

    public function __construct()
    {
        $this->db = self::conexion();
    }

    /** Devuelve la conexión PDO, creándola la primera vez (patrón singleton). */
    protected static function conexion(): PDO
    {
        if (self::$pdo === null) {
            try {
                $dsn = 'mysql:host=' . self::HOST . ';dbname=' . self::BASE . ';charset=utf8mb4';
                self::$pdo = new PDO($dsn, self::USER, self::PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errores como excepciones
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // filas como arrays asociativos
                    PDO::ATTR_EMULATE_PREPARES   => false,                    // prepares reales (más seguro)
                ]);
            } catch (\PDOException $e) {
                // Sin conexión no hay sistema: avisamos claro en JSON para que
                // el frontend muestre un error entendible en vez de un 500 mudo.
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error'   => 'No se pudo conectar a la base de datos "' . self::BASE . '". ' .
                                 'Verificá que MySQL esté corriendo en XAMPP y que hayas importado base.sql.',
                    'detalle' => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        return self::$pdo;
    }

    /** Nombre de la tabla de esta entidad. */
    abstract protected function tabla(): string;

    /** Convierte una fila de la BD (array) en el objeto de la entidad. */
    abstract protected function aObjeto(array $fila): object;

    // ---------------- Helpers de consulta ----------------

    /** Ejecuta una consulta con parámetros y devuelve el statement. */
    /**
     * Ejecuta una consulta con parámetros y devuelve el statement.
     *
     * Si MySQL rechaza la consulta (tabla inexistente, valor duplicado, dato
     * inválido...), PDO lanza una excepción. Sin este try/catch esa excepción
     * cortaría PHP con una página de error HTML, y el frontend solo podría
     * mostrar un "error de conexión" sin explicar nada. Acá la traducimos a
     * un JSON con un mensaje entendible.
     */
    protected function consulta(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            $this->errorSql($e);
        }
    }

    /** Traduce un error de MySQL a una respuesta JSON entendible y corta. */
    private function errorSql(\PDOException $e): never
    {
        $codigo = $e->errorInfo[1] ?? 0;
        $mensaje = match (true) {
            // 1062: clave duplicada (ej. dos contenedores con el mismo código)
            $codigo === 1062 => 'Ya existe un registro con ese valor único '
                              . '(por ejemplo, un código o email repetido). Usá otro.',
            // 1146: la tabla no existe
            $codigo === 1146 => 'Falta una tabla en la base de datos. '
                              . 'Volvé a importar base-de-datos/base.sql en phpMyAdmin.',
            // 1054: columna desconocida (base desactualizada respecto al código)
            $codigo === 1054 => 'La base de datos está desactualizada respecto al código. '
                              . 'Volvé a importar base-de-datos/base.sql en phpMyAdmin.',
            // 1452: falla una clave foránea
            $codigo === 1452 => 'El registro hace referencia a algo que no existe '
                              . '(por ejemplo, una instalación o cuadrilla eliminada).',
            default          => 'Error al acceder a la base de datos.',
        };

        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error'   => $mensaje,
            'detalle' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Devuelve todas las filas de una consulta como arrays. */
    protected function filas(string $sql, array $params = []): array
    {
        return $this->consulta($sql, $params)->fetchAll();
    }

    /** Devuelve la primera fila de una consulta, o null. */
    protected function fila(string $sql, array $params = []): ?array
    {
        $r = $this->consulta($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    // ---------------- Métodos comunes (heredados) ----------------

    /** Devuelve todas las entidades como objetos. */
    public function todos(): array
    {
        return array_map([$this, 'aObjeto'], $this->filas('SELECT * FROM ' . $this->tabla()));
    }

    /** Busca una entidad por id. Devuelve null si no existe. */
    public function buscarPorId(int $id): ?object
    {
        $f = $this->fila('SELECT * FROM ' . $this->tabla() . ' WHERE id = ?', [$id]);
        return $f === null ? null : $this->aObjeto($f);
    }

    /** Elimina una entidad por id. Devuelve true si borró alguna fila. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->consulta('DELETE FROM ' . $this->tabla() . ' WHERE id = ?', [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Calcula el próximo id disponible (mayor actual + 1). La tabla tiene
     * AUTO_INCREMENT, pero mantenemos este método para que los controladores
     * sigan funcionando sin cambios respecto a la versión JSON.
     */
    public function proximoId(): int
    {
        $f = $this->fila('SELECT COALESCE(MAX(id), 0) + 1 AS proximo FROM ' . $this->tabla());
        return (int)$f['proximo'];
    }
}
