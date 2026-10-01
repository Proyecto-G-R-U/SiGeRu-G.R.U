<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * ControladorBase: clase base ABSTRACTA de todos los controladores.
 *
 * Concentra los tres helpers que todos los controladores repetían:
 * leer el JSON del pedido, responder JSON y responder un error.
 * Cada controlador hijo hereda estos métodos y solo define sus acciones
 * (login, listar, crear, etc.).
 */
abstract class ControladorBase
{
    /** Lee y decodifica el cuerpo JSON del pedido HTTP. */
    protected function leerJson(): array
    {
        $datos = json_decode(file_get_contents('php://input'), true);
        return is_array($datos) ? $datos : [];
    }

    /** Responde JSON con un código de estado HTTP y corta la ejecución. */
    protected function responder(mixed $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /** Atajo para responder un error con su código. */
    protected function error(string $mensaje, int $codigo = 400): void
    {
        $this->responder(['error' => $mensaje], $codigo);
    }

    // ---------------- Autenticación y permisos ----------------

    /**
     * Lee el token del encabezado "Authorization: Bearer <token>" y devuelve
     * los datos del usuario, o null si no hay token válido.
     */
    protected function usuarioAutenticado(): ?array
    {
        $cabecera = '';
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $cabecera = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            // Apache a veces mueve la cabecera al reescribir la URL.
            $cabecera = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('getallheaders')) {
            foreach (getallheaders() as $nombre => $valor) {
                if (strtolower($nombre) === 'authorization') {
                    $cabecera = $valor;
                    break;
                }
            }
        }

        if (!str_starts_with($cabecera, 'Bearer ')) {
            return null;
        }
        return (new \App\Models\Autenticacion())->porToken(trim(substr($cabecera, 7)));
    }

    /** Exige que haya sesión iniciada. Corta con 401 si no la hay. */
    protected function exigirLogin(): array
    {
        $u = $this->usuarioAutenticado();
        if ($u === null) {
            $this->error('Necesitás iniciar sesión para hacer esto.', 401);
        }
        return $u;
    }

    /**
     * Exige que el usuario tenga uno de los roles indicados.
     * 401 si no hay sesión, 403 si la hay pero sin permiso.
     */
    protected function exigirRol(array $rolesPermitidos): array
    {
        $u = $this->exigirLogin();
        if (!in_array($u['rol'], $rolesPermitidos, true)) {
            $this->error('No tenés permiso para realizar esta acción.', 403);
        }
        return $u;
    }

    /** Atajo: solo administradores. */
    protected function exigirAdmin(): array
    {
        return $this->exigirRol(['administrador']);
    }
}
