<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioUsuarios;
use App\Models\Administrador;
use App\Models\Operario;
use App\Models\OperarioRecoleccion;
use App\Models\OperarioClasificacion;
use App\Models\OperarioVertedero;
use App\Models\Vecino;

/**
 * UsuarioController: la lógica de la API de Usuarios.
 *
 * En esta versión SIN core, el controlador arma la respuesta JSON por su
 * cuenta (con los helpers privados responder() y error() de más abajo),
 * en lugar de usar una clase Respuesta separada.
 */
class UsuarioController extends ControladorBase
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
            'operario'      => $this->crearOperario(
                $id, $nombre, $email, $hash,
                $datos['especialidad'] ?? 'recoleccion',
<<<<<<< HEAD
                $datos['cuadrilla'] ?? null,
                isset($datos['instalacionId']) && $datos['instalacionId'] !== '' ? (int)$datos['instalacionId'] : null
=======
                $datos['cuadrilla'] ?? null
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
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

    /**
     * Crea la subclase de Operario correcta según la especialidad.
     * Centraliza la decisión en un solo lugar (fábrica simple).
     */
<<<<<<< HEAD
    private function crearOperario(int $id, string $nombre, string $email, string $hash, string $especialidad, ?string $cuadrilla, ?int $instalacionId = null): Operario
    {
        return match ($especialidad) {
            'clasificacion' => new OperarioClasificacion($id, $nombre, $email, $hash, $cuadrilla, $instalacionId),
            'vertedero'     => new OperarioVertedero($id, $nombre, $email, $hash, $cuadrilla, $instalacionId),
            default         => new OperarioRecoleccion($id, $nombre, $email, $hash, $cuadrilla, $instalacionId),
=======
    private function crearOperario(int $id, string $nombre, string $email, string $hash, string $especialidad, ?string $cuadrilla): Operario
    {
        return match ($especialidad) {
            'clasificacion' => new OperarioClasificacion($id, $nombre, $email, $hash, $cuadrilla),
            'vertedero'     => new OperarioVertedero($id, $nombre, $email, $hash, $cuadrilla),
            default         => new OperarioRecoleccion($id, $nombre, $email, $hash, $cuadrilla),
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
        };
    }

    /**
     * POST /usuarios/modificar — edita un usuario existente.
     * Recibe { id, nombre, email, rol, especialidad?, password? }.
     * Si password viene vacío, se conserva la contraseña actual.
     */
    public function modificar(): void
    {
        $datos = $this->leerJson();
        $id       = (int)($datos['id'] ?? 0);
        $nombre   = trim($datos['nombre'] ?? '');
        $email    = trim($datos['email'] ?? '');
        $rol      = $datos['rol'] ?? 'vecino';
        $password = $datos['password'] ?? '';

        if ($id <= 0) {
            $this->error('Falta el id del usuario.', 400);
        }
        if ($nombre === '' || $email === '') {
            $this->error('Nombre y email son obligatorios.', 400);
        }

        $existente = $this->repo->buscarPorId($id);
        if ($existente === null) {
            $this->error('No existe un usuario con ese id.', 404);
        }

        // Si cambia el email, verificar que no choque con otro usuario.
        $otro = $this->repo->buscarPorEmail($email);
        if ($otro !== null && $otro->getId() !== $id) {
            $this->error('Ya existe otro usuario con ese email.', 409);
        }

        // Hash nuevo solo si el admin escribió una contraseña.
        $hashNuevo = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
        // Para reconstruir el objeto necesitamos un hash; si no cambia, usamos
        // el actual (el repositorio igual conserva el viejo al persistir).
        $hashParaObjeto = $hashNuevo ?? $existente->getPasswordHash();

        $usuario = match ($rol) {
            'administrador' => new Administrador($id, $nombre, $email, $hashParaObjeto),
            'operario'      => $this->crearOperario(
                $id, $nombre, $email, $hashParaObjeto,
                $datos['especialidad'] ?? 'recoleccion',
<<<<<<< HEAD
                $datos['cuadrilla'] ?? null,
                isset($datos['instalacionId']) && $datos['instalacionId'] !== '' ? (int)$datos['instalacionId'] : null
=======
                $datos['cuadrilla'] ?? null
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
            ),
            default         => new Vecino($id, $nombre, $email, $hashParaObjeto),
        };

        $this->repo->actualizar($usuario, $hashNuevo);

        $this->responder(['mensaje' => 'Usuario modificado correctamente.', 'usuario' => $usuario], 200);
    }

}
