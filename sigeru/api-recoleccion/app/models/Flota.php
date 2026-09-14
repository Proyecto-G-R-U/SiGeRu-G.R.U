<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Flota: agrupación de camiones (ej. por zona). Un camión pertenece a una
 * sola flota. La flota no guarda la lista de camiones: son los camiones los
 * que apuntan a su flota (flotaId). Así evitamos duplicar la relación.
 */
class Flota implements \JsonSerializable
{
    private int $id;
    private string $nombre;
    private ?string $zona;

    public function __construct(int $id, string $nombre, ?string $zona = null)
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->zona = $zona;
    }

    public function getId(): int        { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getZona(): ?string  { return $this->zona; }

    public function setNombre(string $n): void { $this->nombre = $n; }
    public function setZona(?string $z): void  { $this->zona = $z; }

    public function jsonSerialize(): array
    {
        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
            'zona'   => $this->zona,
        ];
    }
}
