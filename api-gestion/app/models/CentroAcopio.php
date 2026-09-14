<?php
declare(strict_types=1);

namespace App\Models;

/**
 * CentroAcopio: lugar donde se reciben y acumulan residuos reciclables
 * antes de enviarlos a clasificación. Subclase concreta de Instalacion.
 */
class CentroAcopio extends Instalacion
{
    public function getTipo(): string
    {
        return 'acopio';
    }

    public function getTipoLegible(): string
    {
        return 'Centro de acopio';
    }
}
