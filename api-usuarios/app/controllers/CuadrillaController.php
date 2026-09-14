<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioCuadrillas;
use App\Models\RepositorioUsuarios;
use App\Models\Cuadrilla;

/**
 * CuadrillaController: gestión de cuadrillas (solo admin desde el frontend).
 *   listar()          -> GET  /cuadrillas
 *   crear()           -> POST /cuadrillas
 *   eliminar()        -> POST /cuadrillas/eliminar
 *   agregarOperario() -> POST /cuadrillas/agregar-operario
 *   quitarOperario()  -> POST /cuadrillas/quitar-operario
 *   operariosLibres() -> GET  /operarios-libres
 */
class CuadrillaController extends ControladorBase
{
    private RepositorioCuadrillas $repo;
    private RepositorioUsuarios $repoUsuarios;

    public function __construct(RepositorioCuadrillas $repo, RepositorioUsuarios $repoUsuarios)
    {
        $this->repo = $repo;
        $this->repoUsuarios = $repoUsuarios;
    }

    public function listar(): void
    {
        $this->responder($this->repo->todos(), 200);
    }

    public function crear(): void
    {
        $datos = $this->leerJson();
        $nombre = trim($datos['nombre'] ?? '');
        $zona   = trim($datos['zona'] ?? '');
        // operarios: array de ids (opcional al crear)
        $operarios = is_array($datos['operarios'] ?? null) ? array_map('intval', $datos['operarios']) : [];

        if ($nombre === '') {
            $this->error('El nombre de la cuadrilla es obligatorio.', 400);
        }

        $cuadrilla = new Cuadrilla($this->repo->proximoId(), $nombre, $zona !== '' ? $zona : null, $operarios);
        $this->repo->agregar($cuadrilla);

        $this->responder(['mensaje' => 'Cuadrilla creada correctamente.', 'cuadrilla' => $cuadrilla], 201);
    }

    public function eliminar(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Falta el id de la cuadrilla.', 400);
        }
        if (!$this->repo->eliminar($id)) {
            $this->error('No existe una cuadrilla con ese id.', 404);
        }
        $this->responder(['mensaje' => 'Cuadrilla eliminada.'], 200);
    }

    /**
     * POST /cuadrillas/agregar-operario — agrega un operario de recolección a
     * una cuadrilla. Verifica que sea operario de recolección. La exclusividad
     * (quitarlo de otra cuadrilla) la garantiza el repositorio.
     * Recibe { cuadrillaId, operarioId }.
     */
    public function agregarOperario(): void
    {
        $datos = $this->leerJson();
        $cuadrillaId = (int)($datos['cuadrillaId'] ?? 0);
        $operarioId  = (int)($datos['operarioId'] ?? 0);

        if ($cuadrillaId <= 0 || $operarioId <= 0) {
            $this->error('Faltan datos (cuadrillaId, operarioId).', 400);
        }

        // Verificamos que el usuario exista y sea operario DE RECOLECCIÓN.
        $usuario = $this->repoUsuarios->buscarPorId($operarioId);
        if ($usuario === null) {
            $this->error('El operario no existe.', 404);
        }
        $esRecoleccion = method_exists($usuario, 'getEspecialidad')
            && $usuario->getEspecialidad() === 'recoleccion';
        if (!$esRecoleccion) {
            $this->error('Solo los operarios de recolección pueden integrar cuadrillas.', 400);
        }

        if (!$this->repo->agregarOperario($cuadrillaId, $operarioId)) {
            $this->error('No existe la cuadrilla.', 404);
        }
        $this->responder(['mensaje' => 'Operario agregado a la cuadrilla.'], 200);
    }

    /**
     * POST /cuadrillas/quitar-operario — quita un operario de una cuadrilla.
     * Recibe { cuadrillaId, operarioId }.
     */
    public function quitarOperario(): void
    {
        $datos = $this->leerJson();
        $cuadrillaId = (int)($datos['cuadrillaId'] ?? 0);
        $operarioId  = (int)($datos['operarioId'] ?? 0);

        if ($cuadrillaId <= 0 || $operarioId <= 0) {
            $this->error('Faltan datos (cuadrillaId, operarioId).', 400);
        }
        if (!$this->repo->quitarOperario($cuadrillaId, $operarioId)) {
            $this->error('No existe la cuadrilla.', 404);
        }
        $this->responder(['mensaje' => 'Operario quitado de la cuadrilla.'], 200);
    }

    /**
     * GET /operarios-libres — lista los operarios de recolección que NO están
     * en ninguna cuadrilla (para el selector de "agregar operario").
     */
    public function operariosLibres(): void
    {
        $ocupados = $this->repo->operariosOcupados();
        $libres = [];
        foreach ($this->repoUsuarios->todos() as $u) {
            $esRecoleccion = method_exists($u, 'getEspecialidad')
                && $u->getEspecialidad() === 'recoleccion';
            if ($esRecoleccion && !in_array($u->getId(), $ocupados, true)) {
                $libres[] = $u;
            }
        }
        $this->responder($libres, 200);
    }

}
