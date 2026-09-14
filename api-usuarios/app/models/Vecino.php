<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Vecino.
 *
 * HEREDA de Usuario. A diferencia del personal interno (que da de alta
 * el administrador), el vecino se AUTO-REGISTRA desde la landing page
 * (requerimiento 3.1.3.1). Por eso lo distinguimos como su propio tipo.
 *
 * Sus funciones: ver el mapa, consultar contenedores, reportar problemas
 * y ver la guía de reciclaje (3.1.3.2 a 3.1.3.5).
 */
class Vecino extends Usuario
{
    public function getRol(): string
    {
        return 'vecino';
    }
}
