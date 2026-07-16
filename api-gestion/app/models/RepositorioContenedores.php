<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioContenedores: persistencia de contenedores en archivo JSON.
 * Hereda el motor común de RepositorioJson; acá solo lo específico.
 */
class RepositorioContenedores extends RepositorioJson
{
    protected function nombreArchivo(): string
    {
        return 'contenedores.json';
    }

    protected function datosIniciales(): array
    {
        // Coordenadas reales de Montevideo.
        return [
            ['id' => 1, 'codigo' => 'CNT-102', 'direccion' => 'Av. Italia y Bulevar Artigas', 'tipoResiduo' => 'reciclable', 'estado' => 'operativo',  'lat' => -34.9020, 'lng' => -56.1550],
            ['id' => 2, 'codigo' => 'CNT-403', 'direccion' => 'Rambla y Buceo',               'tipoResiduo' => 'mezclado',   'estado' => 'desbordado', 'lat' => -34.9110, 'lng' => -56.1360],
            ['id' => 3, 'codigo' => 'CNT-210', 'direccion' => 'Pocitos, 26 de Marzo',         'tipoResiduo' => 'reciclable', 'estado' => 'operativo',  'lat' => -34.9095, 'lng' => -56.1520],
        ];
    }

    protected function aObjeto(array $f): object
    {
        return new Contenedor(
            $f['id'],
            $f['codigo'],
            $f['direccion'] ?? '',
            $f['tipoResiduo'],
            $f['estado'],
            isset($f['lat']) && $f['lat'] !== null ? (float)$f['lat'] : null,
            isset($f['lng']) && $f['lng'] !== null ? (float)$f['lng'] : null
        );
    }

    private function aFila(Contenedor $c): array
    {
        return [
            'id'          => $c->getId(),
            'codigo'      => $c->getCodigo(),
            'direccion'   => $c->getDireccion(),
            'tipoResiduo' => $c->getTipoResiduo(),
            'estado'      => $c->getEstado(),
            'lat'         => $c->getLat(),
            'lng'         => $c->getLng(),
        ];
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Contenedor $c): void
    {
        $filas = $this->leerCrudo();
        $filas[] = $this->aFila($c);
        $this->guardarCrudo($filas);
    }

    /** Actualiza un contenedor existente. Devuelve true si existía. */
    public function actualizar(Contenedor $c): bool
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
}
