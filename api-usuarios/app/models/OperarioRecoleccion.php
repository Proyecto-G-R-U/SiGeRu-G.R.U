<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Operario de Recolección (color naranja en la interfaz).
 *
 * HEREDA de Operario. Lo único propio es su especialidad. Todo lo demás
 * (cuadrilla, rol, verificarPassword, etc.) lo hereda de Operario y Usuario.
 *
 * Sus funciones (3.1.2.1): ver rutas asignadas, consultar el camión de su
 * turno, reportar novedades y ver el mapa de contenedores.
 */
class OperarioRecoleccion extends Operario
{
    public function getEspecialidad(): string
    {
        return 'recoleccion';
    }
}
