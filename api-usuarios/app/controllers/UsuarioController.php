<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioUsuarios;
use App\Models\Administrador;
use App\Models\Operario;
use App\Models\Vecino;

/**
 * UsuarioController: la lógica de la API de Usuarios.
 *
 * En esta versión SIN core, el controlador arma la respuesta JSON por su
 * cuenta (con los helpers privados responder() y error() de más abajo),
 * en lugar de usar una clase Respuesta separada.
 */
class UsuarioController
{
    private RepositorioUsuarios $repo;

    public function __construct(RepositorioUsuarios $repo)
    {
        $this->repo = $repo;
    }

    /** POST /login — valida { email, password }. */
    public function login(): void
    {
        $datos = $this->leerJson();
        $email    = trim($datos['email'] ?? '');
        $password = $datos['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->error('Faltan email o contraseña.', 400);
        }

        $usuario = $this->repo->buscarPorEmail($email);

        if ($usuario === null || !$usuario->verificarPassword($password)) {
            $this->error('Credenciales inválidas.', 401);
        }

        $this->responder([
            'mensaje' => 'Inicio de sesión correcto.',
            'usuario' => $usuario,
        ], 200);
    }

    /** POST /usuarios — registro / alta de usuario. */
    public function registrar(): void
    {
        $datos = $this->leerJson();
        $nombre   = trim($datos['nombre'] ?? '');
        $email    = trim($datos['email'] ?? '');
        $password = $datos['password'] ?? '';
        $rol      = $datos['rol'] ?? 'vecino';

        if ($nombre === '' || $email === '' || $password === '') {
            $this->error('Nombre, email y contraseña son obligatorios.', 400);
        }
        if ($this->repo->buscarPorEmail($email) !== null) {
            $this->error('Ya existe un usuario con ese email.', 409);
        }

        $id   = $this->repo->proximoId();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $usuario = match ($rol) {
            'administrador' => new Administrador($id, $nombre, $email, $hash),
            'operario'      => new Operario(
                $id, $nombre, $email, $hash,
                $datos['especialidad'] ?? 'recoleccion',
                $datos['cuadrilla'] ?? null
            ),
            default         => new Vecino($id, $nombre, $email, $hash),
        };

        $this->repo->agregar($usuario);

        $this->responder([
            'mensaje' => 'Usuario registrado correctamente.',
            'usuario' => $usuario,
        ], 201);
    }

    /** GET /usuarios — listado completo. */
    public function listar(): void
    {
        $this->responder($this->repo->todos(), 200);
    }

    /**
     * POST /usuarios/eliminar — elimina un usuario por id.
     * Solo lo puede hacer un administrador.
     *
     * Como todavía no hay tokens de sesión (eso llega más adelante), pedimos
     * que el frontend mande el email del admin que está logueado. El backend
     * verifica que ese email exista Y sea de rol administrador antes de borrar.
     * Recibe: { id, adminEmail }
     */
    public function eliminar(): void
    {
        $datos = $this->leerJson();
        $id         = (int)($datos['id'] ?? 0);
        $adminEmail = trim($datos['adminEmail'] ?? '');

        if ($id <= 0) {
            $this->error('Falta el id del usuario a eliminar.', 400);
        }

        // --- Verificación de permisos: quien pide debe ser administrador ---
        $solicitante = $this->repo->buscarPorEmail($adminEmail);
        if ($solicitante === null || $solicitante->getRol() !== 'administrador') {
            $this->error('Solo un administrador puede eliminar usuarios.', 403);
        }

        // Regla de seguridad: un admin no puede eliminarse a sí mismo
        // (evita quedarse sin ningún administrador por accidente).
        if ($solicitante->getId() === $id) {
            $this->error('No podés eliminar tu propia cuenta de administrador.', 400);
        }

        $borrado = $this->repo->eliminar($id);
        if (!$borrado) {
            $this->error('No existe un usuario con ese id.', 404);
        }

        $this->responder(['mensaje' => 'Usuario eliminado correctamente.'], 200);
    }

    // ---------------- Helpers internos ----------------

    /** Lee y decodifica el cuerpo JSON del pedido. */
    private function leerJson(): array
    {
        $cuerpo = file_get_contents('php://input');
        $datos = json_decode($cuerpo, true);
        return is_array($datos) ? $datos : [];
    }

    /** Responde JSON con un código de estado y corta la ejecución. */
    private function responder(mixed $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /** Atajo para responder un error. */
    private function error(string $mensaje, int $codigo = 400): void
    {
        $this->responder(['error' => $mensaje], $codigo);
    }
}
