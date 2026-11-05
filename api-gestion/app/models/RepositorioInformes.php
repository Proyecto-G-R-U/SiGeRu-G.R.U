<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioInformes: las consultas agregadas de la sección Informes.
 *
 * Cubre los requerimientos 3.1.4.2 a 3.1.4.6. No tiene tabla propia: lee
 * de las que ya existen y devuelve totales, así que no hereda el CRUD de
 * RepositorioSql sino solo su motor de consultas.
 *
 * Consulta tablas de los tres microservicios (contenedor e incidencia son
 * de Gestión, ruta y ruta_contenedor de Recolección, cuadrilla de Usuarios).
 * Es el mismo criterio que ya seguía RepositorioCuadrillas: las APIs no se
 * llaman entre ellas, leen la base compartida.
 */
class RepositorioInformes extends RepositorioSql
{
    /** No se usa: este repositorio no mapea una tabla, devuelve agregados. */
    protected function tabla(): string
    {
        return 'incidencia';
    }

    protected function aObjeto(array $fila): object
    {
        return (object)$fila;
    }

    // =================================================================
    //  3.1.4.2 — Volumen de residuos recolectados
    // =================================================================
    //
    // El requerimiento pide "volumen", no peso, así que se trabaja en
    // litros y se evita la densidad de la basura, que es el número difícil
    // de justificar (varía entre 100 y 150 kg/m³ según la compactación).
    //
    // La cuenta es:
    //
    //     litros por pasada  = suma de la capacidad de los contenedores
    //                          asignados a la ruta
    //     volumen del período = litros por pasada x pasadas x factor de llenado
    //
    // Cada ruta corre un día de la semana, así que en N semanas hace N
    // pasadas. El ÚNICO valor simulado es el factor de llenado: todo lo
    // demás sale de datos cargados en el sistema. Por eso el factor es un
    // parámetro y se muestra en pantalla, en vez de estar escondido acá.
    //
    // Si un contenedor está en dos rutas se cuenta dos veces, y está bien:
    // se recolecta dos veces.

    /**
     * Volumen estimado por zona.
     *
     * @param float $factorLlenado cuán llenos se asumen los contenedores (0 a 1.2)
     * @param int   $semanas       cuántas semanas abarca el período
     * @return array<int, array{zona:string, contenedores:int, litrosPorPasada:int,
     *                          pasadas:int, litros:int, metrosCubicos:float}>
     */
    public function volumenPorZona(float $factorLlenado = 0.85, int $semanas = 4): array
    {
        $filas = $this->filas(
            "SELECT COALESCE(NULLIF(c.zona, ''), 'Sin zona asignada') AS zona,
                    COUNT(*)                     AS contenedores,
                    SUM(c.capacidad_litros)      AS litros_pasada
             FROM ruta_contenedor rc
             JOIN contenedor c ON c.id = rc.contenedor_id
             GROUP BY COALESCE(NULLIF(c.zona, ''), 'Sin zona asignada')
             ORDER BY litros_pasada DESC"
        );

        return array_map(function (array $f) use ($factorLlenado, $semanas): array {
            $porPasada = (int)$f['litros_pasada'];
            $litros = (int)round($porPasada * $semanas * $factorLlenado);
            return [
                'zona'            => $f['zona'],
                'contenedores'    => (int)$f['contenedores'],
                'litrosPorPasada' => $porPasada,
                'pasadas'         => $semanas,
                'litros'          => $litros,
                'metrosCubicos'   => round($litros / 1000, 2),
            ];
        }, $filas);
    }

    /**
     * Lo mismo pero abierto por ruta, para poder auditar de dónde sale
     * cada litro del total.
     *
     * @return array<int, array<string, mixed>>
     */
    public function volumenPorRuta(float $factorLlenado = 0.85, int $semanas = 4): array
    {
        $filas = $this->filas(
            "SELECT r.id, r.nombre, r.dia_semana, r.turno,
                    COALESCE(NULLIF(r.zona, ''), 'Sin zona') AS zona,
                    COUNT(rc.contenedor_id)  AS contenedores,
                    COALESCE(SUM(c.capacidad_litros), 0) AS litros_pasada
             FROM ruta r
             LEFT JOIN ruta_contenedor rc ON rc.ruta_id = r.id
             LEFT JOIN contenedor c       ON c.id = rc.contenedor_id
             GROUP BY r.id, r.nombre, r.dia_semana, r.turno, r.zona
             ORDER BY litros_pasada DESC"
        );

        return array_map(function (array $f) use ($factorLlenado, $semanas): array {
            $porPasada = (int)$f['litros_pasada'];
            $litros = (int)round($porPasada * $semanas * $factorLlenado);
            return [
                'rutaId'          => (int)$f['id'],
                'nombre'          => $f['nombre'],
                'zona'            => $f['zona'],
                'diaSemana'       => $f['dia_semana'],
                'turno'           => $f['turno'],
                'contenedores'    => (int)$f['contenedores'],
                'litrosPorPasada' => $porPasada,
                'litros'          => $litros,
                'metrosCubicos'   => round($litros / 1000, 2),
            ];
        }, $filas);
    }

