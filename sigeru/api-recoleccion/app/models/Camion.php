<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Camión recolector.
 *
 * Requerimientos: 3.1.1.2.4 (ABM de camiones), 3.1.1.2.5 (estado y
 * disponibilidad). Además, en nuestro modelo cada camión:
 *   - pertenece a UNA flota (flotaId), y
 *   - tiene asignada UNA cuadrilla (cuadrillaId), o ninguna (null).
 */
class Camion implements \JsonSerializable
{
    private int $id;
    private string $patente;
    private string $modelo;
    private string $estado;          // 'operativo' | 'mantenimiento'
    private string $disponibilidad;  // 'disponible' | 'en_ruta'
    private ?int $flotaId;           // flota a la que pertenece (o null)
    private ?int $cuadrillaId;       // cuadrilla asignada (o null)

    public function __construct(
        int $id,
        string $patente,
        string $modelo,
        string $estado,
        string $disponibilidad,
        ?int $flotaId = null,
        ?int $cuadrillaId = null
    ) {
        $this->id = $id;
        $this->patente = $patente;
        $this->modelo = $modelo;
        $this->estado = $estado;
        $this->disponibilidad = $disponibilidad;
        $this->flotaId = $flotaId;
        $this->cuadrillaId = $cuadrillaId;
    }

    public function getId(): int                 { return $this->id; }
    public function getPatente(): string         { return $this->patente; }
    public function getModelo(): string          { return $this->modelo; }
    public function getEstado(): string          { return $this->estado; }
    public function getDisponibilidad(): string  { return $this->disponibilidad; }
    public function getFlotaId(): ?int           { return $this->flotaId; }
    public function getCuadrillaId(): ?int       { return $this->cuadrillaId; }

    public function setPatente(string $p): void        { $this->patente = $p; }
    public function setModelo(string $m): void         { $this->modelo = $m; }
    public function setEstado(string $e): void         { $this->estado = $e; }
    public function setDisponibilidad(string $d): void { $this->disponibilidad = $d; }
    public function setFlotaId(?int $f): void          { $this->flotaId = $f; }
    public function setCuadrillaId(?int $c): void      { $this->cuadrillaId = $c; }

    public function jsonSerialize(): array
    {
        return [
            'id'             => $this->id,
            'patente'        => $this->patente,
            'modelo'         => $this->modelo,
            'estado'         => $this->estado,
            'disponibilidad' => $this->disponibilidad,
            'flotaId'        => $this->flotaId,
            'cuadrillaId'    => $this->cuadrillaId,
        ];
    }
}
