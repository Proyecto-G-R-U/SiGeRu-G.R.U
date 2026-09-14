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
        return new Incidencia(
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
