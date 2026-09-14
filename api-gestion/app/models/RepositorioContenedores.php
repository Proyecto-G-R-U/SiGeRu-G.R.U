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
<<<<<<< HEAD
            $f['lng'] !== null ? (float)$f['lng'] : null,
            $f['nivel_llenado'] ?? 'vacio'
=======
            $f['lng'] !== null ? (float)$f['lng'] : null
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
        );
    }

    // ---------------- Métodos específicos ----------------

    public function agregar(Contenedor $c): void
    {
        $this->consulta(
<<<<<<< HEAD
            'INSERT INTO contenedor (id, codigo, direccion, tipo_residuo, estado, nivel_llenado, lat, lng)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$c->getId(), $c->getCodigo(), $c->getDireccion(), $c->getTipoResiduo(),
             $c->getEstado(), $c->getNivelLlenado(), $c->getLat(), $c->getLng()]
=======
            'INSERT INTO contenedor (id, codigo, direccion, tipo_residuo, estado, lat, lng)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$c->getId(), $c->getCodigo(), $c->getDireccion(), $c->getTipoResiduo(),
             $c->getEstado(), $c->getLat(), $c->getLng()]
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
        );
    }

    /** Actualiza un contenedor existente. Devuelve true si existía. */
    public function actualizar(Contenedor $c): bool
    {
        $this->consulta(
<<<<<<< HEAD
            'UPDATE contenedor SET codigo = ?, direccion = ?, tipo_residuo = ?, estado = ?,
                    nivel_llenado = ?, lat = ?, lng = ? WHERE id = ?',
            [$c->getCodigo(), $c->getDireccion(), $c->getTipoResiduo(), $c->getEstado(),
             $c->getNivelLlenado(), $c->getLat(), $c->getLng(), $c->getId()]
=======
            'UPDATE contenedor SET codigo = ?, direccion = ?, tipo_residuo = ?, estado = ?, lat = ?, lng = ?
             WHERE id = ?',
            [$c->getCodigo(), $c->getDireccion(), $c->getTipoResiduo(), $c->getEstado(),
             $c->getLat(), $c->getLng(), $c->getId()]
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
        );
        return $this->fila('SELECT id FROM contenedor WHERE id = ?', [$c->getId()]) !== null;
    }
}
