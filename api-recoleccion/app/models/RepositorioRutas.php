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
            $contenedores,
            $f['instalacion_id'] !== null ? (int)$f['instalacion_id'] : null
        );
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Ruta $r): void
    {
        $this->consulta(
            'INSERT INTO ruta (id, nombre, zona, dia_semana, turno, cuadrilla_id, instalacion_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$r->getId(), $r->getNombre(), $r->getZona(),
             $r->getDiaSemana(), $r->getTurno(), $r->getCuadrillaId(), $r->getInstalacionId()]
        );
    }

    /** Actualiza los datos de la ruta (no sus contenedores). */
    public function actualizar(Ruta $r): bool
    {
        $this->consulta(
            'UPDATE ruta SET nombre = ?, zona = ?, dia_semana = ?, turno = ?, cuadrilla_id = ?,
                    instalacion_id = ? WHERE id = ?',
            [$r->getNombre(), $r->getZona(), $r->getDiaSemana(),
             $r->getTurno(), $r->getCuadrillaId(), $r->getInstalacionId(), $r->getId()]
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

    /**
     * Mueve una parada una posición arriba o abajo en el recorrido.
     * Intercambia el campo "orden" con la parada vecina, así el recorrido
     * queda renumerado sin huecos. $direccion: -1 sube, +1 baja.
     * Devuelve true si el movimiento se pudo hacer.
     */
    public function moverContenedor(int $rutaId, int $contenedorId, int $direccion): bool
    {
        $actual = $this->fila(
            'SELECT orden FROM ruta_contenedor WHERE ruta_id = ? AND contenedor_id = ?',
            [$rutaId, $contenedorId]
        );
        if ($actual === null) {
            return false;
        }
        $ordenActual = (int)$actual['orden'];

        // La parada vecina en la dirección pedida: la de orden inmediatamente
        // menor (si sube) o mayor (si baja).
        $vecina = $direccion < 0
            ? $this->fila('SELECT contenedor_id, orden FROM ruta_contenedor
                           WHERE ruta_id = ? AND orden < ? ORDER BY orden DESC LIMIT 1',
                          [$rutaId, $ordenActual])
            : $this->fila('SELECT contenedor_id, orden FROM ruta_contenedor
                           WHERE ruta_id = ? AND orden > ? ORDER BY orden ASC LIMIT 1',
                          [$rutaId, $ordenActual]);

        // Ya está en un extremo: no hay nada que intercambiar.
        if ($vecina === null) {
            return false;
        }

        $this->consulta('UPDATE ruta_contenedor SET orden = ? WHERE ruta_id = ? AND contenedor_id = ?',
                        [(int)$vecina['orden'], $rutaId, $contenedorId]);
        $this->consulta('UPDATE ruta_contenedor SET orden = ? WHERE ruta_id = ? AND contenedor_id = ?',
                        [$ordenActual, $rutaId, (int)$vecina['contenedor_id']]);
        return true;
    }

    /**
     * Asigna (o quita) la instalación donde descarga el camión al terminar.
     * Comprueba contra la tabla instalacion, que vive en la misma base —
     * igual que RepositorioCuadrillas consulta la tabla usuario.
     */
    public function asignarDestino(int $rutaId, ?int $instalacionId): bool
    {
        if ($this->fila('SELECT id FROM ruta WHERE id = ?', [$rutaId]) === null) {
            return false;
        }
        if ($instalacionId !== null
            && $this->fila('SELECT id FROM instalacion WHERE id = ?', [$instalacionId]) === null) {
            return false;
        }
        $this->consulta('UPDATE ruta SET instalacion_id = ? WHERE id = ?', [$instalacionId, $rutaId]);
        return true;
    }
}
