<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Operario interno (clase base ABSTRACTA).
 *
 * Antes había un solo Operario con un campo "especialidad". Ahora Operario
 * es la base abstracta de una jerarquía de TRES subclases concretas:
 *
 *     Usuario (abstracta)
 *       └── Operario (abstracta)   ← esta clase
 *             ├── OperarioRecoleccion
 *             ├── OperarioClasificacion
 *             └── OperarioVertedero
 *
 * Todo lo COMÚN a los tres operarios vive acá (la cuadrilla, el rol base).
 * Lo que los DIFERENCIA (su especialidad) lo define cada subclase con el
 * método abstracto getEspecialidad(). Eso es herencia + polimorfismo.
 *
 * Es abstracta porque "un operario a secas" no existe: siempre es de
 * recolección, de clasificación o de vertedero.
 */
abstract class Operario extends Usuario
{
<<<<<<< HEAD
    protected ?string $cuadrilla;    // agrupación que asigna el admin (puede ser null)
    protected ?int $instalacionId;   // instalación asignada (clasificación/vertedero)
=======
    protected ?string $cuadrilla; // agrupación que asigna el admin (puede ser null)
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c

    public function __construct(
        int $id,
        string $nombre,
        string $email,
        string $passwordHash,
<<<<<<< HEAD
        ?string $cuadrilla = null,
        ?int $instalacionId = null
    ) {
        parent::__construct($id, $nombre, $email, $passwordHash);
        $this->cuadrilla = $cuadrilla;
        $this->instalacionId = $instalacionId;
=======
        ?string $cuadrilla = null
    ) {
        parent::__construct($id, $nombre, $email, $passwordHash);
        $this->cuadrilla = $cuadrilla;
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
    }

    /** Todos los operarios comparten el rol base "operario". */
    public function getRol(): string
    {
        return 'operario';
    }

    /**
     * Cada subclase DEBE definir su especialidad. La base no sabe cuál es.
     * (método abstracto = obliga a las hijas a implementarlo)
     */
    abstract public function getEspecialidad(): string;

    public function getCuadrilla(): ?string    { return $this->cuadrilla; }
    public function setCuadrilla(?string $c): void { $this->cuadrilla = $c; }

<<<<<<< HEAD
    public function getInstalacionId(): ?int      { return $this->instalacionId; }
    public function setInstalacionId(?int $i): void { $this->instalacionId = $i; }

=======
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
    /**
     * Al serializar a JSON agregamos especialidad y cuadrilla a lo que ya
     * trae el padre (id, nombre, email, rol). Como getEspecialidad() es
     * polimórfico, cada subclase aporta su propio valor automáticamente.
     */
    public function jsonSerialize(): array
    {
        $base = parent::jsonSerialize();
        $base['especialidad'] = $this->getEspecialidad();
        $base['cuadrilla'] = $this->cuadrilla;
<<<<<<< HEAD
        $base['instalacionId'] = $this->instalacionId;
=======
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
        return $base;
    }
}
