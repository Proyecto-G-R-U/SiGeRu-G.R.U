<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioMaquinaria: persistencia del inventario de maquinaria,
 * herramientas y repuestos en MySQL. Hereda el motor de RepositorioSql.
 */
class RepositorioMaquinaria extends RepositorioSql
{
    protected function tabla(): string
    {
        return 'maquinaria';
    }

    protected function aObjeto(array $f): object
    {
        return new Maquinaria(
            (int)$f['id'],
            $f['nombre'],
            $f['categoria'],
            $f['estado'],
            (int)$f['cantidad'],
            $f['instalacion_id'] !== null ? (int)$f['instalacion_id'] : null
        );
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Maquinaria $m): void
    {
        $this->consulta(
            'INSERT INTO maquinaria (id, nombre, categoria, estado, cantidad, instalacion_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$m->getId(), $m->getNombre(), $m->getCategoria(), $m->getEstado(),
             $m->getCantidad(), $m->getInstalacionId()]
        );
    }

    /** Actualiza un ítem existente. Devuelve true si existía. */
    public function actualizar(Maquinaria $m): bool
    {
        $this->consulta(
            'UPDATE maquinaria SET nombre = ?, categoria = ?, estado = ?, cantidad = ?,
                    instalacion_id = ? WHERE id = ?',
            [$m->getNombre(), $m->getCategoria(), $m->getEstado(), $m->getCantidad(),
             $m->getInstalacionId(), $m->getId()]
        );
        return $this->fila('SELECT id FROM maquinaria WHERE id = ?', [$m->getId()]) !== null;
    }

    /** Inventario de una instalación concreta (para los paneles de operarios). */
    public function porInstalacion(int $instalacionId): array
    {
        return array_map(
            [$this, 'aObjeto'],
            $this->filas('SELECT * FROM maquinaria WHERE instalacion_id = ? ORDER BY nombre', [$instalacionId])
        );
    }
}
