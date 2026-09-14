<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioIncidencias;
use App\Models\RepositorioContenedores;
use App\Models\Incidencia;

/**
 * IncidenciaController: registro y seguimiento de incidencias
 * (requerimientos 3.1.1.3.1 a 3.1.1.3.4).
 *
 *   listar()           -> GET  /incidencias           (activas, para el mapa)
 *   historial()        -> GET  /incidencias/historial  (resueltas)
 *   crear()            -> POST /incidencias            (cualquier usuario)
 *   cambiarEstado()    -> POST /incidencias/estado     (admin)
 *   asignarCuadrilla() -> POST /incidencias/asignar-cuadrilla (admin)
 *   eliminar()         -> POST /incidencias/eliminar   (admin)
 */
class IncidenciaController extends ControladorBase
{
    private RepositorioIncidencias $repo;
    private RepositorioContenedores $repoContenedores;

    public function __construct(RepositorioIncidencias $repo, RepositorioContenedores $repoContenedores)
    {
        $this->repo = $repo;
        $this->repoContenedores = $repoContenedores;
    }

    /** Incidencias activas (las que se dibujan en el mapa). */
    public function listar(): void
    {
        $this->responder($this->repo->activas(), 200);
    }

    /** Historial de incidencias resueltas. */
    public function historial(): void
    {
        $this->responder($this->repo->historial(), 200);
    }

    /**
     * Registra una incidencia. La puede crear cualquier usuario logueado
     * (vecino, operario o admin) desde el mapa.
     * Recibe { contenedorId, tipo, descripcion, gravedad, reportadoPor, reportadoPorNombre }.
     */
    public function crear(): void
    {
        $datos = $this->leerJson();
        $contenedorId = (int)($datos['contenedorId'] ?? 0);
        $tipo         = $datos['tipo'] ?? 'otro';
        $descripcion  = trim($datos['descripcion'] ?? '');
        $gravedad     = $datos['gravedad'] ?? 'media';
        $reportadoPor = isset($datos['reportadoPor']) && $datos['reportadoPor'] !== ''
            ? (int)$datos['reportadoPor'] : null;
        $reportadoPorNombre = trim($datos['reportadoPorNombre'] ?? 'Anónimo');

        if ($contenedorId <= 0) {
            $this->error('Elegí un contenedor en el mapa antes de reportar.', 400);
        }
        if ($this->repoContenedores->buscarPorId($contenedorId) === null) {
            $this->error('El contenedor indicado no existe.', 404);
        }
        if ($descripcion === '') {
            $this->error('Escribí una breve descripción del problema.', 400);
        }

        $incidencia = new Incidencia(
            $this->repo->proximoId(),
            $contenedorId,
            $tipo,
            $descripcion,
            $gravedad,
            'abierta',              // toda incidencia nace abierta
            null,                   // sin cuadrilla hasta que el admin asigne
            $reportadoPor,
            $reportadoPorNombre
        );
        $this->repo->agregar($incidencia);

        $this->responder(['mensaje' => 'Incidencia reportada. Gracias por avisar.'], 201);
    }

    /**
     * POST /incidencias/estado — cambia el estado (abierta / en_curso / resuelta).
     * Al pasar a "resuelta" se guarda la fecha y la incidencia sale del mapa
     * activo hacia el historial.
     */
    public function cambiarEstado(): void
    {
        $datos = $this->leerJson();
        $id     = (int)($datos['id'] ?? 0);
        $estado = $datos['estado'] ?? '';

        if ($id <= 0) {
            $this->error('Falta el id de la incidencia.', 400);
        }
        if (!in_array($estado, ['abierta', 'en_curso', 'resuelta'], true)) {
            $this->error('Estado inválido.', 400);
        }
        if (!$this->repo->cambiarEstado($id, $estado)) {
            $this->error('No existe una incidencia con ese id.', 404);
        }

        $this->responder(['mensaje' => 'Estado actualizado.'], 200);
    }

    /** POST /incidencias/asignar-cuadrilla — asigna la cuadrilla responsable. */
    public function asignarCuadrilla(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        $cuadrillaId = isset($datos['cuadrillaId']) && $datos['cuadrillaId'] !== '' && $datos['cuadrillaId'] !== null
            ? (int)$datos['cuadrillaId'] : null;

        if ($id <= 0) {
            $this->error('Falta el id de la incidencia.', 400);
        }
        if (!$this->repo->asignarCuadrilla($id, $cuadrillaId)) {
            $this->error('No existe una incidencia con ese id.', 404);
        }

        $this->responder(['mensaje' => 'Cuadrilla responsable asignada.'], 200);
    }

    public function eliminar(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Falta el id de la incidencia.', 400);
        }
        if (!$this->repo->eliminar($id)) {
            $this->error('No existe una incidencia con ese id.', 404);
        }
        $this->responder(['mensaje' => 'Incidencia eliminada.'], 200);
    }
}
