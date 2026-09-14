<?php
declare(strict_types=1);

/**
 * api.php — PUNTO DE ENTRADA de la API de Usuarios (versión sin core).
 *
 * En esta versión no hay clases Router ni Respuesta. El ruteo se resuelve
 * acá mismo: leemos el método HTTP y la ruta, y con un switch decidimos qué
 * método del controlador ejecutar. El controlador arma la respuesta JSON.
 *
 * Levantar con:  php -S localhost:9000 api.php   (desde la carpeta api-usuarios/)
 * O con XAMPP/Apache usando el .htaccess incluido.
 */

// ---- Cargamos las clases que usamos ----
require __DIR__ . '/app/models/RepositorioSql.php';
require __DIR__ . '/app/models/Usuario.php';
require __DIR__ . '/app/models/Administrador.php';
require __DIR__ . '/app/models/Operario.php';
require __DIR__ . '/app/models/OperarioRecoleccion.php';
require __DIR__ . '/app/models/OperarioClasificacion.php';
require __DIR__ . '/app/models/OperarioVertedero.php';
require __DIR__ . '/app/models/Vecino.php';
require __DIR__ . '/app/models/RepositorioUsuarios.php';
require __DIR__ . '/app/models/Cuadrilla.php';
require __DIR__ . '/app/models/RepositorioCuadrillas.php';
require __DIR__ . '/app/controllers/ControladorBase.php';
require __DIR__ . '/app/controllers/UsuarioController.php';
require __DIR__ . '/app/controllers/CuadrillaController.php';

use App\Models\RepositorioUsuarios;
use App\Models\RepositorioCuadrillas;
use App\Controllers\UsuarioController;
use App\Controllers\CuadrillaController;

// ---- CORS: permite que el frontend (otro puerto) consuma esta API ----
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// El navegador manda OPTIONS antes de un POST (CORS). Respondemos vacío.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- Averiguamos método y ruta ----
$metodo = $_SERVER['REQUEST_METHOD'];

// Ruta pedida, sin query string (?a=1).
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Quitamos el prefijo de la carpeta (para que funcione en Apache y en php -S).
$base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($base !== '/' && $base !== '' && str_starts_with($ruta, $base)) {
    $ruta = substr($ruta, strlen($base));
}
$ruta = '/' . trim($ruta, '/');   // normalizamos: siempre empieza con "/"

// ---- Armamos los controladores ----
$repoUsuarios = new RepositorioUsuarios();
$controller = new UsuarioController($repoUsuarios);
$cuadrillaCtrl = new CuadrillaController(new RepositorioCuadrillas(), $repoUsuarios);

// ---- Ruteo: método + ruta -> acción ----
switch ($metodo . ' ' . $ruta) {
    case 'POST /login':
        $controller->login();
        break;

    case 'POST /usuarios':
        $controller->registrar();
        break;

    case 'GET /usuarios':
        $controller->listar();
        break;

    case 'POST /usuarios/eliminar':
        $controller->eliminar();
        break;

    case 'POST /usuarios/modificar':
        $controller->modificar();
        break;

    case 'GET /cuadrillas':
        $cuadrillaCtrl->listar();
        break;

    case 'POST /cuadrillas':
        $cuadrillaCtrl->crear();
        break;

    case 'POST /cuadrillas/eliminar':
        $cuadrillaCtrl->eliminar();
        break;

    case 'POST /cuadrillas/agregar-operario':
        $cuadrillaCtrl->agregarOperario();
        break;

    case 'POST /cuadrillas/quitar-operario':
        $cuadrillaCtrl->quitarOperario();
        break;

    case 'GET /operarios-libres':
        $cuadrillaCtrl->operariosLibres();
        break;

    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Ruta no encontrada: ' . $metodo . ' ' . $ruta]);
        break;
}
