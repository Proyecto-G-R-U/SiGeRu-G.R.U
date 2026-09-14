<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Instalacion: clase ABSTRACTA base de los lugares físicos donde se procesan
 * o depositan los residuos (requerimiento 3.1.1.2.6).
 *
 * Es abstracta por el mismo criterio que Usuario: no existe una "instalación
 * a secas", siempre es un centro de acopio, una planta de clasificación o un
 * vertedero. Lo que le falta al molde es el tipo, que cada subclase completa
 * con el método abstracto getTipo().
 *
 * Las tres subclases se guardan en la MISMA tabla `instalacion`, distinguidas
 * por la columna `tipo` (igual que usuario/rol).
 */
abstract class Instalacion implements \JsonSerializable
{
    protected int $id;
    protected string $nombre;
    protected string $direccion;
    protected int $capacidadMaxima;   // tope en toneladas
    protected int $ocupacionActual;   // cuánto lleva ocupado hoy
    protected string $tipoResiduo;    // 'mezclado' | 'reciclable' | 'ambos'
    protected string $estado;         // 'operativa' | 'mantenimiento' | 'fuera_servicio'
    protected ?float $lat;            // ubicación exacta para el mapa
    protected ?float $lng;

    public function __construct(
        int $id,
        string $nombre,
        string $direccion,
        int $capacidadMaxima,
        string $tipoResiduo = 'mezclado',
        string $estado = 'operativa',
        ?float $lat = null,
        ?float $lng = null,
        int $ocupacionActual = 0
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->direccion = $direccion;
        $this->capacidadMaxima = $capacidadMaxima;
        $this->tipoResiduo = $tipoResiduo;
        $this->estado = $estado;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->ocupacionActual = $ocupacionActual;
    }

    /** Cada subclase define qué tipo de instalación es. */
    abstract public function getTipo(): string;

    /** Nombre legible del tipo, para mostrar en pantalla. */
    abstract public function getTipoLegible(): string;

    public function getId(): int              { return $this->id; }
    public function getNombre(): string       { return $this->nombre; }
    public function getDireccion(): string    { return $this->direccion; }
    public function getCapacidadMaxima(): int { return $this->capacidadMaxima; }
    public function getOcupacionActual(): int { return $this->ocupacionActual; }

    /** Porcentaje de ocupación (0-100), para las barras de progreso. */
    public function getPorcentajeOcupacion(): int
    {
        if ($this->capacidadMaxima <= 0) { return 0; }
        return (int)round($this->ocupacionActual * 100 / $this->capacidadMaxima);
    }
    public function getTipoResiduo(): string  { return $this->tipoResiduo; }
    public function getEstado(): string       { return $this->estado; }
    public function getLat(): ?float          { return $this->lat; }
    public function getLng(): ?float          { return $this->lng; }

    public function setNombre(string $n): void      { $this->nombre = $n; }
    public function setDireccion(string $d): void   { $this->direccion = $d; }
    public function setCapacidadMaxima(int $c): void { $this->capacidadMaxima = $c; }
    public function setOcupacionActual(int $o): void { $this->ocupacionActual = $o; }
    public function setTipoResiduo(string $t): void { $this->tipoResiduo = $t; }
    public function setEstado(string $e): void      { $this->estado = $e; }
    public function setLat(?float $l): void         { $this->lat = $l; }
    public function setLng(?float $l): void         { $this->lng = $l; }

    public function jsonSerialize(): array
    {
        return [
            'id'              => $this->id,
            'nombre'          => $this->nombre,
            'direccion'       => $this->direccion,
            'tipo'            => $this->getTipo(),
            'tipoLegible'     => $this->getTipoLegible(),
            'capacidadMaxima' => $this->capacidadMaxima,
            'ocupacionActual' => $this->ocupacionActual,
            'porcentaje'      => $this->getPorcentajeOcupacion(),
            'tipoResiduo'     => $this->tipoResiduo,
            'estado'          => $this->estado,
            'lat'             => $this->lat,
            'lng'             => $this->lng,
        ];
    }
}
