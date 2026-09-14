<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioMaquinaria;
use App\Models\RepositorioInstalaciones;
use App\Models\Maquinaria;

/**
 * MaquinariaController: inventario de maquinaria, herramientas y stock de
 * repuestos por instalación (requerimiento 3.1.1.2.7).
 *   listar()    -> GET  /maquinaria
 *   crear()     -> POST /maquinaria
 *   eliminar()  -> POST /maquinaria/eliminar
 *   modificar() -> POST /maquinaria/modificar
 */
class MaquinariaController extends ControladorBase
{
    private RepositorioMaquinaria $repo;
    private RepositorioInstalaciones $repoInstalaciones;

    public function __construct(RepositorioMaquinaria $repo, RepositorioInstalaciones $repoInstalaciones)
    {
        $this->repo = $repo;
        $this->repoInstalaciones = $repoInstalaciones;
    }

    public function listar(): void
    {
        $this->responder($this->repo->todos(), 200);
    }

    public function crear(): void
    {
        $datos = $this->leerJson();
        $nombre    = trim($datos['nombre'] ?? '');
        $categoria = $datos['categoria'] ?? 'maquinaria';
        $estado    = $datos['estado'] ?? 'operativa';
        $cantidad  = (int)($datos['cantidad'] ?? 1);
        $instalacionId = isset($datos['instalacionId']) && $datos['instalacionId'] !== ''
            ? (int)$datos['instalacionId'] : null;

        if ($nombre === '') {
            $this->error('El nombre del ítem es obligatorio.', 400);
        }
        if ($cantidad <= 0) {
            $this->error('La cantidad debe ser mayor a cero.', 400);
        }
        if ($instalacionId === null) {
            $this->error('Elegí a qué instalación pertenece.', 400);
        }
        if ($this->repoInstalaciones->buscarPorId($instalacionId) === null) {
            $this->error('La instalación indicada no existe.', 404);
        }

        $item = new Maquinaria($this->repo->proximoId(), $nombre, $categoria, $estado, $cantidad, $instalacionId);
        $this->repo->agregar($item);

        $this->responder(['mensaje' => 'Ítem agregado al inventario.', 'maquinaria' => $item], 201);
    }

    public function modificar(): void
    {
        $datos = $this->leerJson();
        $id        = (int)($datos['id'] ?? 0);
        $nombre    = trim($datos['nombre'] ?? '');
        $categoria = $datos['categoria'] ?? 'maquinaria';
        $estado    = $datos['estado'] ?? 'operativa';
        $cantidad  = (int)($datos['cantidad'] ?? 1);
        $instalacionId = isset($datos['instalacionId']) && $datos['instalacionId'] !== ''
            ? (int)$datos['instalacionId'] : null;

        if ($id <= 0) {
            $this->error('Falta el id del ítem.', 400);
        }
        if ($nombre === '') {
            $this->error('El nombre del ítem es obligatorio.', 400);
        }
        if ($cantidad <= 0) {
            $this->error('La cantidad debe ser mayor a cero.', 400);
        }
        if ($instalacionId !== null && $this->repoInstalaciones->buscarPorId($instalacionId) === null) {
            $this->error('La instalación indicada no existe.', 404);
        }

        $item = new Maquinaria($id, $nombre, $categoria, $estado, $cantidad, $instalacionId);
        if (!$this->repo->actualizar($item)) {
            $this->error('No existe un ítem con ese id.', 404);
        }

        $this->responder(['mensaje' => 'Ítem modificado correctamente.', 'maquinaria' => $item], 200);
    }

    public function eliminar(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Falta el id del ítem.', 400);
        }
        if (!$this->repo->eliminar($id)) {
            $this->error('No existe un ítem con ese id.', 404);
        }
        $this->responder(['mensaje' => 'Ítem eliminado del inventario.'], 200);
    }
}
