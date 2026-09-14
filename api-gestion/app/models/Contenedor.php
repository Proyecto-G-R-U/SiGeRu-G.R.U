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
<<<<<<< HEAD
    private string $estado;       // INTEGRIDAD física: 'operativo' | 'roto'
    private string $nivelLlenado; // CUÁNTO TIENE: 'vacio' | 'lleno' | 'desbordado'
=======
    private string $estado;      // 'operativo' | 'roto' | 'desbordado'
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
    private ?float $lat;
    private ?float $lng;

    public function __construct(
        int $id,
        string $codigo,
        string $direccion,
        string $tipoResiduo,
        string $estado,
        ?float $lat = null,
<<<<<<< HEAD
        ?float $lng = null,
        string $nivelLlenado = 'vacio'
=======
        ?float $lng = null
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
    ) {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->direccion = $direccion;
        $this->tipoResiduo = $tipoResiduo;
        $this->estado = $estado;
<<<<<<< HEAD
        $this->nivelLlenado = $nivelLlenado;
=======
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function getId(): int              { return $this->id; }
    public function getCodigo(): string       { return $this->codigo; }
    public function getDireccion(): string    { return $this->direccion; }
    public function getTipoResiduo(): string  { return $this->tipoResiduo; }
    public function getEstado(): string       { return $this->estado; }
<<<<<<< HEAD
    public function getNivelLlenado(): string { return $this->nivelLlenado; }
=======
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
    public function getLat(): ?float          { return $this->lat; }
    public function getLng(): ?float          { return $this->lng; }

    public function setCodigo(string $c): void      { $this->codigo = $c; }
    public function setDireccion(string $d): void   { $this->direccion = $d; }
    public function setTipoResiduo(string $t): void { $this->tipoResiduo = $t; }
    public function setEstado(string $e): void      { $this->estado = $e; }
<<<<<<< HEAD
    public function setNivelLlenado(string $n): void { $this->nivelLlenado = $n; }
=======
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
    public function setLat(?float $l): void         { $this->lat = $l; }
    public function setLng(?float $l): void         { $this->lng = $l; }

    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'codigo'      => $this->codigo,
            'direccion'   => $this->direccion,
            'tipoResiduo' => $this->tipoResiduo,
<<<<<<< HEAD
            'estado'       => $this->estado,
            'nivelLlenado' => $this->nivelLlenado,
=======
            'estado'      => $this->estado,
>>>>>>> 900ec4a2af4c3bd6139702994975f1671d87a12c
            'lat'         => $this->lat,
            'lng'         => $this->lng,
        ];
    }
}
