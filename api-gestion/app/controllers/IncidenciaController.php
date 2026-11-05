<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioIncidencias;
use App\Models\RepositorioNotificaciones;
use App\Models\RepositorioInstalaciones;
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
    private RepositorioNotificaciones $notis;
    private RepositorioInstalaciones $repoInstalaciones;

    public function __construct(
        RepositorioIncidencias $repo,
        RepositorioContenedores $repoContenedores,
        RepositorioNotificaciones $notis,
        RepositorioInstalaciones $repoInstalaciones
    ) {
        $this->repo = $repo;
        $this->repoContenedores = $repoContenedores;
        $this->notis = $notis;
        $this->repoInstalaciones = $repoInstalaciones;
    }

    /** Incidencias activas (las que se dibujan en el mapa). */
    public function listar(): void
    {
        $this->exigirLogin();
        $this->responder($this->repo->activas(), 200);
    }

    /**
     * GET /incidencias/historial — historial de incidencias (req. 3.1.1.3.4).
     *
     * Sin parámetros devuelve las resueltas, que es como venía funcionando.
     * Acepta filtros por la query string, todos opcionales:
     *
     *   ?estado=resuelta|abierta|en_curso|todas
     *   ?zona=Municipio+CH   (o 'sin-zona' para las que no tienen municipio)
     *   ?contenedorId=3
     *   ?gravedad=alta|media|baja
     *
     * Responde { incidencias: [...], porZona: [{zona, total}, ...] } para que
     * la pantalla pueda mostrar el resumen sin pedir una segunda consulta.
     */
    public function historial(): void
    {
        $this->exigirLogin();

        $estado = trim((string)($_GET['estado'] ?? 'resuelta'));
        if ($estado === 'todas') {
            $estado = null;                    // null = sin filtrar por estado
        } elseif ($estado !== '' && !in_array($estado, ['abierta', 'en_curso', 'resuelta'], true)) {
            $this->error('Estado inválido: ' . $estado, 400);
        }

        $gravedad = trim((string)($_GET['gravedad'] ?? ''));
        if ($gravedad !== '' && !in_array($gravedad, ['baja', 'media', 'alta'], true)) {
            $this->error('Gravedad inválida: ' . $gravedad, 400);
        }

        $zona         = trim((string)($_GET['zona'] ?? ''));
        $contenedorId = (int)($_GET['contenedorId'] ?? 0);

        $this->responder([
            'incidencias' => $this->repo->historialFiltrado(
                $estado === '' ? null : $estado,
                $zona === '' ? null : $zona,
                $contenedorId > 0 ? $contenedorId : null,
                $gravedad === '' ? null : $gravedad
            ),
            'porZona' => $this->repo->totalesPorZona($estado === '' ? null : $estado),
        ], 200);
    }

    /**
     * Registra una incidencia. La puede crear cualquier usuario logueado
     * (vecino, operario o admin) desde el mapa.
     * Recibe { contenedorId, tipo, descripcion, gravedad, reportadoPor, reportadoPorNombre }.
     */
    public function crear(): void
    {
        $this->exigirLogin();
        $datos = $this->leerJson();
        $contenedorId  = (int)($datos['contenedorId'] ?? 0);
        $instalacionId = (int)($datos['instalacionId'] ?? 0);
        $tipo         = $datos['tipo'] ?? 'otro';
        $descripcion  = trim($datos['descripcion'] ?? '');
        $gravedad     = $datos['gravedad'] ?? 'media';
        $reportadoPor = isset($datos['reportadoPor']) && $datos['reportadoPor'] !== ''
            ? (int)$datos['reportadoPor'] : null;
        $reportadoPorNombre = trim($datos['reportadoPorNombre'] ?? 'Anónimo');

        // Una incidencia refiere a un contenedor O a una instalación, nunca
        // a las dos ni a ninguna. Los operarios de recolección reportan
        // sobre contenedores (3.1.2.1.3) y los de clasificación sobre las
        // instalaciones donde trabajan (3.1.2.2.3). La base lo garantiza con
        // un CHECK; acá se valida antes para poder dar un error entendible
        // en vez de un 500 del motor.
        if ($contenedorId <= 0 && $instalacionId <= 0) {
            $this->error('Elegí un contenedor o una instalación en el mapa antes de reportar.', 400);
        }
        if ($contenedorId > 0 && $instalacionId > 0) {
            $this->error('Una incidencia refiere a un contenedor o a una instalación, no a las dos.', 400);
        }
        if ($contenedorId > 0 && $this->repoContenedores->buscarPorId($contenedorId) === null) {
            $this->error('El contenedor indicado no existe.', 404);
        }
        if ($instalacionId > 0 && $this->repoInstalaciones->buscarPorId($instalacionId) === null) {
            $this->error('La instalación indicada no existe.', 404);
        }
        if ($descripcion === '') {
            $this->error('Escribí una breve descripción del problema.', 400);
        }

        $incidencia = new Incidencia(
            $this->repo->proximoId(),
            $contenedorId > 0 ? $contenedorId : null,
            $tipo,
            $descripcion,
            $gravedad,
            'abierta',              // toda incidencia nace abierta
            null,                   // sin cuadrilla hasta que el admin asigne
            $reportadoPor,
            $reportadoPorNombre,
            '',
            null,
            $instalacionId > 0 ? $instalacionId : null
        );
        $this->repo->agregar($incidencia);

        // Aviso a los administradores: alguien tiene que verla y asignarla.
        // Se excluye a quien la reportó, si resulta ser admin: no tiene
        // sentido notificarle algo que acaba de hacer él mismo.
        $donde = $this->nombrarObjetivo($contenedorId, $instalacionId);
        $this->notis->avisarAAdmins(
            'incidencia_nueva',
            $gravedad === 'alta' ? 'Incidencia grave sin asignar' : 'Incidencia nueva',
            $incidencia->getTipoLegible() . ' en ' . $donde . '. Reportó ' . $reportadoPorNombre . '.',
            $gravedad,
            $incidencia->getId(),
            $reportadoPor
        );

        $this->responder(['mensaje' => 'Incidencia reportada. Gracias por avisar.'], 201);
    }

    /**
     * POST /incidencias/estado — cambia el estado (abierta / en_curso / resuelta).
     * Al pasar a "resuelta" se guarda la fecha y la incidencia sale del mapa
     * activo hacia el historial.
     */
    public function cambiarEstado(): void
    {
        $this->exigirAdmin();
        $datos = $this->leerJson();
        $id     = (int)($datos['id'] ?? 0);
        $estado = $datos['estado'] ?? '';

        if ($id <= 0) {
            $this->error('Falta el id de la incidencia.', 400);
        }
        if (!in_array($estado, ['abierta', 'en_curso', 'resuelta'], true)) {
            $this->error('Estado inválido.', 400);
        }
        // Se lee ANTES de cambiarla, para saber quién la reportó.
        $antes = $this->repo->buscarPorId($id);

        if (!$this->repo->cambiarEstado($id, $estado)) {
            $this->error('No existe una incidencia con ese id.', 404);
        }

        // Al resolverse, se le avisa a quien la reportó, si fue un usuario
        // registrado. Los reportes anónimos no tienen a quién avisarle.
        if ($estado === 'resuelta' && $antes !== null && $antes->getReportadoPor() !== null) {
            $this->notis->crear(
                $antes->getReportadoPor(),
                'incidencia_resuelta',
                'Resolvieron lo que reportaste',
                'Tu reporte de ' . mb_strtolower($antes->getTipoLegible()) . ' fue marcado como resuelto. Gracias por avisar.',
                'baja',
                $id
            );
        }

        $this->responder(['mensaje' => 'Estado actualizado.'], 200);
    }

    /** POST /incidencias/asignar-cuadrilla — asigna la cuadrilla responsable. */
    public function asignarCuadrilla(): void
    {
        $this->exigirAdmin();
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

        // Aviso a la cuadrilla. Solo al asignar: si se quita la cuadrilla
        // ($cuadrillaId null) no hay a quién avisarle.
        $avisados = 0;
        if ($cuadrillaId !== null) {
            $inc = $this->repo->buscarPorId($id);
            $donde = $inc !== null
                ? $this->nombrarObjetivo((int)$inc->getContenedorId(), (int)$inc->getInstalacionId())
                : 'el objetivo indicado';
            $avisados = $this->notis->avisarACuadrilla(
                $cuadrillaId,
                'incidencia_asignada',
                'Les asignaron una incidencia',
                ($inc !== null ? $inc->getTipoLegible() : 'Incidencia') . ' en ' . $donde . '.',
                $inc !== null ? $inc->getGravedad() : 'media',
                $id
            );
        }

        $this->responder([
            'mensaje'  => 'Cuadrilla responsable asignada.',
            'avisados' => $avisados,
        ], 200);
    }

    public function eliminar(): void
    {
        $this->exigirAdmin();
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

    /**
     * Cómo nombrar en un aviso aquello sobre lo que se reportó, sin que
     * quien arma el mensaje tenga que saber si es contenedor o instalación.
     */
    private function nombrarObjetivo(int $contenedorId, int $instalacionId): string
    {
        if ($instalacionId > 0) {
            $i = $this->repoInstalaciones->buscarPorId($instalacionId);
            return $i !== null
                ? $i->getNombre() . ' (' . $i->getDireccion() . ')'
                : 'instalación #' . $instalacionId;
        }
        $c = $this->repoContenedores->buscarPorId($contenedorId);
        return $c !== null
            ? $c->getCodigo() . ' (' . $c->getDireccion() . ')'
            : 'contenedor #' . $contenedorId;
    }
}
