<?php
declare(strict_types=1);

/**
 * api.php — PUNTO DE ENTRADA de la API de Recolección (camiones, sin core).
 * Levantar con:  php -S localhost:9002 api.php   (desde la carpeta api-recoleccion/)
 */

require __DIR__ . '/app/models/Camion.php';
require __DIR__ . '/app/models/RepositorioCamiones.php';
require __DIR__ . '/app/controllers/CamionController.php';

use App\Models\RepositorioCamiones;
use App\Controllers\CamionController;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($base !== '/' && $base !== '' && str_starts_with($ruta, $base)) {
    $ruta = substr($ruta, strlen($base));
}
$ruta = '/' . trim($ruta, '/');

$controller = new CamionController(new RepositorioCamiones());

switch ($metodo . ' ' . $ruta) {
    case 'GET /camiones':
        $controller->listar();
        break;

    case 'POST /camiones':
        $controller->crear();
        break;

    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Ruta no encontrada: ' . $metodo . ' ' . $ruta]);
        break;
}
