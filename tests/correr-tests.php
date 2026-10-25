<?php
declare(strict_types=1);

/**
 * correr-tests.php — Pruebas unitarias de SiGeRU
 *
 * Prueban la LÓGICA de las clases del backend sin tocar la base de datos:
 * la jerarquía de herencia, los roles, el hash de contraseñas, la
 * serialización a JSON y las reglas de negocio de cada modelo.
 *
 * Ejecutar:  php tests/correr-tests.php
 *       o:   http://localhost/sigeru/tests/correr-tests.php
 */

// En el navegador conviene texto plano para que se lea el reporte.
if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

require __DIR__ . '/Test.php';

$base = dirname(__DIR__);

// ---- Clases de usuarios (API Usuarios) ----
require $base . '/api-usuarios/app/models/Usuario.php';
require $base . '/api-usuarios/app/models/Administrador.php';
require $base . '/api-usuarios/app/models/Operario.php';
require $base . '/api-usuarios/app/models/OperarioRecoleccion.php';
require $base . '/api-usuarios/app/models/OperarioClasificacion.php';
require $base . '/api-usuarios/app/models/OperarioVertedero.php';
require $base . '/api-usuarios/app/models/Vecino.php';
require $base . '/api-usuarios/app/models/Cuadrilla.php';

// ---- Clases de gestión ----
require $base . '/api-gestion/app/models/Contenedor.php';
require $base . '/api-gestion/app/models/Instalacion.php';
require $base . '/api-gestion/app/models/CentroAcopio.php';
require $base . '/api-gestion/app/models/PlantaClasificacion.php';
require $base . '/api-gestion/app/models/Vertedero.php';
require $base . '/api-gestion/app/models/Maquinaria.php';
require $base . '/api-gestion/app/models/Incidencia.php';

// ---- Clases de recolección ----
require $base . '/api-recoleccion/app/models/Camion.php';
require $base . '/api-recoleccion/app/models/Flota.php';

use App\Models\Administrador;
use App\Models\OperarioRecoleccion;
use App\Models\OperarioClasificacion;
use App\Models\OperarioVertedero;
use App\Models\Vecino;
use App\Models\Cuadrilla;
use App\Models\Contenedor;
use App\Models\CentroAcopio;
use App\Models\PlantaClasificacion;
use App\Models\Vertedero;
use App\Models\Maquinaria;
use App\Models\Incidencia;
use App\Models\Camion;
use App\Models\Flota;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           SiGeRU — Pruebas unitarias del backend             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

$hash = password_hash('1234', PASSWORD_DEFAULT);

// ═══════════════════════════════════════════════════════════════
Test::grupo('Herencia de usuarios: cada subclase declara su rol');

$admin  = new Administrador(1, 'Carlos', 'admin@sigeru.uy', $hash);
$vecino = new Vecino(5, 'Vecino', 'vecino@gmail.com', $hash);
$opRec  = new OperarioRecoleccion(2, 'Marta', 'rec@sigeru.uy', $hash);
$opCla  = new OperarioClasificacion(3, 'Julián', 'cla@sigeru.uy', $hash);
$opVer  = new OperarioVertedero(4, 'Ana', 'ver@sigeru.uy', $hash);

Test::igual('Administrador tiene rol "administrador"', 'administrador', $admin->getRol());
Test::igual('Vecino tiene rol "vecino"',               'vecino',        $vecino->getRol());
Test::igual('Operario de recolección tiene rol "operario"', 'operario',  $opRec->getRol());
Test::igual('Especialidad de recolección',    'recoleccion',   $opRec->getEspecialidad());
Test::igual('Especialidad de clasificación', 'clasificacion', $opCla->getEspecialidad());
Test::igual('Especialidad de vertedero',     'vertedero',     $opVer->getEspecialidad());

Test::comprobar('OperarioRecoleccion ES UN Operario', $opRec instanceof \App\Models\Operario);
Test::comprobar('OperarioRecoleccion ES UN Usuario',  $opRec instanceof \App\Models\Usuario);
Test::comprobar('Administrador ES UN Usuario',        $admin instanceof \App\Models\Usuario);

// ═══════════════════════════════════════════════════════════════
Test::grupo('Clases abstractas: no se pueden instanciar');

