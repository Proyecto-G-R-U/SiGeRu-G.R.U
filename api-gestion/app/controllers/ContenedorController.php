<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioContenedores;
use App\Models\Contenedor;

/**
 * ContenedorController: lógica de la API de Gestión.
 *   listar()   -> GET  /contenedores
 *   crear()    -> POST /contenedores
 *   eliminar() -> POST /contenedores/eliminar
 */
class ContenedorController extends ControladorBase
{
    private RepositorioContenedores $repo;

    public function __construct(RepositorioContenedores $repo)
    {
        $this->repo = $repo;
    }

    public function listar(): void
    {
        $this->exigirLogin();
        $this->responder($this->repo->todos(), 200);
    }

    public function crear(): void
    {
        $this->exigirAdmin();
        $datos = $this->leerJson();
        $codigo      = trim($datos['codigo'] ?? '');
        $direccion   = trim($datos['direccion'] ?? '');
        $tipoResiduo = $datos['tipoResiduo'] ?? 'mezclado';
        $estado      = $datos['estado'] ?? 'operativo';
        $nivelLlenado = $datos['nivelLlenado'] ?? 'vacio';
        $zona = $this->leerZona($datos);
        $lat = isset($datos['lat']) && $datos['lat'] !== '' && $datos['lat'] !== null ? (float)$datos['lat'] : null;
        $lng = isset($datos['lng']) && $datos['lng'] !== '' && $datos['lng'] !== null ? (float)$datos['lng'] : null;

        if ($codigo === '' || $direccion === '') {
            $this->error('Código y dirección son obligatorios.', 400);
        }
        if ($lat === null || $lng === null) {
            $this->error('Marcá la ubicación del contenedor en el mapa.', 400);
        }

        $contenedor = new Contenedor(
            $this->repo->proximoId(),
            $codigo,
            $direccion,
            $tipoResiduo,
            $estado,
            $lat,
            $lng,
            $nivelLlenado,
            $zona
        );
        $this->repo->agregar($contenedor);

        $this->responder(['mensaje' => 'Contenedor creado correctamente.', 'contenedor' => $contenedor], 201);
    }

    public function eliminar(): void
    {
        $this->exigirAdmin();
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Falta el id del contenedor.', 400);
        }
        if (!$this->repo->eliminar($id)) {
            $this->error('No existe un contenedor con ese id.', 404);
        }
        $this->responder(['mensaje' => 'Contenedor eliminado.'], 200);
    }

    /**
     * POST /contenedores/modificar — edita un contenedor.
     * Recibe { id, codigo, direccion, zona, tipoResiduo, estado, lat, lng }.
     */
    public function modificar(): void
    {
        $this->exigirAdmin();
        $datos = $this->leerJson();
        $id          = (int)($datos['id'] ?? 0);
        $codigo      = trim($datos['codigo'] ?? '');
        $direccion   = trim($datos['direccion'] ?? '');
        $tipoResiduo = $datos['tipoResiduo'] ?? 'mezclado';
        $estado      = $datos['estado'] ?? 'operativo';
        $nivelLlenado = $datos['nivelLlenado'] ?? 'vacio';
        $zona = $this->leerZona($datos);
        $lat = isset($datos['lat']) && $datos['lat'] !== '' && $datos['lat'] !== null ? (float)$datos['lat'] : null;
        $lng = isset($datos['lng']) && $datos['lng'] !== '' && $datos['lng'] !== null ? (float)$datos['lng'] : null;

        if ($id <= 0) {
            $this->error('Falta el id del contenedor.', 400);
        }
        if ($codigo === '' || $direccion === '') {
            $this->error('Código y dirección son obligatorios.', 400);
        }
        if ($lat === null || $lng === null) {
            $this->error('Marcá la ubicación del contenedor en el mapa.', 400);
        }

        $contenedor = new Contenedor($id, $codigo, $direccion, $tipoResiduo, $estado, $lat, $lng, $nivelLlenado, $zona);
        if (!$this->repo->actualizar($contenedor)) {
            $this->error('No existe un contenedor con ese id.', 404);
        }

        $this->responder(['mensaje' => 'Contenedor modificado correctamente.', 'contenedor' => $contenedor], 200);
    }

    /**
     * GET /contenedores/zonas — municipios disponibles.
     *
     * Devuelve { todas: [...], enUso: [...] }:
     *   - todas: la lista completa, para el <select> del alta.
     *   - enUso: sólo las que ya tienen contenedores, para los filtros
     *     de los informes (3.1.4.1), así no se ofrecen municipios vacíos.
     */
    public function zonas(): void
    {
        $this->exigirLogin();
        $this->responder([
            'todas' => Contenedor::ZONAS,
            'enUso' => $this->repo->zonas(),
        ], 200);
    }

    /**
     * Saca la zona del cuerpo del pedido y la valida.
     * Vacío o ausente significa "sin zona asignada" (null), que es válido:
     * un contenedor recién cargado puede no tener municipio todavía.
     * Un municipio inventado sí es un error: si entrara, rompería el
     * GROUP BY de los informes con zonas fantasma.
     */
    private function leerZona(array $datos): ?string
    {
        $zona = trim((string)($datos['zona'] ?? ''));
        if ($zona === '') {
            return null;
        }
        if (!Contenedor::zonaValida($zona)) {
            $this->error('La zona "' . $zona . '" no es un municipio válido.', 400);
        }
        return $zona;
    }
}
