<?php
declare(strict_types=1);

/**
 * api.php — PUNTO DE ENTRADA de la API de Gestión (contenedores, sin core).
 * Levantar con:  php -S localhost:9001 api.php   (desde la carpeta api-gestion/)
 */

require __DIR__ . '/app/models/RepositorioSql.php';
require __DIR__ . '/app/models/Contenedor.php';
require __DIR__ . '/app/models/RepositorioContenedores.php';
<<<<<<< HEAD
require __DIR__ . '/app/models/Instalacion.php';
require __DIR__ . '/app/models/CentroAcopio.php';
require __DIR__ . '/app/models/PlantaClasificacion.php';
require __DIR__ . '/app/models/Vertedero.php';
require __DIR__ . '/app/models/RepositorioInstalaciones.php';
require __DIR__ . '/app/models/Maquinaria.php';
require __DIR__ . '/app/models/RepositorioMaquinaria.php';
require __DIR__ . '/app/models/Incidencia.php';
require __DIR__ . '/app/models/RepositorioIncidencias.php';
require __DIR__ . '/app/controllers/ControladorBase.php';
require __DIR__ . '/app/controllers/ContenedorController.php';
require __DIR__ . '/app/controllers/InstalacionController.php';
require __DIR__ . '/app/controllers/MaquinariaController.php';
require __DIR__ . '/app/controllers/IncidenciaController.php';

use App\Models\RepositorioContenedores;
use App\Models\RepositorioInstalaciones;
use App\Models\RepositorioMaquinaria;
use App\Models\RepositorioIncidencias;
use App\Controllers\ContenedorController;
use App\Controllers\InstalacionController;
use App\Controllers\MaquinariaController;
use App\Controllers\IncidenciaController;
=======
require __DIR__ . '/app/controllers/ControladorBase.php';
require __DIR__ . '/app/controllers/ContenedorController.php';

use App\Models\RepositorioContenedores;
use App\Controllers\ContenedorController;
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c

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

<<<<<<< HEAD
$repoContenedores = new RepositorioContenedores();
$controller = new ContenedorController($repoContenedores);
$repoInstalaciones = new RepositorioInstalaciones();
$instalacionCtrl = new InstalacionController($repoInstalaciones);
$maquinariaCtrl  = new MaquinariaController(new RepositorioMaquinaria(), $repoInstalaciones);
$incidenciaCtrl  = new IncidenciaController(new RepositorioIncidencias(), $repoContenedores);
=======
$controller = new ContenedorController(new RepositorioContenedores());
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c

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

    case 'POST /contenedores/modificar':
        $controller->modificar();
        break;

<<<<<<< HEAD
    // ---- Instalaciones (centros de acopio, plantas, vertederos) ----
    case 'GET /instalaciones':
        $instalacionCtrl->listar();
        break;

    case 'POST /instalaciones':
        $instalacionCtrl->crear();
        break;

    case 'POST /instalaciones/eliminar':
        $instalacionCtrl->eliminar();
        break;

    case 'POST /instalaciones/modificar':
        $instalacionCtrl->modificar();
        break;

    // ---- Maquinaria / inventario ----
    case 'GET /maquinaria':
        $maquinariaCtrl->listar();
        break;

    case 'POST /maquinaria':
        $maquinariaCtrl->crear();
        break;

    case 'POST /maquinaria/eliminar':
        $maquinariaCtrl->eliminar();
        break;

    case 'POST /maquinaria/modificar':
        $maquinariaCtrl->modificar();
        break;

    // ---- Incidencias ----
    case 'GET /incidencias':
        $incidenciaCtrl->listar();
        break;

    case 'GET /incidencias/historial':
        $incidenciaCtrl->historial();
        break;

    case 'POST /incidencias':
        $incidenciaCtrl->crear();
        break;

    case 'POST /incidencias/estado':
        $incidenciaCtrl->cambiarEstado();
        break;

    case 'POST /incidencias/asignar-cuadrilla':
        $incidenciaCtrl->asignarCuadrilla();
        break;

    case 'POST /incidencias/eliminar':
        $incidenciaCtrl->eliminar();
        break;

=======
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Ruta no encontrada: ' . $metodo . ' ' . $ruta]);
        break;
}
