<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Contenedor de residuos.
 *
 * Requerimientos: 3.1.1.2.1 (ABM), 3.1.1.2.2 (ubicación geográfica exacta),
 * 3.1.1.2.3 (tipo de residuo y estado).
 *
 * La ubicación se guarda de dos formas complementarias:
 *   - direccion: texto legible (ej. "Av. Italia y Propios")
 *   - lat / lng: coordenadas exactas para ubicarlo en el mapa
 */
class Contenedor implements \JsonSerializable
{
    private int $id;
    private string $codigo;
    private string $direccion;
    private string $tipoResiduo; // 'mezclado' | 'reciclable'
    private string $estado;      // 'operativo' | 'roto' | 'desbordado'
    private ?float $lat;
    private ?float $lng;

    public function __construct(
        int $id,
        string $codigo,
        string $direccion,
        string $tipoResiduo,
        string $estado,
        ?float $lat = null,
        ?float $lng = null
    ) {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->direccion = $direccion;
        $this->tipoResiduo = $tipoResiduo;
        $this->estado = $estado;
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function getId(): int              { return $this->id; }
    public function getCodigo(): string       { return $this->codigo; }
    public function getDireccion(): string    { return $this->direccion; }
    public function getTipoResiduo(): string  { return $this->tipoResiduo; }
    public function getEstado(): string       { return $this->estado; }
    public function getLat(): ?float          { return $this->lat; }
    public function getLng(): ?float          { return $this->lng; }

    public function setCodigo(string $c): void      { $this->codigo = $c; }
    public function setDireccion(string $d): void   { $this->direccion = $d; }
    public function setTipoResiduo(string $t): void { $this->tipoResiduo = $t; }
    public function setEstado(string $e): void      { $this->estado = $e; }
    public function setLat(?float $l): void         { $this->lat = $l; }
    public function setLng(?float $l): void         { $this->lng = $l; }

    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'codigo'      => $this->codigo,
            'direccion'   => $this->direccion,
            'tipoResiduo' => $this->tipoResiduo,
            'estado'      => $this->estado,
            'lat'         => $this->lat,
            'lng'         => $this->lng,
        ];
    }
}
