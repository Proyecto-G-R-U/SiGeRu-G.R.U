<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioRutas: persistencia de rutas de recolección en MySQL.
 *
 * La lista de contenedores de cada ruta vive en la tabla intermedia
 * ruta_contenedor, así que aObjeto() la reconstruye con una consulta
 * aparte (igual que RepositorioCuadrillas hace con sus operarios).
 */
class RepositorioRutas extends RepositorioSql
{
    protected function tabla(): string
    {
        return 'ruta';
    }

    protected function aObjeto(array $f): object
    {
        $contenedores = array_map(
            fn($r) => (int)$r['contenedor_id'],
            $this->filas('SELECT contenedor_id FROM ruta_contenedor
                          WHERE ruta_id = ? ORDER BY orden', [$f['id']])
        );
        return new Ruta(
            (int)$f['id'],
            $f['nombre'],
            $f['zona'] ?? null,
            $f['dia_semana'],
            $f['turno'],
            $f['cuadrilla_id'] !== null ? (int)$f['cuadrilla_id'] : null,
            $contenedores
        );
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Ruta $r): void
    {
        $this->consulta(
            'INSERT INTO ruta (id, nombre, zona, dia_semana, turno, cuadrilla_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$r->getId(), $r->getNombre(), $r->getZona(),
             $r->getDiaSemana(), $r->getTurno(), $r->getCuadrillaId()]
        );
    }

    /** Actualiza los datos de la ruta (no sus contenedores). */
    public function actualizar(Ruta $r): bool
    {
        $this->consulta(
            'UPDATE ruta SET nombre = ?, zona = ?, dia_semana = ?, turno = ?, cuadrilla_id = ?
             WHERE id = ?',
            [$r->getNombre(), $r->getZona(), $r->getDiaSemana(),
             $r->getTurno(), $r->getCuadrillaId(), $r->getId()]
        );
        return $this->fila('SELECT id FROM ruta WHERE id = ?', [$r->getId()]) !== null;
    }

    /** Rutas asignadas a una cuadrilla (lo que ve el operario de recolección). */
    public function porCuadrilla(int $cuadrillaId): array
    {
        return array_map(
            [$this, 'aObjeto'],
            $this->filas('SELECT * FROM ruta WHERE cuadrilla_id = ?
                          ORDER BY FIELD(dia_semana,"lunes","martes","miercoles","jueves","viernes","sabado","domingo")',
                         [$cuadrillaId])
        );
    }

    /** Agrega un contenedor al final del recorrido de una ruta. */
    public function agregarContenedor(int $rutaId, int $contenedorId): bool
    {
        if ($this->fila('SELECT id FROM ruta WHERE id = ?', [$rutaId]) === null) {
            return false;
        }
        // Si ya está en la ruta, no lo duplicamos.
        $ya = $this->fila('SELECT ruta_id FROM ruta_contenedor WHERE ruta_id = ? AND contenedor_id = ?',
                          [$rutaId, $contenedorId]);
        if ($ya !== null) {
            return true;
        }
        $f = $this->fila('SELECT COALESCE(MAX(orden), 0) + 1 AS sig FROM ruta_contenedor WHERE ruta_id = ?',
                         [$rutaId]);
        $this->consulta('INSERT INTO ruta_contenedor (ruta_id, contenedor_id, orden) VALUES (?, ?, ?)',
                        [$rutaId, $contenedorId, (int)$f['sig']]);
        return true;
    }

    /** Quita un contenedor del recorrido. */
    public function quitarContenedor(int $rutaId, int $contenedorId): bool
    {
        $this->consulta('DELETE FROM ruta_contenedor WHERE ruta_id = ? AND contenedor_id = ?',
                        [$rutaId, $contenedorId]);
        return true;
    }
}
