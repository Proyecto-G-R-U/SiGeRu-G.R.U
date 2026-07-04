<?php
declare(strict_types=1);

/**
 * api.php — PUNTO DE ENTRADA de la API de Gestión (contenedores, sin core).
 * Levantar con:  php -S localhost:9001 api.php   (desde la carpeta api-gestion/)
 */

require __DIR__ . '/app/models/Contenedor.php';
require __DIR__ . '/app/models/RepositorioContenedores.php';
require __DIR__ . '/app/controllers/ContenedorController.php';

use App\Models\RepositorioContenedores;
use App\Controllers\ContenedorController;

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

$controller = new ContenedorController(new RepositorioContenedores());

switch ($metodo . ' ' . $ruta) {
    case 'GET /contenedores':
        $controller->listar();
        break;

    case 'POST /contenedores':
        $controller->crear();
        break;

    case 'POST /contenedores/eliminar':
        $controller->eliminar();
        break;

    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Ruta no encontrada: ' . $metodo . ' ' . $ruta]);
        break;
}
