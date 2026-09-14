<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Vertedero: destino final de los residuos no recuperables.
 * Subclase concreta de Instalacion.
 */
class Vertedero extends Instalacion
{
    public function getTipo(): string
    {
        return 'vertedero';
    }

    public function getTipoLegible(): string
    {
        return 'Vertedero';
    }
}