    // =================================================================
    //  3.1.4.3 — Zonas críticas
    // =================================================================

    /**
     * Zonas ordenadas por conflictividad.
     *
     * No alcanza con contar incidencias: una zona con 200 contenedores va a
     * tener más que una con 20 sin ser peor. Por eso se devuelve también
     * cuántos contenedores tiene y el promedio de incidencias por
     * contenedor, que es la cifra comparable entre zonas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function zonasCriticas(): array
    {
        $filas = $this->filas(
            "SELECT z.zona,
                    COALESCE(c.contenedores, 0)    AS contenedores,
                    COALESCE(ins.instalaciones, 0) AS instalaciones,
                    COALESCE(i.total, 0)    AS total,
                    COALESCE(i.abiertas, 0) AS abiertas,
                    COALESCE(i.graves, 0)   AS graves
             FROM (
                 -- La lista de zonas sale de las DOS tablas. Si saliera solo
                 -- de contenedor, un municipio que únicamente tiene una
                 -- planta desaparecería del informe junto con sus
                 -- incidencias, sin que nadie se entere.
                 SELECT DISTINCT COALESCE(NULLIF(zona, ''), 'Sin zona asignada') AS zona FROM contenedor
                 UNION
                 SELECT DISTINCT COALESCE(NULLIF(zona, ''), 'Sin zona asignada') AS zona FROM instalacion
             ) z
             LEFT JOIN (
                 SELECT COALESCE(NULLIF(zona, ''), 'Sin zona asignada') AS zona, COUNT(*) AS contenedores
                 FROM contenedor
                 GROUP BY COALESCE(NULLIF(zona, ''), 'Sin zona asignada')
             ) c ON c.zona = z.zona
             LEFT JOIN (
                 SELECT COALESCE(NULLIF(zona, ''), 'Sin zona asignada') AS zona, COUNT(*) AS instalaciones
                 FROM instalacion
                 GROUP BY COALESCE(NULLIF(zona, ''), 'Sin zona asignada')
             ) ins ON ins.zona = z.zona
             LEFT JOIN (
                 -- Las incidencias cuelgan de un contenedor o de una
                 -- instalación, y las dos cuentan para la conflictividad.
                 SELECT COALESCE(NULLIF(COALESCE(co.zona, i2.zona), ''), 'Sin zona asignada') AS zona,
                        COUNT(*) AS total,
                        SUM(CASE WHEN inc.estado <> 'resuelta' THEN 1 ELSE 0 END) AS abiertas,
                        SUM(CASE WHEN inc.gravedad = 'alta'    THEN 1 ELSE 0 END) AS graves
                 FROM incidencia inc
                 LEFT JOIN contenedor  co ON co.id = inc.contenedor_id
                 LEFT JOIN instalacion i2 ON i2.id = inc.instalacion_id
                 GROUP BY COALESCE(NULLIF(COALESCE(co.zona, i2.zona), ''), 'Sin zona asignada')
             ) i ON i.zona = z.zona
             ORDER BY total DESC, z.zona"
        );

        return array_map(static function (array $f): array {
            $puntos = (int)$f['contenedores'] + (int)$f['instalaciones'];
            $total = (int)$f['total'];
            return [
                'zona'          => $f['zona'],
                'contenedores'  => (int)$f['contenedores'],
                'instalaciones' => (int)$f['instalaciones'],
                'puntos'        => $puntos,
                'total'         => $total,
                'abiertas'      => (int)$f['abiertas'],
                'graves'        => (int)$f['graves'],
                // Incidencias por punto atendido (contenedor o instalación).
                // Es la cifra comparable entre zonas: una con 200 puntos va
                // a tener más incidencias que una con 20 sin ser peor.
                'porPunto'      => $puntos > 0 ? round($total / $puntos, 2) : 0.0,
            ];
        }, $filas);
    }

    // =================================================================
    //  3.1.4.4 — Tiempo promedio de resolución por cuadrilla
    // =================================================================

    /**
     * Cuánto tarda cada cuadrilla en resolver, en horas.
     *
     * Sale de restar fecha_reporte a fecha_resolucion, dos campos que el
     * sistema ya venía guardando. Solo cuentan las resueltas: una incidencia
     * todavía abierta no tiene tiempo de resolución y promediarla como cero
     * ensuciaría el número.
     *
     * Las incidencias resueltas sin cuadrilla asignada se agrupan aparte
     * en vez de descartarse, porque son trabajo que igual se hizo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function tiemposPorCuadrilla(): array
    {
        $filas = $this->filas(
            "SELECT COALESCE(cu.nombre, 'Sin cuadrilla asignada') AS cuadrilla,
                    COUNT(*)                                            AS resueltas,
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, i.fecha_reporte, i.fecha_resolucion)) / 60, 2) AS horas_promedio,
                    ROUND(MIN(TIMESTAMPDIFF(MINUTE, i.fecha_reporte, i.fecha_resolucion)) / 60, 2) AS horas_min,
                    ROUND(MAX(TIMESTAMPDIFF(MINUTE, i.fecha_reporte, i.fecha_resolucion)) / 60, 2) AS horas_max,
                    SUM(CASE WHEN i.gravedad = 'alta' THEN 1 ELSE 0 END) AS graves
             FROM incidencia i
             LEFT JOIN cuadrilla cu ON cu.id = i.cuadrilla_id
             WHERE i.estado = 'resuelta' AND i.fecha_resolucion IS NOT NULL
             GROUP BY COALESCE(cu.nombre, 'Sin cuadrilla asignada')
             ORDER BY horas_promedio ASC"
        );

        return array_map(static fn(array $f): array => [
            'cuadrilla'     => $f['cuadrilla'],
            'resueltas'     => (int)$f['resueltas'],
            'horasPromedio' => (float)$f['horas_promedio'],
            'horasMin'      => (float)$f['horas_min'],
            'horasMax'      => (float)$f['horas_max'],
            'graves'        => (int)$f['graves'],
        ], $filas);
    }

    // =================================================================
    //  3.1.4.5 — Indicadores del panel de control
    // =================================================================

    /**
     * Los números de arriba del dashboard, en una sola consulta por bloque.
     *
     * @return array<string, mixed>
     */
    public function indicadores(float $factorLlenado = 0.85, int $semanas = 4): array
    {
        $c = $this->fila(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'roto'            THEN 1 ELSE 0 END) AS rotos,
                    SUM(CASE WHEN nivel_llenado = 'desbordado' THEN 1 ELSE 0 END) AS desbordados,
                    SUM(CASE WHEN zona IS NULL OR zona = ''  THEN 1 ELSE 0 END) AS sin_zona,
                    COUNT(DISTINCT NULLIF(zona, ''))                            AS zonas
             FROM contenedor"
        ) ?? [];

