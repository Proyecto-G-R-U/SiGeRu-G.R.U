<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioInstalaciones: persistencia de instalaciones en MySQL.
 *
 * Hereda de RepositorioSql el motor común. Lo específico acá es aObjeto(),
 * que rearma la SUBCLASE correcta según la columna `tipo` — igual que hace
 * RepositorioUsuarios con el rol. Así la herencia "revive" al leer de la BD.
 */
class RepositorioInstalaciones extends RepositorioSql
{
    protected function tabla(): string
    {
        return 'instalacion';
    }

    protected function aObjeto(array $f): object
    {
        $id  = (int)$f['id'];
        $cap = (int)$f['capacidad_maxima'];
        $lat = $f['lat'] !== null ? (float)$f['lat'] : null;
        $lng = $f['lng'] !== null ? (float)$f['lng'] : null;
        $ocu = (int)($f['ocupacion_actual'] ?? 0);
        return match ($f['tipo']) {
            'clasificacion' => new PlantaClasificacion($id, $f['nombre'], $f['direccion'], $cap, $f['tipo_residuo'], $f['estado'], $lat, $lng, $ocu),
            'vertedero'     => new Vertedero($id, $f['nombre'], $f['direccion'], $cap, $f['tipo_residuo'], $f['estado'], $lat, $lng, $ocu),
            default         => new CentroAcopio($id, $f['nombre'], $f['direccion'], $cap, $f['tipo_residuo'], $f['estado'], $lat, $lng, $ocu),
        };
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Instalacion $i): void
    {
        $this->consulta(
            'INSERT INTO instalacion (id, nombre, direccion, tipo, capacidad_maxima, ocupacion_actual, tipo_residuo, estado, lat, lng)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$i->getId(), $i->getNombre(), $i->getDireccion(), $i->getTipo(),
             $i->getCapacidadMaxima(), $i->getOcupacionActual(), $i->getTipoResiduo(), $i->getEstado(),
             $i->getLat(), $i->getLng()]
        );
    }

    /** Actualiza una instalación existente. Devuelve true si existía. */
    public function actualizar(Instalacion $i): bool
    {
        $this->consulta(
            'UPDATE instalacion SET nombre = ?, direccion = ?, tipo = ?, capacidad_maxima = ?,
                    ocupacion_actual = ?, tipo_residuo = ?, estado = ?, lat = ?, lng = ? WHERE id = ?',
            [$i->getNombre(), $i->getDireccion(), $i->getTipo(), $i->getCapacidadMaxima(),
             $i->getOcupacionActual(), $i->getTipoResiduo(), $i->getEstado(),
             $i->getLat(), $i->getLng(), $i->getId()]
        );
        return $this->fila('SELECT id FROM instalacion WHERE id = ?', [$i->getId()]) !== null;
    }

    /** Instalaciones de un tipo concreto (para los paneles de operarios). */
    public function porTipo(string $tipo): array
    {
        return array_map(
            [$this, 'aObjeto'],
            $this->filas('SELECT * FROM instalacion WHERE tipo = ? ORDER BY nombre', [$tipo])
        );
    }
}
