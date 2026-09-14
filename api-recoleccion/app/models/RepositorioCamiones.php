<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioCamiones: persistencia de la flota de camiones en MySQL.
 * Hereda el motor común de RepositorioSql; acá solo lo específico.
 * (Los datos de prueba los inserta base.sql.)
 */
class RepositorioCamiones extends RepositorioSql
{
    protected function tabla(): string
    {
        return 'camion';
    }

    protected function aObjeto(array $f): object
    {
        return new Camion(
            (int)$f['id'],
            $f['patente'],
            $f['modelo'],
            $f['estado'],
            $f['disponibilidad'],
            $f['flota_id'] !== null ? (int)$f['flota_id'] : null,
            $f['cuadrilla_id'] !== null ? (int)$f['cuadrilla_id'] : null
        );
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Camion $c): void
    {
        $this->consulta(
            'INSERT INTO camion (id, patente, modelo, estado, disponibilidad, flota_id, cuadrilla_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$c->getId(), $c->getPatente(), $c->getModelo(), $c->getEstado(),
             $c->getDisponibilidad(), $c->getFlotaId(), $c->getCuadrillaId()]
        );
    }

    /** Actualiza un camión completo. Devuelve true si existía. */
    public function actualizar(Camion $c): bool
    {
        $this->consulta(
            'UPDATE camion SET patente = ?, modelo = ?, estado = ?, disponibilidad = ?, flota_id = ?, cuadrilla_id = ?
             WHERE id = ?',
            [$c->getPatente(), $c->getModelo(), $c->getEstado(), $c->getDisponibilidad(),
             $c->getFlotaId(), $c->getCuadrillaId(), $c->getId()]
        );
        return $this->fila('SELECT id FROM camion WHERE id = ?', [$c->getId()]) !== null;
    }

    /**
     * Asigna una cuadrilla a un camión con EXCLUSIVIDAD: primero se la
     * quita a cualquier otro camión que la tuviera, después se la pone al
     * camión destino. Con null, deja el camión sin cuadrilla.
     * Devuelve true si el camión existe.
     */
    public function asignarCuadrillaExclusiva(int $camionId, ?int $cuadrillaId): bool
    {
        if ($this->fila('SELECT id FROM camion WHERE id = ?', [$camionId]) === null) {
            return false;
        }
        if ($cuadrillaId !== null) {
            $this->consulta(
                'UPDATE camion SET cuadrilla_id = NULL WHERE cuadrilla_id = ? AND id <> ?',
                [$cuadrillaId, $camionId]
            );
        }
        $this->consulta('UPDATE camion SET cuadrilla_id = ? WHERE id = ?', [$cuadrillaId, $camionId]);
        return true;
    }
}
