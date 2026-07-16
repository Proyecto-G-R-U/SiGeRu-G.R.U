<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioJson: clase base ABSTRACTA de todos los repositorios.
 *
 * Concentra en un solo lugar todo lo que los repositorios repetían:
 * leer/escribir el archivo JSON, crear los datos iniciales la primera vez,
 * listar, buscar por id, eliminar y calcular el próximo id.
 *
 * Cada repositorio hijo solo define lo que lo hace distinto:
 *   - nombreArchivo():  en qué archivo se guarda (ej. "usuarios.json")
 *   - datosIniciales(): los datos de prueba de la primera vez
 *   - aObjeto():        cómo convertir una fila del JSON en su objeto
 *   - sus métodos específicos (buscarPorEmail, asignarCuadrilla, etc.)
 *
 * Esto es HERENCIA aplicada a la capa de datos: mismo motor, distinta entidad.
 * En la 2da entrega, para migrar a MySQL, se reemplaza esta base (y los
 * métodos específicos que consultan el archivo) por consultas PDO.
 */
abstract class RepositorioJson
{
    protected string $archivo;

    public function __construct()
    {
        // La base vive en app/models/, así que subimos dos niveles
        // para llegar a la raíz de la API y entrar a data/.
        $this->archivo = dirname(__DIR__, 2) . '/data/' . $this->nombreArchivo();
        $this->inicializarSiHaceFalta();
    }

    /** Nombre del archivo JSON de esta entidad (ej. "usuarios.json"). */
    abstract protected function nombreArchivo(): string;

    /** Datos de prueba con los que se crea el archivo la primera vez. */
    abstract protected function datosIniciales(): array;

    /** Convierte una fila cruda del JSON en el objeto de la entidad. */
    abstract protected function aObjeto(array $fila): object;

    /** Si el archivo no existe todavía, lo crea con los datos iniciales. */
    private function inicializarSiHaceFalta(): void
    {
        if (file_exists($this->archivo)) {
            return;
        }
        $carpeta = dirname($this->archivo);
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        $this->guardarCrudo($this->datosIniciales());
    }

    /** Lee el archivo y devuelve un array de filas crudas. */
    protected function leerCrudo(): array
    {
        $datos = json_decode(file_get_contents($this->archivo), true);
        return is_array($datos) ? $datos : [];
    }

    /** Escribe el array de filas crudas al archivo. */
    protected function guardarCrudo(array $filas): void
    {
        file_put_contents(
            $this->archivo,
            json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /** Devuelve todas las entidades como objetos. */
    public function todos(): array
    {
        return array_map([$this, 'aObjeto'], $this->leerCrudo());
    }

    /** Busca una entidad por id. Devuelve null si no existe. */
    public function buscarPorId(int $id): ?object
    {
        foreach ($this->leerCrudo() as $f) {
            if ((int)$f['id'] === $id) {
                return $this->aObjeto($f);
            }
        }
        return null;
    }

    /** Elimina una entidad por id. Devuelve true si existía. */
    public function eliminar(int $id): bool
    {
        $filas = $this->leerCrudo();
        $original = count($filas);
        $filas = array_values(array_filter($filas, fn($f) => (int)$f['id'] !== $id));
        if (count($filas) === $original) {
            return false;
        }
        $this->guardarCrudo($filas);
        return true;
    }

    /** Calcula el próximo id disponible (el mayor actual + 1). */
    public function proximoId(): int
    {
        $maxId = 0;
        foreach ($this->leerCrudo() as $f) {
            if ((int)$f['id'] > $maxId) {
                $maxId = (int)$f['id'];
            }
        }
        return $maxId + 1;
    }
}