Test::lanzaError('No se puede hacer new Usuario()', function () use ($hash) {
    $clase = 'App\Models\Usuario';
    new $clase(9, 'X', 'x@x.uy', $hash);
});
Test::lanzaError('No se puede hacer new Operario()', function () use ($hash) {
    $clase = 'App\Models\Operario';
    new $clase(9, 'X', 'x@x.uy', $hash);
});
Test::lanzaError('No se puede hacer new Instalacion()', function () {
    $clase = 'App\Models\Instalacion';
    new $clase(9, 'X', 'Dir', 100);
});

// ═══════════════════════════════════════════════════════════════
Test::grupo('Seguridad de contraseñas');

Test::comprobar('La contraseña correcta valida',  $admin->verificarPassword('1234'));
Test::comprobar('Una contraseña incorrecta NO valida', !$admin->verificarPassword('incorrecta'));
Test::comprobar('El hash NO es la contraseña en texto plano', $admin->getPasswordHash() !== '1234');
Test::comprobar('Dos hashes de la misma clave son distintos (salt)',
    password_hash('1234', PASSWORD_DEFAULT) !== password_hash('1234', PASSWORD_DEFAULT));

// ═══════════════════════════════════════════════════════════════
Test::grupo('El hash nunca viaja al frontend');

$json = $admin->jsonSerialize();
Test::comprobar('jsonSerialize() NO incluye passwordHash', !isset($json['passwordHash']));
Test::comprobar('jsonSerialize() NO incluye password_hash', !isset($json['password_hash']));
Test::comprobar('jsonSerialize() sí incluye el rol', isset($json['rol']));
Test::comprobar('jsonSerialize() sí incluye el nombre', isset($json['nombre']));

$jsonOp = $opCla->jsonSerialize();
Test::igual('El JSON del operario lleva su especialidad', 'clasificacion', $jsonOp['especialidad']);

// ═══════════════════════════════════════════════════════════════
Test::grupo('Contenedores: integridad y llenado son independientes');

$c1 = new Contenedor(1, 'CNT-102', 'Av. Italia', 'reciclable', 'operativo', -34.90, -56.15, 'vacio',      'Municipio CH');
$c2 = new Contenedor(2, 'CNT-403', 'Malvín',     'mezclado',   'operativo', -34.89, -56.11, 'desbordado', 'Municipio E');
$c3 = new Contenedor(3, 'CNT-210', 'Pocitos',    'reciclable', 'roto',      -34.90, -56.15, 'lleno',      'Municipio CH');

Test::igual('Contenedor sano y vacío: estado',   'operativo',  $c1->getEstado());
Test::igual('Contenedor sano y vacío: llenado',  'vacio',      $c1->getNivelLlenado());
Test::igual('Puede estar operativo y desbordado', 'desbordado', $c2->getNivelLlenado());
Test::igual('…y su integridad sigue siendo operativo', 'operativo', $c2->getEstado());
Test::igual('Puede estar roto pero no desbordado', 'lleno',     $c3->getNivelLlenado());
Test::igual('…y su integridad es roto',            'roto',      $c3->getEstado());

$jc = $c2->jsonSerialize();
Test::comprobar('El JSON del contenedor lleva las coordenadas',
    isset($jc['lat']) && isset($jc['lng']));
Test::comprobar('El JSON del contenedor lleva el nivel de llenado', isset($jc['nivelLlenado']));

// ───────────────────────────────────────────────────────────────
// Zona del contenedor (req. 3.1.1.3.4, 3.1.4.1 y 3.1.4.3).
// Sin un valor controlado, el GROUP BY de los informes se llenaría de
// zonas escritas distinto, así que lo que se prueba es la validación.
Test::grupo('Contenedores: la zona agrupa para los informes');

Test::igual('La zona se guarda en el contenedor', 'Municipio CH', $c1->getZona());
Test::comprobar('El JSON del contenedor lleva la zona', array_key_exists('zona', $jc));
Test::igual('…y es la que se le cargó', 'Municipio E', $jc['zona']);

Test::comprobar('Un municipio de la lista es válido',
    Contenedor::zonaValida('Municipio A'));
Test::comprobar('Sin zona asignada (null) también es válido',
    Contenedor::zonaValida(null));
