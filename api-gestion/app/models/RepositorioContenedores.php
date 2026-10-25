<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioContenedores: persistencia de contenedores en MySQL.
 * Hereda el motor común de RepositorioSql; acá solo lo específico.
 * (Los datos de prueba los inserta base.sql.)
 */
class RepositorioContenedores extends RepositorioSql
{
    protected function tabla(): string
    {
        return 'contenedor';
    }

    protected function aObjeto(array $f): object
    {
        return new Contenedor(
            (int)$f['id'],
            $f['codigo'],
            $f['direccion'],
            $f['tipo_residuo'],
            $f['estado'],
            $f['lat'] !== null ? (float)$f['lat'] : null,
            $f['lng'] !== null ? (float)$f['lng'] : null,
            $f['nivel_llenado'] ?? 'vacio',
            $f['zona'] ?? null
        );
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Contenedor $c): void
    {
        $this->consulta(
            'INSERT INTO contenedor (id, codigo, direccion, zona, tipo_residuo, estado, nivel_llenado, lat, lng)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$c->getId(), $c->getCodigo(), $c->getDireccion(), $c->getZona(), $c->getTipoResiduo(),
             $c->getEstado(), $c->getNivelLlenado(), $c->getLat(), $c->getLng()]
        );
    }

    /** Actualiza un contenedor existente. Devuelve true si existía. */
    public function actualizar(Contenedor $c): bool
    {
        $this->consulta(
            'UPDATE contenedor SET codigo = ?, direccion = ?, zona = ?, tipo_residuo = ?, estado = ?,
                    nivel_llenado = ?, lat = ?, lng = ? WHERE id = ?',
            [$c->getCodigo(), $c->getDireccion(), $c->getZona(), $c->getTipoResiduo(), $c->getEstado(),
             $c->getNivelLlenado(), $c->getLat(), $c->getLng(), $c->getId()]
        );
        return $this->fila('SELECT id FROM contenedor WHERE id = ?', [$c->getId()]) !== null;
    }

    /**
     * Zonas distintas que hoy tienen contenedores asignados.
     * La usan los filtros de los informes (3.1.4.1) para no ofrecer
     * municipios en los que no hay nada cargado.
     *
     * @return string[] nombres de zona, ordenados alfabéticamente
     */
    public function zonas(): array
    {
        $filas = $this->filas(
            "SELECT DISTINCT zona FROM contenedor
             WHERE zona IS NOT NULL AND zona <> ''
             ORDER BY zona"
        );
        return array_map(static fn(array $f): string => $f['zona'], $filas);
    }
}
