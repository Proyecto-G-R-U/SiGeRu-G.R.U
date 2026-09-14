<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioCamiones;
use App\Models\Camion;

/**
 * CamionController: gestión de camiones.
 *   listar()          -> GET  /camiones
 *   crear()           -> POST /camiones
 *   eliminar()        -> POST /camiones/eliminar
 *   asignarCuadrilla()-> POST /camiones/asignar-cuadrilla
 */
class CamionController extends ControladorBase
{
    private RepositorioCamiones $repo;

    public function __construct(RepositorioCamiones $repo)
    {
        $this->repo = $repo;
    }

    public function listar(): void
    {
        $this->responder($this->repo->todos(), 200);
    }

    public function crear(): void
    {
        $datos = $this->leerJson();
        $patente        = trim($datos['patente'] ?? '');
        $modelo         = trim($datos['modelo'] ?? '');
        $estado         = $datos['estado'] ?? 'operativo';
        $disponibilidad = $datos['disponibilidad'] ?? 'disponible';
        $flotaId        = isset($datos['flotaId']) && $datos['flotaId'] !== '' ? (int)$datos['flotaId'] : null;
        $cuadrillaId    = isset($datos['cuadrillaId']) && $datos['cuadrillaId'] !== '' ? (int)$datos['cuadrillaId'] : null;

        if ($patente === '' || $modelo === '') {
            $this->error('Patente y modelo son obligatorios.', 400);
        }

        $camion = new Camion(
            $this->repo->proximoId(),
            $patente,
            $modelo,
            $estado,
            $disponibilidad,
            $flotaId,
            $cuadrillaId
        );
        $this->repo->agregar($camion);

        $this->responder(['mensaje' => 'Camión creado correctamente.', 'camion' => $camion], 201);
    }

    public function eliminar(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Falta el id del camión.', 400);
        }
        if (!$this->repo->eliminar($id)) {
            $this->error('No existe un camión con ese id.', 404);
        }
        $this->responder(['mensaje' => 'Camión eliminado.'], 200);
    }

    /**
     * POST /camiones/modificar — edita los datos básicos de un camión.
     * Recibe { id, patente, modelo, estado, disponibilidad }.
     * Conserva la flota y la cuadrilla que ya tenía asignadas.
     */
    public function modificar(): void
    {
        $datos = $this->leerJson();
        $id             = (int)($datos['id'] ?? 0);
        $patente        = trim($datos['patente'] ?? '');
        $modelo         = trim($datos['modelo'] ?? '');
        $estado         = $datos['estado'] ?? 'operativo';
        $disponibilidad = $datos['disponibilidad'] ?? 'disponible';

        if ($id <= 0) {
            $this->error('Falta el id del camión.', 400);
        }
        if ($patente === '' || $modelo === '') {
            $this->error('Patente y modelo son obligatorios.', 400);
        }

        $existente = $this->repo->buscarPorId($id);
        if ($existente === null) {
            $this->error('No existe un camión con ese id.', 404);
        }

        // Conservamos flota y cuadrilla actuales (se cambian con sus propios selectores).
        $camion = new Camion(
            $id, $patente, $modelo, $estado, $disponibilidad,
            $existente->getFlotaId(),
            $existente->getCuadrillaId()
        );
        $this->repo->actualizar($camion);

        $this->responder(['mensaje' => 'Camión modificado correctamente.', 'camion' => $camion], 200);
    }

    /**
     * POST /camiones/asignar-cuadrilla — asigna (o quita) la cuadrilla de un
     * camión. Recibe { id, cuadrillaId }. cuadrillaId null quita la asignación.
     */
    public function asignarCuadrilla(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        $cuadrillaId = isset($datos['cuadrillaId']) && $datos['cuadrillaId'] !== '' && $datos['cuadrillaId'] !== null
            ? (int)$datos['cuadrillaId'] : null;

        // Exclusividad: la cuadrilla queda solo en este camión.
        if (!$this->repo->asignarCuadrillaExclusiva($id, $cuadrillaId)) {
            $this->error('No existe un camión con ese id.', 404);
        }

        $camion = $this->repo->buscarPorId($id);
        $this->responder(['mensaje' => 'Cuadrilla asignada al camión.', 'camion' => $camion], 200);
    }

    /**
     * POST /camiones/asignar-flota — mueve un camión a una flota (o lo quita).
     * Recibe { id, flotaId }. flotaId null lo deja sin flota.
     */
    public function asignarFlota(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        $flotaId = isset($datos['flotaId']) && $datos['flotaId'] !== '' && $datos['flotaId'] !== null
            ? (int)$datos['flotaId'] : null;

        $camion = $this->repo->buscarPorId($id);
        if ($camion === null) {
            $this->error('No existe un camión con ese id.', 404);
        }

        $camion->setFlotaId($flotaId);
        $this->repo->actualizar($camion);

        $this->responder(['mensaje' => 'Camión movido de flota.', 'camion' => $camion], 200);
    }

}
