<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioCuadrillas: persistencia de cuadrillas en MySQL.
 *
 * Cambio importante respecto a la versión JSON: antes cada cuadrilla
 * guardaba un array "operarios"; ahora la relación vive del lado del
 * usuario (columna usuario.cuadrilla_id). Eso garantiza la EXCLUSIVIDAD
 * por diseño: como un usuario tiene UNA sola cuadrilla_id, es imposible
 * que esté en dos cuadrillas a la vez.
 *
 * El objeto Cuadrilla sigue exponiendo su lista de operarios (la
 * reconstruimos con una consulta), así el frontend no cambia en nada.
 */
class RepositorioCuadrillas extends RepositorioSql
{
    protected function tabla(): string
    {
        return 'cuadrilla';
    }

    protected function aObjeto(array $f): object
    {
        // Reconstruimos la lista de integrantes consultando qué usuarios
        // apuntan a esta cuadrilla.
        $operarios = array_map(
            fn($r) => (int)$r['id'],
            $this->filas('SELECT id FROM usuario WHERE cuadrilla_id = ?', [$f['id']])
        );
        return new Cuadrilla((int)$f['id'], $f['nombre'], $f['zona'] ?? null, $operarios);
    }

    // ---------------- Métodos específicos de cuadrillas ----------------

    public function agregar(Cuadrilla $c): void
    {
        $this->consulta(
            'INSERT INTO cuadrilla (id, nombre, zona) VALUES (?, ?, ?)',
            [$c->getId(), $c->getNombre(), $c->getZona()]
        );
    }

    /**
     * Suma un operario a una cuadrilla. La exclusividad es automática:
     * el UPDATE pisa cualquier cuadrilla anterior del operario.
     * Devuelve true si la cuadrilla destino existe.
     */
    public function agregarOperario(int $cuadrillaId, int $operarioId): bool
    {
        if ($this->fila('SELECT id FROM cuadrilla WHERE id = ?', [$cuadrillaId]) === null) {
            return false;
        }
        $this->consulta('UPDATE usuario SET cuadrilla_id = ? WHERE id = ?', [$cuadrillaId, $operarioId]);
        return true;
    }

    /** Quita un operario de una cuadrilla. Devuelve true si la cuadrilla existe. */
    public function quitarOperario(int $cuadrillaId, int $operarioId): bool
    {
        if ($this->fila('SELECT id FROM cuadrilla WHERE id = ?', [$cuadrillaId]) === null) {
            return false;
        }
        $this->consulta(
            'UPDATE usuario SET cuadrilla_id = NULL WHERE id = ? AND cuadrilla_id = ?',
            [$operarioId, $cuadrillaId]
        );
        return true;
    }

    /** Ids de operarios que ya integran alguna cuadrilla (para calcular libres). */
    public function operariosOcupados(): array
    {
        return array_map(
            fn($r) => (int)$r['id'],
            $this->filas('SELECT id FROM usuario WHERE cuadrilla_id IS NOT NULL')
        );
    }
}
