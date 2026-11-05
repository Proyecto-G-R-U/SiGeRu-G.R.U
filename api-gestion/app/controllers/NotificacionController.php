<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioNotificaciones;

/**
 * NotificacionController: la campana del encabezado.
 *
 *   listar()       -> GET  /notificaciones
 *   marcar()       -> POST /notificaciones/leer
 *   marcarTodas()  -> POST /notificaciones/leer-todas
 *
 * Todo lo que hace es sobre las notificaciones DEL USUARIO LOGUEADO. El
 * id del usuario nunca se toma del cuerpo del pedido: sale del token. Si
 * se aceptara por parámetro, cualquiera podría leer o marcar las de otro
 * mandando un número distinto.
 */
class NotificacionController extends ControladorBase
{
    private RepositorioNotificaciones $repo;

    public function __construct(RepositorioNotificaciones $repo)
    {
        $this->repo = $repo;
    }

    /**
     * GET /notificaciones?soloNoLeidas=1
     *
     * Devuelve { notificaciones: [...], noLeidas: n }. El contador viene
     * en la misma respuesta porque la campana lo necesita siempre, y así
     * la pantalla no hace dos pedidos para pintar una sola cosa.
     */
    public function listar(): void
    {
        $usuario = $this->exigirLogin();
        $soloNoLeidas = ($_GET['soloNoLeidas'] ?? '') === '1';
        $id = (int)$usuario['id'];

        $this->responder([
            'notificaciones' => $this->repo->paraUsuario($id, $soloNoLeidas),
            'noLeidas'       => $this->repo->contarNoLeidas($id),
        ], 200);
    }

    /** POST /notificaciones/leer — marca una como leída. Recibe { id }. */
    public function marcar(): void
    {
        $usuario = $this->exigirLogin();
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);

        if ($id <= 0) {
            $this->error('Falta el id de la notificación.', 400);
        }
        // El repositorio filtra también por usuario: si la notificación es
        // de otro, para este usuario es como si no existiera.
        if (!$this->repo->marcarLeida($id, (int)$usuario['id'])) {
            $this->error('No existe esa notificación.', 404);
        }

        $this->responder([
            'mensaje'  => 'Notificación marcada como leída.',
            'noLeidas' => $this->repo->contarNoLeidas((int)$usuario['id']),
        ], 200);
    }

    /** POST /notificaciones/leer-todas */
    public function marcarTodas(): void
    {
        $usuario = $this->exigirLogin();
        $n = $this->repo->marcarTodasLeidas((int)$usuario['id']);
        $this->responder([
            'mensaje'  => $n === 0 ? 'No había notificaciones sin leer.'
                                   : $n . ' notificación(es) marcadas como leídas.',
            'marcadas' => $n,
            'noLeidas' => 0,
        ], 200);
    }
}
