<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioInstalaciones;
use App\Models\CentroAcopio;
use App\Models\PlantaClasificacion;
use App\Models\Vertedero;

/**
 * InstalacionController: ABM de centros de acopio, plantas de clasificación
 * y vertederos (requerimiento 3.1.1.2.6).
 *   listar()    -> GET  /instalaciones
 *   crear()     -> POST /instalaciones
 *   eliminar()  -> POST /instalaciones/eliminar
 *   modificar() -> POST /instalaciones/modificar
 */
class InstalacionController extends ControladorBase
{
    private RepositorioInstalaciones $repo;

    public function __construct(RepositorioInstalaciones $repo)
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
        $nombre     = trim($datos['nombre'] ?? '');
        $direccion  = trim($datos['direccion'] ?? '');
        $tipo       = $datos['tipo'] ?? 'acopio';
        $capacidad  = (int)($datos['capacidadMaxima'] ?? 0);
        $ocupacion  = (int)($datos['ocupacionActual'] ?? 0);
        $tipoResiduo = $datos['tipoResiduo'] ?? 'mezclado';
        $estado     = $datos['estado'] ?? 'operativa';
        $lat = isset($datos['lat']) && $datos['lat'] !== '' && $datos['lat'] !== null ? (float)$datos['lat'] : null;
        $lng = isset($datos['lng']) && $datos['lng'] !== '' && $datos['lng'] !== null ? (float)$datos['lng'] : null;

        if ($nombre === '' || $direccion === '') {
            $this->error('Nombre y dirección son obligatorios.', 400);
        }
        if ($capacidad <= 0) {
            $this->error('La capacidad máxima debe ser mayor a cero.', 400);
        }
        if ($lat === null || $lng === null) {
            $this->error('Marcá la ubicación de la instalación en el mapa.', 400);
        }

        $instalacion = $this->crearInstalacion(
            $this->repo->proximoId(), $nombre, $direccion, $tipo, $capacidad, $tipoResiduo, $estado, $lat, $lng, $ocupacion
        );
        $this->repo->agregar($instalacion);

        $this->responder(['mensaje' => 'Instalación creada correctamente.', 'instalacion' => $instalacion], 201);
    }

    public function modificar(): void
    {
        $datos = $this->leerJson();
        $id         = (int)($datos['id'] ?? 0);
        $nombre     = trim($datos['nombre'] ?? '');
        $direccion  = trim($datos['direccion'] ?? '');
        $tipo       = $datos['tipo'] ?? 'acopio';
        $capacidad  = (int)($datos['capacidadMaxima'] ?? 0);
        $ocupacion  = (int)($datos['ocupacionActual'] ?? 0);
        $tipoResiduo = $datos['tipoResiduo'] ?? 'mezclado';
        $estado     = $datos['estado'] ?? 'operativa';
        $lat = isset($datos['lat']) && $datos['lat'] !== '' && $datos['lat'] !== null ? (float)$datos['lat'] : null;
        $lng = isset($datos['lng']) && $datos['lng'] !== '' && $datos['lng'] !== null ? (float)$datos['lng'] : null;

        if ($id <= 0) {
            $this->error('Falta el id de la instalación.', 400);
        }
        if ($nombre === '' || $direccion === '') {
            $this->error('Nombre y dirección son obligatorios.', 400);
        }
        if ($capacidad <= 0) {
            $this->error('La capacidad máxima debe ser mayor a cero.', 400);
        }
        if ($lat === null || $lng === null) {
            $this->error('Marcá la ubicación de la instalación en el mapa.', 400);
        }

        $instalacion = $this->crearInstalacion($id, $nombre, $direccion, $tipo, $capacidad, $tipoResiduo, $estado, $lat, $lng, $ocupacion);
        if (!$this->repo->actualizar($instalacion)) {
            $this->error('No existe una instalación con ese id.', 404);
        }

        $this->responder(['mensaje' => 'Instalación modificada correctamente.', 'instalacion' => $instalacion], 200);
    }

    public function eliminar(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Falta el id de la instalación.', 400);
        }
        if (!$this->repo->eliminar($id)) {
            $this->error('No existe una instalación con ese id.', 404);
        }
        $this->responder(['mensaje' => 'Instalación eliminada.'], 200);
    }

    /**
     * Crea la SUBCLASE que corresponda según el tipo elegido.
     * Centraliza acá la decisión para no repetirla en crear() y modificar().
     */
    private function crearInstalacion(
        int $id, string $nombre, string $direccion, string $tipo,
        int $capacidad, string $tipoResiduo, string $estado,
        ?float $lat = null, ?float $lng = null, int $ocupacion = 0
    ): \App\Models\Instalacion {
        return match ($tipo) {
            'clasificacion' => new PlantaClasificacion($id, $nombre, $direccion, $capacidad, $tipoResiduo, $estado, $lat, $lng, $ocupacion),
            'vertedero'     => new Vertedero($id, $nombre, $direccion, $capacidad, $tipoResiduo, $estado, $lat, $lng, $ocupacion),
            default         => new CentroAcopio($id, $nombre, $direccion, $capacidad, $tipoResiduo, $estado, $lat, $lng, $ocupacion),
        };
    }
}