        $i = $this->fila(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'abierta'  THEN 1 ELSE 0 END) AS abiertas,
                    SUM(CASE WHEN estado = 'en_curso' THEN 1 ELSE 0 END) AS en_curso,
                    SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) AS resueltas,
                    SUM(CASE WHEN gravedad = 'alta' AND estado <> 'resuelta' THEN 1 ELSE 0 END) AS graves_abiertas,
                    ROUND(AVG(CASE WHEN estado = 'resuelta' AND fecha_resolucion IS NOT NULL
                              THEN TIMESTAMPDIFF(MINUTE, fecha_reporte, fecha_resolucion) END) / 60, 2) AS horas_promedio
             FROM incidencia"
        ) ?? [];

        $r = $this->fila("SELECT COUNT(*) AS total FROM ruta") ?? [];

        $volumen = array_sum(array_column($this->volumenPorZona($factorLlenado, $semanas), 'litros'));

        $totalInc = (int)($i['total'] ?? 0);
        $resueltas = (int)($i['resueltas'] ?? 0);

        return [
            'contenedores'        => (int)($c['total'] ?? 0),
            'contenedoresRotos'   => (int)($c['rotos'] ?? 0),
            'contenedoresDesbordados' => (int)($c['desbordados'] ?? 0),
            'contenedoresSinZona' => (int)($c['sin_zona'] ?? 0),
            'zonasCubiertas'      => (int)($c['zonas'] ?? 0),
            'rutas'               => (int)($r['total'] ?? 0),
            'incidencias'         => $totalInc,
            'incidenciasAbiertas' => (int)($i['abiertas'] ?? 0),
            'incidenciasEnCurso'  => (int)($i['en_curso'] ?? 0),
            'incidenciasResueltas'=> $resueltas,
            'gravesAbiertas'      => (int)($i['graves_abiertas'] ?? 0),
            'horasPromedioResolucion' => $i['horas_promedio'] !== null ? (float)$i['horas_promedio'] : null,
            'tasaResolucion'      => $totalInc > 0 ? round(100 * $resueltas / $totalInc, 1) : null,
            'volumenLitros'       => $volumen,
            'volumenM3'           => round($volumen / 1000, 2),
            'factorLlenado'       => $factorLlenado,
            'semanas'             => $semanas,
        ];
    }
}