Test::comprobar('Un municipio inventado NO es válido',
    !Contenedor::zonaValida('Municipio Z'));
Test::comprobar('La validación distingue mayúsculas: "municipio a" no entra',
    !Contenedor::zonaValida('municipio a'));
Test::comprobar('Texto libre no entra como zona',
    !Contenedor::zonaValida('por el centro'));
Test::igual('Montevideo tiene 8 municipios', 8, count(Contenedor::ZONAS));

// Un contenedor sin zona es un caso normal: recién cargado, todavía
// no se le asignó municipio.
$sinZona = new Contenedor(9, 'CNT-999', 'Sin asignar', 'mezclado', 'operativo', -34.90, -56.15);
Test::igual('Un contenedor nuevo arranca sin zona', null, $sinZona->getZona());
$sinZona->setZona('Municipio F');
Test::igual('…y se le puede asignar después', 'Municipio F', $sinZona->getZona());

// ═══════════════════════════════════════════════════════════════
Test::grupo('Instalaciones: la jerarquía define el tipo');

$acopio = new CentroAcopio(1, 'Acopio Centro', 'Rondeau 1580', 150, 'reciclable', 'operativa', -34.88, -56.18);
$planta = new PlantaClasificacion(2, 'Planta Este', 'Maldonado 5400', 300, 'ambos', 'operativa', -34.84, -56.10);
$verted = new Vertedero(3, 'Felipe Cardoso', 'Cno. F. Cardoso', 900, 'mezclado', 'operativa', -34.82, -56.09);

Test::igual('CentroAcopio tipo "acopio"',               'acopio',        $acopio->getTipo());
Test::igual('PlantaClasificacion tipo "clasificacion"', 'clasificacion', $planta->getTipo());
Test::igual('Vertedero tipo "vertedero"',               'vertedero',     $verted->getTipo());
Test::igual('Nombre legible del vertedero', 'Vertedero', $verted->getTipoLegible());
Test::comprobar('Las tres heredan de Instalacion',
    $acopio instanceof \App\Models\Instalacion
    && $planta instanceof \App\Models\Instalacion
    && $verted instanceof \App\Models\Instalacion);
Test::igual('La capacidad máxima se guarda bien', 900, $verted->getCapacidadMaxima());

// ═══════════════════════════════════════════════════════════════
Test::grupo('Camiones: flota y cuadrilla pueden estar vacías');

$camion = new Camion(1, 'STP 1234', 'Volvo FE', 'operativo', 'en_ruta', 1, 2);
$libre  = new Camion(2, 'STP 5678', 'Mercedes', 'operativo', 'disponible', null, null);

Test::igual('El camión conoce su flota',     1, $camion->getFlotaId());
Test::igual('El camión conoce su cuadrilla', 2, $camion->getCuadrillaId());
Test::igual('Un camión sin flota devuelve null',     null, $libre->getFlotaId());
Test::igual('Un camión sin cuadrilla devuelve null', null, $libre->getCuadrillaId());

$camion->setCuadrillaId(null);
Test::igual('Se le puede quitar la cuadrilla', null, $camion->getCuadrillaId());

// ═══════════════════════════════════════════════════════════════
Test::grupo('Cuadrillas: agrupan operarios');

$cuadrilla = new Cuadrilla(1, 'Cuadrilla Norte', 'Zona Norte', [2, 3]);
Test::igual('La cuadrilla conoce su nombre', 'Cuadrilla Norte', $cuadrilla->getNombre());
Test::igual('La cuadrilla lista sus integrantes', [2, 3], $cuadrilla->getOperarios());
$cuadrilla->setOperarios([2]);
Test::igual('Se puede quitar un integrante', [2], $cuadrilla->getOperarios());

// ═══════════════════════════════════════════════════════════════
Test::grupo('Incidencias: nacen abiertas y sin cuadrilla');

$inc = new Incidencia(1, 2, 'desborde', 'Desbordado hace dos días', 'alta',
                      'abierta', null, 5, 'Vecino de Prueba', '2026-09-10 10:00:00');

