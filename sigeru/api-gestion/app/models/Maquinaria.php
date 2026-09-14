<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Maquinaria: equipos, herramientas y stock de repuestos de una instalación
 * (requerimiento 3.1.1.2.7).
 *
 * Cubre tanto maquinaria propiamente dicha (cintas, prensas, palas
 * mecánicas) como stock de contenedores de repuesto, distinguidos por el
 * campo `categoria`. Cada ítem pertenece a UNA instalación.
 */
class Maquinaria implements \JsonSerializable
{
    private int $id;
    private string $nombre;
    private string $categoria;   // 'maquinaria' | 'herramienta' | 'repuesto'
    private string $estado;      // 'operativa' | 'mantenimiento' | 'rota'
    private int $cantidad;
    private ?int $instalacionId; // instalación donde está (o null)

    public function __construct(
        int $id,
        string $nombre,
        string $categoria,
        string $estado,
        int $cantidad,
        ?int $instalacionId = null
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->categoria = $categoria;
        $this->estado = $estado;
        $this->cantidad = $cantidad;
        $this->instalacionId = $instalacionId;
    }

    public function getId(): int             { return $this->id; }
    public function getNombre(): string      { return $this->nombre; }
    public function getCategoria(): string   { return $this->categoria; }
    public function getEstado(): string      { return $this->estado; }
    public function getCantidad(): int       { return $this->cantidad; }
    public function getInstalacionId(): ?int { return $this->instalacionId; }

    public function setNombre(string $n): void       { $this->nombre = $n; }
    public function setCategoria(string $c): void    { $this->categoria = $c; }
    public function setEstado(string $e): void       { $this->estado = $e; }
    public function setCantidad(int $c): void        { $this->cantidad = $c; }
    public function setInstalacionId(?int $i): void  { $this->instalacionId = $i; }

    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'nombre'        => $this->nombre,
            'categoria'     => $this->categoria,
            'estado'        => $this->estado,
            'cantidad'      => $this->cantidad,
            'instalacionId' => $this->instalacionId,
        ];
    }
}
