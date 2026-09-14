<?php
declare(strict_types=1);

/**
 * api.php — PUNTO DE ENTRADA de la API de Recolección (camiones y flotas).
 * Levantar con:  php -S localhost:9002 api.php   (desde la carpeta api-recoleccion/)
 * O con XAMPP/Apache usando el .htaccess incluido.
 */

require __DIR__ . '/app/models/RepositorioSql.php';
require __DIR__ . '/app/models/Camion.php';
require __DIR__ . '/app/models/RepositorioCamiones.php';
require __DIR__ . '/app/models/Flota.php';
require __DIR__ . '/app/models/RepositorioFlotas.php';
require __DIR__ . '/app/models/Ruta.php';
require __DIR__ . '/app/models/RepositorioRutas.php';
require __DIR__ . '/app/controllers/ControladorBase.php';
require __DIR__ . '/app/controllers/CamionController.php';
require __DIR__ . '/app/controllers/FlotaController.php';
require __DIR__ . '/app/controllers/RutaController.php';

use App\Models\RepositorioCamiones;
use App\Models\RepositorioFlotas;
use App\Models\RepositorioRutas;
use App\Controllers\CamionController;
use App\Controllers\FlotaController;
use App\Controllers\RutaController;

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
$rutaCtrl   = new RutaController(new RepositorioRutas());

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

    case 'POST /camiones/modificar':
        $camionCtrl->modificar();
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

    // ---- Rutas de recolección ----
    case 'GET /rutas':
        $rutaCtrl->listar();
        break;

    case 'GET /rutas/cuadrilla':
        $rutaCtrl->porCuadrilla();
        break;

    case 'POST /rutas':
        $rutaCtrl->crear();
        break;

    case 'POST /rutas/modificar':
        $rutaCtrl->modificar();
        break;

    case 'POST /rutas/eliminar':
        $rutaCtrl->eliminar();
        break;

    case 'POST /rutas/agregar-contenedor':
        $rutaCtrl->agregarContenedor();
        break;

    case 'POST /rutas/quitar-contenedor':
        $rutaCtrl->quitarContenedor();
        break;

    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Ruta no encontrada: ' . $metodo . ' ' . $ruta]);
        break;
}
