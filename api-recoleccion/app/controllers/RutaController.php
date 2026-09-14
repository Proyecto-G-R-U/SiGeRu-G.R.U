<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioRutas;
use App\Models\Ruta;

/**
 * RutaController: ABM de rutas de recolección y armado de su recorrido
 * (requerimiento 3.1.2.1.1).
 *   listar()             -> GET  /rutas
 *   porCuadrilla()       -> GET  /rutas/cuadrilla?id=N
 *   crear()              -> POST /rutas
 *   modificar()          -> POST /rutas/modificar
 *   eliminar()           -> POST /rutas/eliminar
 *   agregarContenedor()  -> POST /rutas/agregar-contenedor
 *   quitarContenedor()   -> POST /rutas/quitar-contenedor
 */
class RutaController extends ControladorBase
{
    private RepositorioRutas $repo;

    public function __construct(RepositorioRutas $repo)
    {
        $this->repo = $repo;
    }

    public function listar(): void
    {
        $this->responder($this->repo->todos(), 200);
    }

    /** Rutas de una cuadrilla: lo que ve el operario de recolección. */
    public function porCuadrilla(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Falta el id de la cuadrilla.', 400);
        }
        $this->responder($this->repo->porCuadrilla($id), 200);
    }

    public function crear(): void
    {
        $datos = $this->leerJson();
        $nombre = trim($datos['nombre'] ?? '');
        $zona   = trim($datos['zona'] ?? '');
        $dia    = $datos['diaSemana'] ?? 'lunes';
        $turno  = $datos['turno'] ?? 'matutino';
        $cuadrillaId = isset($datos['cuadrillaId']) && $datos['cuadrillaId'] !== ''
            ? (int)$datos['cuadrillaId'] : null;

        if ($nombre === '') {
            $this->error('El nombre de la ruta es obligatorio.', 400);
        }

        $ruta = new Ruta($this->repo->proximoId(), $nombre, $zona !== '' ? $zona : null,
                         $dia, $turno, $cuadrillaId);
        $this->repo->agregar($ruta);

        $this->responder(['mensaje' => 'Ruta creada correctamente.', 'ruta' => $ruta], 201);
    }

    public function modificar(): void
    {
        $datos = $this->leerJson();
        $id     = (int)($datos['id'] ?? 0);
        $nombre = trim($datos['nombre'] ?? '');
        $zona   = trim($datos['zona'] ?? '');
        $dia    = $datos['diaSemana'] ?? 'lunes';
        $turno  = $datos['turno'] ?? 'matutino';
        $cuadrillaId = isset($datos['cuadrillaId']) && $datos['cuadrillaId'] !== ''
            ? (int)$datos['cuadrillaId'] : null;

        if ($id <= 0) {
            $this->error('Falta el id de la ruta.', 400);
        }
        if ($nombre === '') {
            $this->error('El nombre de la ruta es obligatorio.', 400);
        }

        $ruta = new Ruta($id, $nombre, $zona !== '' ? $zona : null, $dia, $turno, $cuadrillaId);
        if (!$this->repo->actualizar($ruta)) {
            $this->error('No existe una ruta con ese id.', 404);
        }

        $this->responder(['mensaje' => 'Ruta modificada correctamente.'], 200);
    }

    public function eliminar(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Falta el id de la ruta.', 400);
        }
        if (!$this->repo->eliminar($id)) {
            $this->error('No existe una ruta con ese id.', 404);
        }
        $this->responder(['mensaje' => 'Ruta eliminada.'], 200);
    }

    public function agregarContenedor(): void
    {
        $datos = $this->leerJson();
        $rutaId = (int)($datos['rutaId'] ?? 0);
        $contenedorId = (int)($datos['contenedorId'] ?? 0);

        if ($rutaId <= 0 || $contenedorId <= 0) {
            $this->error('Faltan datos (rutaId, contenedorId).', 400);
        }
        if (!$this->repo->agregarContenedor($rutaId, $contenedorId)) {
            $this->error('No existe una ruta con ese id.', 404);
        }
        $this->responder(['mensaje' => 'Contenedor agregado al recorrido.'], 200);
    }

    public function quitarContenedor(): void
    {
        $datos = $this->leerJson();
        $rutaId = (int)($datos['rutaId'] ?? 0);
        $contenedorId = (int)($datos['contenedorId'] ?? 0);

        if ($rutaId <= 0 || $contenedorId <= 0) {
            $this->error('Faltan datos (rutaId, contenedorId).', 400);
        }
        $this->repo->quitarContenedor($rutaId, $contenedorId);
        $this->responder(['mensaje' => 'Contenedor quitado del recorrido.'], 200);
    }
}
