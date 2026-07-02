<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioCamiones;
use App\Models\Camion;

/**
 * CamionController: lógica de la API de Recolección.
 * Versión sin core: arma el JSON con sus propios helpers.
 */
class CamionController
{
    private RepositorioCamiones $repo;

    public function __construct(RepositorioCamiones $repo)
    {
        $this->repo = $repo;
    }

    /** GET /camiones — listado completo de la flota. */
    public function listar(): void
    {
        $this->responder($this->repo->todos(), 200);
    }

    /** POST /camiones — alta de camión. */
    public function crear(): void
    {
        $datos = $this->leerJson();
        $patente        = trim($datos['patente'] ?? '');
        $modelo         = trim($datos['modelo'] ?? '');
        $estado         = $datos['estado'] ?? 'operativo';
        $disponibilidad = $datos['disponibilidad'] ?? 'disponible';

        if ($patente === '' || $modelo === '') {
            $this->error('Patente y modelo son obligatorios.', 400);
        }

        $camion = new Camion(
            $this->repo->proximoId(),
            $patente,
            $modelo,
            $estado,
            $disponibilidad
        );
        $this->repo->agregar($camion);

        $this->responder([
            'mensaje' => 'Camión creado correctamente.',
            'camion'  => $camion,
        ], 201);
    }

    // ---------------- Helpers internos ----------------

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
