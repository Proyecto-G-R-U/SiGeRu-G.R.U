<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioIncidencias: persistencia de incidencias en MySQL.
 * Hereda el motor común de RepositorioSql; acá solo lo específico.
 */
class RepositorioIncidencias extends RepositorioSql
{
    protected function tabla(): string
    {
        return 'incidencia';
    }

    protected function aObjeto(array $f): object
    {
        $i = new Incidencia(
            (int)$f['id'],
            (int)$f['contenedor_id'],
            $f['tipo'],
            $f['descripcion'],
            $f['gravedad'],
            $f['estado'],
            $f['cuadrilla_id'] !== null ? (int)$f['cuadrilla_id'] : null,
            $f['reportado_por'] !== null ? (int)$f['reportado_por'] : null,
            $f['reportado_por_nombre'] ?? '',
            (string)$f['fecha_reporte'],
            $f['fecha_resolucion'] !== null ? (string)$f['fecha_resolucion'] : null
        );
        // Si la consulta unió con contenedor, aprovechamos esos datos.
        if (array_key_exists('zona', $f) || array_key_exists('contenedor_codigo', $f)) {
            $i->setDatosContenedor($f['zona'] ?? null, $f['contenedor_codigo'] ?? null);
        }
        return $i;
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Incidencia $i): void
    {
        $this->consulta(
            'INSERT INTO incidencia
                (id, contenedor_id, tipo, descripcion, gravedad, estado, cuadrilla_id,
                 reportado_por, reportado_por_nombre, fecha_reporte)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$i->getId(), $i->getContenedorId(), $i->getTipo(), $i->getDescripcion(),
             $i->getGravedad(), $i->getEstado(), $i->getCuadrillaId(),
             $i->getReportadoPor(), $i->getReportadoPorNombre()]
        );
    }

    /**
     * Incidencias ACTIVAS: las que todavía no se resolvieron. Son las que se
     * ven en el mapa. Las resueltas pasan al historial.
     */
    public function activas(): array
    {
        return array_map(
            [$this, 'aObjeto'],
            $this->filas("SELECT * FROM incidencia WHERE estado <> 'resuelta'
                          ORDER BY FIELD(gravedad,'alta','media','baja'), fecha_reporte DESC")
        );
    }

    /** Historial: las incidencias ya resueltas (requerimiento 3.1.1.3.4). */
    public function historial(): array
    {
        return array_map(
            [$this, 'aObjeto'],
            $this->filas("SELECT * FROM incidencia WHERE estado = 'resuelta'
                          ORDER BY fecha_resolucion DESC")
        );
    }

    /**
     * Historial filtrable (requerimiento 3.1.1.3.4, y base del 3.1.4.1).
     *
     * Une con contenedor porque la zona vive ahí, no en la incidencia: así
     * si un contenedor cambia de municipio, su historial lo acompaña solo.
     *
     * Todos los filtros son opcionales; los que vengan en null no se aplican.
     * El WHERE se arma juntando condiciones con marcadores ?, nunca pegando
     * los valores en el texto del SQL: eso es lo que evita la inyección.
     *
     * @param string|null $estado 'resuelta', 'abierta', 'en_curso' o null = todas
     */
    public function historialFiltrado(
        ?string $estado = 'resuelta',
        ?string $zona = null,
        ?int $contenedorId = null,
        ?string $gravedad = null
    ): array {
        $condiciones = [];
        $params = [];

        if ($estado !== null && $estado !== '') {
            $condiciones[] = 'i.estado = ?';
            $params[] = $estado;
        }
        if ($zona !== null && $zona !== '') {
            // 'sin-zona' busca los contenedores a los que todavía no se les
            // asignó municipio: son los que quedarían invisibles en un
            // informe por zona si no se pudieran listar.
            if ($zona === 'sin-zona') {
                $condiciones[] = "(c.zona IS NULL OR c.zona = '')";
            } else {
                $condiciones[] = 'c.zona = ?';
                $params[] = $zona;
            }
        }
        if ($contenedorId !== null && $contenedorId > 0) {
            $condiciones[] = 'i.contenedor_id = ?';
            $params[] = $contenedorId;
        }
        if ($gravedad !== null && $gravedad !== '') {
            $condiciones[] = 'i.gravedad = ?';
            $params[] = $gravedad;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "SELECT i.*, c.zona AS zona, c.codigo AS contenedor_codigo
                FROM incidencia i
                JOIN contenedor c ON c.id = i.contenedor_id
                $where
                ORDER BY COALESCE(i.fecha_resolucion, i.fecha_reporte) DESC";

        return array_map([$this, 'aObjeto'], $this->filas($sql, $params));
    }

    /**
     * Cuántas incidencias tiene cada zona, de mayor a menor.
     * El historial la usa para el resumen de arriba; es además la consulta
     * que va a necesitar el informe de zonas críticas (3.1.4.3).
     *
     * @return array<int, array{zona: string, total: int}>
     */
    public function totalesPorZona(?string $estado = null): array
    {
        $where = '';
        $params = [];
        if ($estado !== null && $estado !== '') {
            $where = 'WHERE i.estado = ?';
            $params[] = $estado;
        }
        $filas = $this->filas(
            "SELECT COALESCE(NULLIF(c.zona, ''), 'Sin zona asignada') AS zona,
                    COUNT(*) AS total
             FROM incidencia i
             JOIN contenedor c ON c.id = i.contenedor_id
             $where
             GROUP BY COALESCE(NULLIF(c.zona, ''), 'Sin zona asignada')
             ORDER BY total DESC, zona", $params
        );
        return array_map(
            static fn(array $f): array => ['zona' => $f['zona'], 'total' => (int)$f['total']],
            $filas
        );
    }

    /** Incidencias activas de un contenedor concreto. */
    public function activasDeContenedor(int $contenedorId): array
    {
        return array_map(
            [$this, 'aObjeto'],
            $this->filas("SELECT * FROM incidencia
                          WHERE contenedor_id = ? AND estado <> 'resuelta'
                          ORDER BY fecha_reporte DESC", [$contenedorId])
        );
    }

    /** Cambia el estado de una incidencia. Devuelve true si existía. */
    public function cambiarEstado(int $id, string $estado): bool
    {
        if ($this->fila('SELECT id FROM incidencia WHERE id = ?', [$id]) === null) {
            return false;
        }
        // Al resolver se sella la fecha de resolución; si se reabre, se limpia.
        if ($estado === 'resuelta') {
            $this->consulta(
                'UPDATE incidencia SET estado = ?, fecha_resolucion = NOW() WHERE id = ?',
                [$estado, $id]
            );
        } else {
            $this->consulta(
                'UPDATE incidencia SET estado = ?, fecha_resolucion = NULL WHERE id = ?',
                [$estado, $id]
            );
        }
        return true;
    }

    /** Asigna (o quita) la cuadrilla responsable. Devuelve true si existía. */
    public function asignarCuadrilla(int $id, ?int $cuadrillaId): bool
    {
        if ($this->fila('SELECT id FROM incidencia WHERE id = ?', [$id]) === null) {
            return false;
        }
        $this->consulta('UPDATE incidencia SET cuadrilla_id = ? WHERE id = ?', [$cuadrillaId, $id]);
        return true;
    }
}
