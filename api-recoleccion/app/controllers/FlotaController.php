<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioFlotas;
use App\Models\Flota;

/**
 * FlotaController: gestión de flotas.
 *   listar()   -> GET  /flotas
 *   crear()    -> POST /flotas
 *   eliminar() -> POST /flotas/eliminar
 */
class FlotaController
{
    private RepositorioFlotas $repo;

    public function __construct(RepositorioFlotas $repo)
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

        if ($nombre === '') {
            $this->error('El nombre de la flota es obligatorio.', 400);
        }

        $flota = new Flota($this->repo->proximoId(), $nombre, $zona !== '' ? $zona : null);
        $this->repo->agregar($flota);

        $this->responder(['mensaje' => 'Flota creada correctamente.', 'flota' => $flota], 201);
    }

    public function eliminar(): void
    {
        $datos = $this->leerJson();
        $id = (int)($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Falta el id de la flota.', 400);
        }
        if (!$this->repo->eliminar($id)) {
            $this->error('No existe una flota con ese id.', 404);
        }
        $this->responder(['mensaje' => 'Flota eliminada.'], 200);
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
