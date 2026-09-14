<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Cuadrilla de trabajo.
 *
 * Agrupa operarios (requerimientos 3.1.1.1.3 y 3.1.1.1.4). El admin la crea
 * en el apartado de gestión de usuarios. Guarda los ids de los operarios que
 * la integran.
 */
class Cuadrilla implements \JsonSerializable
{
    private int $id;
    private string $nombre;
    private ?string $zona;
    /** @var int[] ids de operarios que integran la cuadrilla */
    private array $operarios;

    public function __construct(int $id, string $nombre, ?string $zona = null, array $operarios = [])
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->zona = $zona;
        $this->operarios = $operarios;
    }

    public function getId(): int          { return $this->id; }
    public function getNombre(): string   { return $this->nombre; }
    public function getZona(): ?string    { return $this->zona; }
    public function getOperarios(): array { return $this->operarios; }

    public function setNombre(string $n): void { $this->nombre = $n; }
    public function setZona(?string $z): void  { $this->zona = $z; }
    public function setOperarios(array $o): void { $this->operarios = $o; }

    public function jsonSerialize(): array
    {
        return [
            'id'        => $this->id,
            'nombre'    => $this->nombre,
            'zona'      => $this->zona,
            'operarios' => $this->operarios,
        ];
    }
}
