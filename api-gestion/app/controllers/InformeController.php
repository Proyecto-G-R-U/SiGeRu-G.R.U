<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RepositorioInformes;

/**
 * InformeController: la sección de informes y reportes.
 *
 *   resumen()  -> GET /informes            (requerimientos 3.1.4.2 a 3.1.4.5)
 *   exportar() -> GET /informes/exportar   (requerimiento 3.1.4.6)
 *
 * Todo es solo para el administrador: son datos de gestión municipal, no
 * información que un vecino o un operario necesite para su tarea.
 */
class InformeController extends ControladorBase
{
    private RepositorioInformes $repo;

    /** Límites del factor de llenado: 0 no tiene sentido y arriba de 1,2 tampoco. */
    private const FACTOR_MIN = 0.1;
    private const FACTOR_MAX = 1.2;
    private const SEMANAS_MAX = 52;

    public function __construct(RepositorioInformes $repo)
    {
        $this->repo = $repo;
    }

    /**
     * GET /informes?factor=0.85&semanas=4
     *
     * Devuelve todo el tablero de una vez. Es una sola ida y vuelta en vez
     * de cinco: las consultas son livianas y la pantalla las muestra juntas.
     */
    public function resumen(): void
    {
        $this->exigirAdmin();
        [$factor, $semanas] = $this->leerParametros();

        $this->responder([
            'indicadores'   => $this->repo->indicadores($factor, $semanas),
            'zonasCriticas' => $this->repo->zonasCriticas(),
            'cuadrillas'    => $this->repo->tiemposPorCuadrilla(),
            'volumenZona'   => $this->repo->volumenPorZona($factor, $semanas),
            'volumenRuta'   => $this->repo->volumenPorRuta($factor, $semanas),
            'parametros'    => ['factorLlenado' => $factor, 'semanas' => $semanas],
        ], 200);
    }

    /**
     * GET /informes/exportar?tipo=zonas|volumen|cuadrillas
     *
     * Devuelve un CSV descargable (requerimiento 3.1.4.6: "formatos
     * compatibles para el análisis municipal"). CSV porque lo abre
     * cualquier planilla sin instalar nada.
     *
     * Dos detalles que parecen menores y no lo son:
     *   - separador punto y coma, que es lo que espera Excel en español;
     *   - BOM al principio, sin el cual Excel rompe los acentos.
     */
    public function exportar(): void
    {
        $this->exigirAdmin();
        [$factor, $semanas] = $this->leerParametros();
        $tipo = trim((string)($_GET['tipo'] ?? 'zonas'));

        switch ($tipo) {
            case 'zonas':
                $cabeceras = ['Zona', 'Contenedores', 'Instalaciones', 'Incidencias',
                              'Sin resolver', 'Graves', 'Incidencias por punto'];
                $filas = array_map(static fn(array $z): array => [
                    $z['zona'], $z['contenedores'], $z['instalaciones'], $z['total'],
                    $z['abiertas'], $z['graves'], $z['porPunto'],
                ], $this->repo->zonasCriticas());
                break;

            case 'volumen':
                $cabeceras = ['Zona', 'Contenedores', 'Litros por pasada', 'Pasadas',
                              'Litros del período', 'Metros cúbicos'];
                $filas = array_map(static fn(array $v): array => [
                    $v['zona'], $v['contenedores'], $v['litrosPorPasada'],
                    $v['pasadas'], $v['litros'], $v['metrosCubicos'],
                ], $this->repo->volumenPorZona($factor, $semanas));
                break;

            case 'cuadrillas':
                $cabeceras = ['Cuadrilla', 'Incidencias resueltas', 'Horas promedio',
                              'Mínimo (h)', 'Máximo (h)', 'De gravedad alta'];
                $filas = array_map(static fn(array $c): array => [
                    $c['cuadrilla'], $c['resueltas'], $c['horasPromedio'],
                    $c['horasMin'], $c['horasMax'], $c['graves'],
                ], $this->repo->tiemposPorCuadrilla());
                break;

            default:
                $this->error('Tipo de informe desconocido: ' . $tipo
                           . '. Valores válidos: zonas, volumen, cuadrillas.', 400);
                return;
        }

        $nombre = 'sigeru-' . $tipo . '-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');

        echo "\xEF\xBB\xBF";                       // BOM: para que Excel lea los acentos
        $salida = fopen('php://output', 'w');
        fputcsv($salida, $cabeceras, ';', '"', '');
        foreach ($filas as $f) {
            fputcsv($salida, $f, ';', '"', '');
        }
        fclose($salida);
        exit;
    }

    /**
     * Lee y acota el factor de llenado y la cantidad de semanas.
     * Son los dos parámetros del cálculo de volumen: si entraran valores
     * absurdos, el informe mostraría cifras absurdas con cara de dato real.
     *
     * @return array{0: float, 1: int}
     */
    private function leerParametros(): array
    {
        $factor = isset($_GET['factor']) && $_GET['factor'] !== ''
            ? (float)$_GET['factor'] : 0.85;
        if ($factor < self::FACTOR_MIN || $factor > self::FACTOR_MAX) {
            $this->error('El factor de llenado tiene que estar entre '
                       . self::FACTOR_MIN . ' y ' . self::FACTOR_MAX . '.', 400);
        }

        $semanas = isset($_GET['semanas']) && $_GET['semanas'] !== ''
            ? (int)$_GET['semanas'] : 4;
        if ($semanas < 1 || $semanas > self::SEMANAS_MAX) {
            $this->error('Las semanas tienen que estar entre 1 y ' . self::SEMANAS_MAX . '.', 400);
        }

        return [$factor, $semanas];
    }
}
