<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Operario interno.
 *
 * HEREDA de Usuario, pero agrega DOS datos propios que el administrador
 * define: la especialidad (recolección / clasificación / vertedero, según
 * 3.1.1.1.2) y la cuadrilla a la que pertenece (3.1.1.1.4).
 *
 * Un mismo Operario puede tener distinta especialidad; por eso NO hacemos
 * una subclase por cada especialidad, sino un campo. La cuadrilla es una
 * agrupación que arma el admin, no un tipo de usuario.
 */
class Operario extends Usuario
{
    private string $especialidad; // 'recoleccion' | 'clasificacion' | 'vertedero'
    private ?string $cuadrilla;   // puede no tener cuadrilla asignada todavía (null)

    public function __construct(
        int $id,
        string $nombre,
        string $email,
        string $passwordHash,
        string $especialidad,
        ?string $cuadrilla = null
    ) {
        // "parent::__construct" llama al constructor de la clase padre (Usuario)
        // para que rellene id, nombre, email y passwordHash. Así no repetimos.
        parent::__construct($id, $nombre, $email, $passwordHash);
        $this->especialidad = $especialidad;
        $this->cuadrilla = $cuadrilla;
    }

    public function getRol(): string
    {
        return 'operario';
    }

    public function getEspecialidad(): string    { return $this->especialidad; }
    public function getCuadrilla(): ?string      { return $this->cuadrilla; }
    public function setEspecialidad(string $e): void { $this->especialidad = $e; }
    public function setCuadrilla(?string $c): void   { $this->cuadrilla = $c; }

    /**
     * Sobreescribimos jsonSerialize para agregar los campos extra del operario.
     * Primero pedimos el JSON del padre (parent::) y le sumamos lo nuestro.
     */
    public function jsonSerialize(): array
    {
        $base = parent::jsonSerialize();
        $base['especialidad'] = $this->especialidad;
        $base['cuadrilla'] = $this->cuadrilla;
        return $base;
    }
}
