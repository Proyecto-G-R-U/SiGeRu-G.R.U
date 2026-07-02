<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Camión recolector de la flota.
 *
 * Requerimientos que apoya:
 *   3.1.1.2.4 ABM de camiones recolectores
 *   3.1.1.2.5 estado de disponibilidad y mantenimiento
 */
class Camion implements \JsonSerializable
{
    private int $id;
    private string $patente;
    private string $modelo;
    private string $estado;          // 'operativo' | 'mantenimiento'
    private string $disponibilidad;  // 'disponible' | 'en_ruta'

    public function __construct(
        int $id,
        string $patente,
        string $modelo,
        string $estado,
        string $disponibilidad
    ) {
        $this->id = $id;
        $this->patente = $patente;
        $this->modelo = $modelo;
        $this->estado = $estado;
        $this->disponibilidad = $disponibilidad;
    }

    public function getId(): int                 { return $this->id; }
    public function getPatente(): string         { return $this->patente; }
    public function getModelo(): string          { return $this->modelo; }
    public function getEstado(): string          { return $this->estado; }
    public function getDisponibilidad(): string  { return $this->disponibilidad; }

    public function setPatente(string $p): void        { $this->patente = $p; }
    public function setModelo(string $m): void         { $this->modelo = $m; }
    public function setEstado(string $e): void         { $this->estado = $e; }
    public function setDisponibilidad(string $d): void { $this->disponibilidad = $d; }

    public function jsonSerialize(): array
    {
        return [
            'id'             => $this->id,
            'patente'        => $this->patente,
            'modelo'         => $this->modelo,
            'estado'         => $this->estado,
            'disponibilidad' => $this->disponibilidad,
        ];
    }
}
