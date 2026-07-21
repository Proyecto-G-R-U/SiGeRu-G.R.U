<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioFlotas: persistencia de flotas en MySQL.
 * Hereda el motor común de RepositorioSql; acá solo lo específico.
 * (Los datos de prueba los inserta base.sql.)
 */
class RepositorioFlotas extends RepositorioSql
{
    protected function tabla(): string
    {
        return 'flota';
    }

    protected function aObjeto(array $f): object
    {
        return new Flota((int)$f['id'], $f['nombre'], $f['zona'] ?? null);
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Flota $flota): void
    {
        $this->consulta(
            'INSERT INTO flota (id, nombre, zona) VALUES (?, ?, ?)',
            [$flota->getId(), $flota->getNombre(), $flota->getZona()]
        );
    }
}
