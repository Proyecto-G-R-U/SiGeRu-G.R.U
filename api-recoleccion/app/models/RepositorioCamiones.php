<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioCamiones: persistencia de la flota en archivo JSON.
 * Hereda el motor común de RepositorioJson; acá solo lo específico.
 */
class RepositorioCamiones extends RepositorioJson
{
    protected function nombreArchivo(): string
    {
        return 'camiones.json';
    }

    protected function datosIniciales(): array
    {
        return [
            ['id' => 1, 'patente' => 'STP 1234', 'modelo' => 'Volvo FE',       'estado' => 'operativo',     'disponibilidad' => 'en_ruta',    'flotaId' => 1, 'cuadrillaId' => 1],
            ['id' => 2, 'patente' => 'STP 5678', 'modelo' => 'Mercedes Atego', 'estado' => 'operativo',     'disponibilidad' => 'disponible', 'flotaId' => 1, 'cuadrillaId' => null],
            ['id' => 3, 'patente' => 'STP 9012', 'modelo' => 'Iveco Tector',   'estado' => 'mantenimiento', 'disponibilidad' => 'disponible', 'flotaId' => 2, 'cuadrillaId' => null],
        ];
    }

    protected function aObjeto(array $f): object
    {
        return new Camion(
            $f['id'],
            $f['patente'],
            $f['modelo'],
            $f['estado'],
            $f['disponibilidad'],
            isset($f['flotaId']) && $f['flotaId'] !== null ? (int)$f['flotaId'] : null,
            isset($f['cuadrillaId']) && $f['cuadrillaId'] !== null ? (int)$f['cuadrillaId'] : null
        );
    }

    private function aFila(Camion $c): array
    {
        return [
            'id'             => $c->getId(),
            'patente'        => $c->getPatente(),
            'modelo'         => $c->getModelo(),
            'estado'         => $c->getEstado(),
            'disponibilidad' => $c->getDisponibilidad(),
            'flotaId'        => $c->getFlotaId(),
            'cuadrillaId'    => $c->getCuadrillaId(),
        ];
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Camion $c): void
    {
        $filas = $this->leerCrudo();
        $filas[] = $this->aFila($c);
        $this->guardarCrudo($filas);
    }

    /** Actualiza un camión existente. Devuelve true si existía. */
    public function actualizar(Camion $c): bool
    {
        $filas = $this->leerCrudo();
        foreach ($filas as $i => $f) {
            if ((int)$f['id'] === $c->getId()) {
                $filas[$i] = $this->aFila($c);
                $this->guardarCrudo($filas);
                return true;
            }
        }
        return false;
    }

    /**
     * Asigna una cuadrilla a un camión con EXCLUSIVIDAD: la quita de
     * cualquier otro camión que la tuviera. Con null, deja el camión sin
     * cuadrilla. Devuelve true si el camión existe.
     */
    public function asignarCuadrillaExclusiva(int $camionId, ?int $cuadrillaId): bool
    {
        $filas = $this->leerCrudo();
        $existe = false;

        foreach ($filas as $i => $f) {
            if ($cuadrillaId !== null && (int)$f['id'] !== $camionId
                && isset($f['cuadrillaId']) && (int)$f['cuadrillaId'] === $cuadrillaId) {
                $filas[$i]['cuadrillaId'] = null;
            }
            if ((int)$f['id'] === $camionId) {
                $filas[$i]['cuadrillaId'] = $cuadrillaId;
                $existe = true;
            }
        }

        if (!$existe) {
            return false;
        }
        $this->guardarCrudo($filas);
        return true;
    }
}
