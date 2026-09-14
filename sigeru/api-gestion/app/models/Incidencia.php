<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Incidencia: problema reportado sobre un contenedor
 * (requerimientos 3.1.1.3.1 a 3.1.1.3.4, y 3.1.3.4 para vecinos).
 *
 * Cualquier usuario logueado puede reportarla desde el mapa. El
 * administrador le hace el seguimiento: cambia su estado, le asigna una
 * cuadrilla responsable y finalmente la resuelve (pasa al historial).
 *
 * Sobre "reportadoPorNombre": guardamos el nombre del que reportó como
 * copia, no solo su id. Así el historial sigue siendo legible aunque ese
 * usuario se elimine después, y evitamos consultar la API de usuarios
 * cada vez que se muestra una incidencia.
 */
class Incidencia implements \JsonSerializable
{
    private int $id;
    private int $contenedorId;
    private string $tipo;        // 'rotura' | 'desborde' | 'falta_recoleccion' | 'otro'
    private string $descripcion;
    private string $gravedad;    // 'baja' | 'media' | 'alta'
    private string $estado;      // 'abierta' | 'en_curso' | 'resuelta'
    private ?int $cuadrillaId;   // cuadrilla responsable (la asigna el admin)
    private ?int $reportadoPor;  // id del usuario que reportó
    private string $reportadoPorNombre;
    private string $fechaReporte;
    private ?string $fechaResolucion;

    public function __construct(
        int $id,
        int $contenedorId,
        string $tipo,
        string $descripcion,
        string $gravedad = 'media',
        string $estado = 'abierta',
        ?int $cuadrillaId = null,
        ?int $reportadoPor = null,
        string $reportadoPorNombre = '',
        string $fechaReporte = '',
        ?string $fechaResolucion = null
    ) {
        $this->id = $id;
        $this->contenedorId = $contenedorId;
        $this->tipo = $tipo;
        $this->descripcion = $descripcion;
        $this->gravedad = $gravedad;
        $this->estado = $estado;
        $this->cuadrillaId = $cuadrillaId;
        $this->reportadoPor = $reportadoPor;
        $this->reportadoPorNombre = $reportadoPorNombre;
        $this->fechaReporte = $fechaReporte;
        $this->fechaResolucion = $fechaResolucion;
    }

    public function getId(): int                  { return $this->id; }
    public function getContenedorId(): int        { return $this->contenedorId; }
    public function getTipo(): string             { return $this->tipo; }
    public function getDescripcion(): string      { return $this->descripcion; }
    public function getGravedad(): string         { return $this->gravedad; }
    public function getEstado(): string           { return $this->estado; }
    public function getCuadrillaId(): ?int        { return $this->cuadrillaId; }
    public function getReportadoPor(): ?int       { return $this->reportadoPor; }
    public function getReportadoPorNombre(): string { return $this->reportadoPorNombre; }
    public function getFechaReporte(): string     { return $this->fechaReporte; }
    public function getFechaResolucion(): ?string { return $this->fechaResolucion; }

    public function setTipo(string $t): void        { $this->tipo = $t; }
    public function setDescripcion(string $d): void { $this->descripcion = $d; }
    public function setGravedad(string $g): void    { $this->gravedad = $g; }
    public function setEstado(string $e): void      { $this->estado = $e; }
    public function setCuadrillaId(?int $c): void   { $this->cuadrillaId = $c; }

    /** Nombre legible del tipo, para mostrar en pantalla. */
    public function getTipoLegible(): string
    {
        return match ($this->tipo) {
            'rotura'            => 'Rotura',
            'desborde'          => 'Desborde',
            'falta_recoleccion' => 'Falta de recolección',
            default             => 'Otro',
        };
    }

    public function jsonSerialize(): array
    {
        return [
            'id'                 => $this->id,
            'contenedorId'       => $this->contenedorId,
            'tipo'               => $this->tipo,
            'tipoLegible'        => $this->getTipoLegible(),
            'descripcion'        => $this->descripcion,
            'gravedad'           => $this->gravedad,
            'estado'             => $this->estado,
            'cuadrillaId'        => $this->cuadrillaId,
            'reportadoPor'       => $this->reportadoPor,
            'reportadoPorNombre' => $this->reportadoPorNombre,
            'fechaReporte'       => $this->fechaReporte,
            'fechaResolucion'    => $this->fechaResolucion,
        ];
    }
}
