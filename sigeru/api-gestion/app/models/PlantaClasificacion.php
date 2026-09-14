<?php
declare(strict_types=1);

namespace App\Models;

/**
 * PlantaClasificacion: instalación donde los residuos se separan por tipo
 * para su reciclaje. Subclase concreta de Instalacion.
 */
class PlantaClasificacion extends Instalacion
{
    public function getTipo(): string
    {
        return 'clasificacion';
    }

    public function getTipoLegible(): string
    {
        return 'Planta de clasificación';
    }
}
