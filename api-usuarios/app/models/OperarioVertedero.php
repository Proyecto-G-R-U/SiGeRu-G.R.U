<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Operario de Vertedero (color amarillo en la interfaz).
 *
 * HEREDA de Operario. Solo define su especialidad.
 *
 * Sus funciones (3.1.2.3): ver niveles de capacidad de las celdas del
 * vertedero y monitorear la maquinaria pesada asignada.
 */
class OperarioVertedero extends Operario
{
    public function getEspecialidad(): string
    {
        return 'vertedero';
    }
}
