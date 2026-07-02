<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Contenedor de residuos.
 *
 * Requerimientos que apoya:
 *   3.1.1.2.1 ABM de contenedores
 *   3.1.1.2.2 ubicación geográfica exacta
 *   3.1.1.2.3 tipo de residuo (mezclado/reciclable) y estado funcional
 *
 * No usa herencia porque un contenedor no tiene subtipos: se distingue
 * por sus datos (tipo y estado), no por su clase.
 */
class Contenedor implements \JsonSerializable
{
    private int $id;
    private string $codigo;
    private string $ubicacion;
    private string $tipoResiduo; // 'mezclado' | 'reciclable'
    private string $estado;      // 'operativo' | 'roto' | 'desbordado'

    public function __construct(
        int $id,
        string $codigo,
        string $ubicacion,
        string $tipoResiduo,
        string $estado
    ) {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->ubicacion = $ubicacion;
        $this->tipoResiduo = $tipoResiduo;
        $this->estado = $estado;
    }

    public function getId(): int              { return $this->id; }
    public function getCodigo(): string       { return $this->codigo; }
    public function getUbicacion(): string    { return $this->ubicacion; }
    public function getTipoResiduo(): string  { return $this->tipoResiduo; }
    public function getEstado(): string       { return $this->estado; }

    public function setCodigo(string $c): void      { $this->codigo = $c; }
    public function setUbicacion(string $u): void   { $this->ubicacion = $u; }
    public function setTipoResiduo(string $t): void { $this->tipoResiduo = $t; }
    public function setEstado(string $e): void      { $this->estado = $e; }

    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'codigo'      => $this->codigo,
            'ubicacion'   => $this->ubicacion,
            'tipoResiduo' => $this->tipoResiduo,
            'estado'      => $this->estado,
        ];
    }
}
