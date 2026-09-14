<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Administrador municipal.
 *
 * HEREDA de Usuario (la palabra clave es "extends"). Eso significa que
 * ya tiene id, nombre, email, verificarPassword(), etc. sin escribirlos
 * de nuevo. Solo agrega lo propio del rol administrador.
 *
 * Es quien gestiona personal, contenedores, flota, incidencias y reportes
 * (todo el punto 3.1.1 y 3.1.4 de los requerimientos).
 */
class Administrador extends Usuario
{
    // Cada subclase responde con su propio rol (polimorfismo).
    public function getRol(): string
    {
        return 'administrador';
    }
}
