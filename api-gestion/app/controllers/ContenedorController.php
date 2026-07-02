<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioContenedores;
use App\Models\Contenedor;

/**
 * ContenedorController: lógica de la API de Gestión.
 * Versión sin core: arma el JSON con sus propios helpers.
 */
class ContenedorController
{
    private RepositorioContenedores $repo;

    public function __construct(RepositorioContenedores $repo)
    {
        $this->repo = $repo;
    }

    /** GET /contenedores — listado completo. */
    public function listar(): void
    {
        $this->responder($this->repo->todos(), 200);
    }

    /** POST /contenedores — alta de contenedor. */
    public function crear(): void
    {
        $datos = $this->leerJson();
        $codigo      = trim($datos['codigo'] ?? '');
        $ubicacion   = trim($datos['ubicacion'] ?? '');
        $tipoResiduo = $datos['tipoResiduo'] ?? 'mezclado';
        $estado      = $datos['estado'] ?? 'operativo';

        if ($codigo === '' || $ubicacion === '') {
            $this->error('Código y ubicación son obligatorios.', 400);
        }

        $contenedor = new Contenedor(
            $this->repo->proximoId(),
            $codigo,
            $ubicacion,
            $tipoResiduo,
            $estado
        );
        $this->repo->agregar($contenedor);

        $this->responder([
            'mensaje'    => 'Contenedor creado correctamente.',
            'contenedor' => $contenedor,
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
