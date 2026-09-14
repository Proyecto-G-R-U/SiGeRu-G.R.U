<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Operario de Clasificación (color violeta en la interfaz).
 *
 * HEREDA de Operario. Solo define su especialidad.
 *
 * Sus funciones (3.1.2.2): ver estado de la planta, consultar inventario
 * de maquinaria, reportar novedades y ver la guía de clasificación.
 */
class OperarioClasificacion extends Operario
{
    public function getEspecialidad(): string
    {
        return 'clasificacion';
    }
}
