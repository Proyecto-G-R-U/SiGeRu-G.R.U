<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioCuadrillas;
use App\Models\Cuadrilla;

/**
 * CuadrillaController: gestión de cuadrillas (solo admin desde el frontend).
 *   listar()   -> GET  /cuadrillas
 *   crear()    -> POST /cuadrillas
 *   eliminar() -> POST /cuadrillas/eliminar
 */
class CuadrillaController
{
    private RepositorioCuadrillas $repo;

    public function __construct(RepositorioCuadrillas $repo)
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

    private function leerJson(): array
    {
        $cuerpo = file_get_contents('php://input');
        $datos = json_decode($cuerpo, true);
        return is_array($datos) ? $datos : [];
    }

    private function responder(mixed $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function error(string $mensaje, int $codigo = 400): void
    {
        $this->responder(['error' => $mensaje], $codigo);
    }
}
