<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioFlotas: persistencia de flotas en archivo JSON.
 * Hereda el motor común de RepositorioJson; acá solo lo específico.
 */
class RepositorioFlotas extends RepositorioJson
{
    protected function nombreArchivo(): string
    {
        return 'flotas.json';
    }

    protected function datosIniciales(): array
    {
        return [
            ['id' => 1, 'nombre' => 'Flota Norte', 'zona' => 'Zona Norte'],
            ['id' => 2, 'nombre' => 'Flota Sur',   'zona' => 'Zona Sur'],
        ];
    }

    protected function aObjeto(array $f): object
    {
        return new Flota($f['id'], $f['nombre'], $f['zona'] ?? null);
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Flota $flota): void
    {
        $filas = $this->leerCrudo();
        $filas[] = [
            'id'     => $flota->getId(),
            'nombre' => $flota->getNombre(),
            'zona'   => $flota->getZona(),
        ];
        $this->guardarCrudo($filas);
    }
}