Test::igual('Nace en estado "abierta"',      'abierta',    $inc->getEstado());
Test::igual('Nace sin cuadrilla asignada',   null,         $inc->getCuadrillaId());
Test::igual('Guarda la gravedad',            'alta',       $inc->getGravedad());
Test::igual('Guarda a qué contenedor refiere', 2,          $inc->getContenedorId());
Test::igual('Guarda quién la reportó', 'Vecino de Prueba', $inc->getReportadoPorNombre());
Test::igual('Nombre legible del tipo',       'Desborde',   $inc->getTipoLegible());
Test::igual('Todavía no tiene fecha de resolución', null,  $inc->getFechaResolucion());

$inc->setCuadrillaId(1);
$inc->setEstado('en_curso');
Test::igual('El admin le asigna cuadrilla', 1,          $inc->getCuadrillaId());
Test::igual('El admin la pasa a en curso',  'en_curso', $inc->getEstado());
$inc->setEstado('resuelta');
Test::igual('Y finalmente la resuelve',     'resuelta', $inc->getEstado());

$otra = new Incidencia(2, 1, 'falta_recoleccion', 'No pasaron', 'media');
Test::igual('Tipo legible de falta de recolección', 'Falta de recolección', $otra->getTipoLegible());
Test::igual('Gravedad por defecto si no se aclara', 'media', $otra->getGravedad());

// ═══════════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════
// Historial por zona o por contenedor (requerimiento 3.1.1.3.4).
// La zona NO se guarda en la incidencia: la trae el repositorio con un
// JOIN contra contenedor, para que no se pueda desincronizar. Acá se
// prueba que el modelo la transporte bien hasta el JSON.
Test::grupo('Incidencias: zona y contenedor para el historial');

$conZona = new Incidencia(9, 3, 'rotura', 'Tapa rota', 'alta',
                          'resuelta', 1, 2, 'Ana', '2026-10-01 08:00:00', '2026-10-01 12:00:00');

Test::igual('Sin el JOIN, la incidencia no sabe su zona', null, $conZona->getZona());
Test::igual('Ni el código del contenedor',                null, $conZona->getContenedorCodigo());

$conZona->setDatosContenedor('Municipio CH', 'CNT-210');
Test::igual('El repositorio le pasa la zona',    'Municipio CH', $conZona->getZona());
Test::igual('…y el código del contenedor',       'CNT-210',      $conZona->getContenedorCodigo());

$jz = $conZona->jsonSerialize();
Test::igual('La zona viaja en el JSON al frontend',  'Municipio CH', $jz['zona']);
Test::igual('El código del contenedor también',      'CNT-210',      $jz['contenedorCodigo']);
Test::igual('Y sigue estando el id del contenedor',  3,              $jz['contenedorId']);

// Un contenedor sin municipio asignado es un caso válido: el historial
// lo tiene que poder agrupar igual, bajo "Sin zona asignada".
$sinZona = new Incidencia(10, 4, 'otro', 'Olor', 'baja');
$sinZona->setDatosContenedor(null, 'CNT-X01');
Test::igual('Un contenedor sin municipio deja la zona en null', null, $sinZona->getZona());
Test::igual('…pero igual se sabe de qué contenedor es', 'CNT-X01', $sinZona->getContenedorCodigo());

Test::grupo('Maquinaria: pertenece a una instalación');

$maq = new Maquinaria(1, 'Cinta transportadora', 'maquinaria', 'operativa', 1, 2);
$rep = new Maquinaria(4, 'Contenedores de repuesto', 'repuesto', 'operativa', 12, 1);

Test::igual('Sabe a qué instalación pertenece', 2, $maq->getInstalacionId());
Test::igual('Guarda la categoría',   'repuesto', $rep->getCategoria());
Test::igual('Guarda la cantidad',    12,         $rep->getCantidad());
$maq->setEstado('rota');
Test::igual('Se puede marcar como rota', 'rota', $maq->getEstado());

// ═══════════════════════════════════════════════════════════════
Test::grupo('Flotas');

$flota = new Flota(1, 'Flota Norte', 'Zona Norte');
Test::igual('La flota guarda su nombre', 'Flota Norte', $flota->getNombre());
Test::igual('La flota guarda su zona',   'Zona Norte',  $flota->getZona());

// ═══════════════════════════════════════════════════════════════
exit(Test::resumen());
