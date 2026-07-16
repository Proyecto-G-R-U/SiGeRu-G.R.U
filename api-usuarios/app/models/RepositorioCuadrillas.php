<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioCuadrillas: persistencia de cuadrillas en archivo JSON.
 * Hereda el motor común de RepositorioJson; acá solo lo específico.
 */
class RepositorioCuadrillas extends RepositorioJson
{
    protected function nombreArchivo(): string
    {
        return 'cuadrillas.json';
    }

    protected function datosIniciales(): array
    {
        return [
            ['id' => 1, 'nombre' => 'Cuadrilla Norte', 'zona' => 'Zona Norte', 'operarios' => [2]],
        ];
    }

    protected function aObjeto(array $f): object
    {
        return new Cuadrilla($f['id'], $f['nombre'], $f['zona'] ?? null, $f['operarios'] ?? []);
    }

    // ---------------- Métodos específicos de cuadrillas ----------------

    public function agregar(Cuadrilla $c): void
    {
        $filas = $this->leerCrudo();
        $filas[] = [
            'id'        => $c->getId(),
            'nombre'    => $c->getNombre(),
            'zona'      => $c->getZona(),
            'operarios' => $c->getOperarios(),
        ];
        $this->guardarCrudo($filas);
    }

    /**
     * Agrega un operario garantizando EXCLUSIVIDAD: lo quita de cualquier
     * otra cuadrilla antes de sumarlo a la indicada. Así un operario nunca
     * queda en dos cuadrillas a la vez. Devuelve true si la cuadrilla existe.
     */
    public function agregarOperario(int $cuadrillaId, int $operarioId): bool
    {
        $filas = $this->leerCrudo();
        $existeDestino = false;

        foreach ($filas as $i => $f) {
            $operarios = array_map('intval', $f['operarios'] ?? []);
            $operarios = array_values(array_filter($operarios, fn($op) => $op !== $operarioId));
            if ((int)$f['id'] === $cuadrillaId) {
                $operarios[] = $operarioId;
                $existeDestino = true;
            }
            $filas[$i]['operarios'] = $operarios;
        }

        if (!$existeDestino) {
            return false;
        }
        $this->guardarCrudo($filas);
        return true;
    }

    /** Quita un operario de una cuadrilla. Devuelve true si la cuadrilla existe. */
    public function quitarOperario(int $cuadrillaId, int $operarioId): bool
    {
        $filas = $this->leerCrudo();
        foreach ($filas as $i => $f) {
            if ((int)$f['id'] === $cuadrillaId) {
                $operarios = array_map('intval', $f['operarios'] ?? []);
                $filas[$i]['operarios'] = array_values(array_filter($operarios, fn($op) => $op !== $operarioId));
                $this->guardarCrudo($filas);
                return true;
            }
        }
        return false;
    }

    /** Ids de operarios que ya integran alguna cuadrilla (para calcular libres). */
    public function operariosOcupados(): array
    {
        $ocupados = [];
        foreach ($this->leerCrudo() as $f) {
            foreach ($f['operarios'] ?? [] as $op) {
                $ocupados[] = (int)$op;
            }
        }
        return array_values(array_unique($ocupados));
    }
}
