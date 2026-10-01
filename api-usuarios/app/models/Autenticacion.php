<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Autenticacion: busca al usuario dueño de un token de sesión.
 *
 * Cada API necesita saber QUIÉN está haciendo el pedido para decidir si
 * tiene permiso. Como las tres APIs comparten la base, cada una puede
 * resolver el token contra la tabla `usuario` sin depender de las otras.
 *
 * Devuelve un array simple (no un objeto Usuario) porque acá solo importan
 * el id, el rol y la especialidad para decidir permisos.
 */
class Autenticacion extends RepositorioSql
{
    protected function tabla(): string
    {
        return 'usuario';
    }

    protected function aObjeto(array $f): object
    {
        return (object)$f;
    }

    /** Devuelve los datos del usuario dueño del token, o null si no existe. */
    public function porToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        return $this->fila(
            'SELECT id, nombre, email, rol, especialidad, cuadrilla_id, instalacion_id
             FROM usuario WHERE token = ?',
            [$token]
        );
    }
}
