<?php
declare(strict_types=1);

/**
 * api.php — PUNTO DE ENTRADA de la API de Recolección (camiones y flotas).
 * Levantar con:  php -S localhost:9002 api.php   (desde la carpeta api-recoleccion/)
 * O con XAMPP/Apache usando el .htaccess incluido.
 */

require __DIR__ . '/app/models/Camion.php';
require __DIR__ . '/app/models/RepositorioCamiones.php';
require __DIR__ . '/app/models/Flota.php';
require __DIR__ . '/app/models/RepositorioFlotas.php';
require __DIR__ . '/app/controllers/CamionController.php';
require __DIR__ . '/app/controllers/FlotaController.php';

use App\Models\RepositorioCamiones;
use App\Models\RepositorioFlotas;
use App\Controllers\CamionController;
use App\Controllers\FlotaController;

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

$camionCtrl = new CamionController(new RepositorioCamiones());
$flotaCtrl  = new FlotaController(new RepositorioFlotas());

switch ($metodo . ' ' . $ruta) {
    case 'GET /camiones':
        $camionCtrl->listar();
        break;

    case 'POST /camiones':
        $camionCtrl->crear();
        break;

    case 'POST /camiones/eliminar':
        $camionCtrl->eliminar();
        break;

    case 'POST /camiones/asignar-cuadrilla':
        $camionCtrl->asignarCuadrilla();
        break;

    case 'POST /camiones/asignar-flota':
        $camionCtrl->asignarFlota();
        break;

    case 'GET /flotas':
        $flotaCtrl->listar();
        break;

    case 'POST /flotas':
        $flotaCtrl->crear();
        break;

    case 'POST /flotas/eliminar':
        $flotaCtrl->eliminar();
        break;

    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Ruta no encontrada: ' . $metodo . ' ' . $ruta]);
        break;
}
