<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * ControladorBase: clase base ABSTRACTA de todos los controladores.
 *
 * Concentra los tres helpers que todos los controladores repetían:
 * leer el JSON del pedido, responder JSON y responder un error.
 * Cada controlador hijo hereda estos métodos y solo define sus acciones
 * (login, listar, crear, etc.).
 */
abstract class ControladorBase
{
    /** Lee y decodifica el cuerpo JSON del pedido HTTP. */
    protected function leerJson(): array
    {
        $datos = json_decode(file_get_contents('php://input'), true);
        return is_array($datos) ? $datos : [];
    }

    /** Responde JSON con un código de estado HTTP y corta la ejecución. */
    protected function responder(mixed $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /** Atajo para responder un error con su código. */
    protected function error(string $mensaje, int $codigo = 400): void
    {
        $this->responder(['error' => $mensaje], $codigo);
    }
}
