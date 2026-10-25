<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Ruta de recolección: recorrido que la administración arma y asigna a una
 * cuadrilla (requerimiento 3.1.2.1.1 y "rutas de recolección" de la letra).
 *
 * Una ruta tiene día y turno, una cuadrilla responsable, la lista ordenada
 * de contenedores que recorre y la instalación donde el camión descarga al
 * terminar (destino final). La lista de paradas vive en la tabla intermedia
 * ruta_contenedor; acá se expone como un array de ids en orden.
 */
class Ruta implements \JsonSerializable
{
    private int $id;
    private string $nombre;
    private ?string $zona;
    private string $diaSemana;
    private string $turno;
    private ?int $cuadrillaId;
    private ?int $instalacionId;   // destino final donde descarga
    /** @var int[] ids de contenedores, en el orden del recorrido */
    private array $contenedores;

    public function __construct(
        int $id,
        string $nombre,
        ?string $zona = null,
        string $diaSemana = 'lunes',
        string $turno = 'matutino',
        ?int $cuadrillaId = null,
        array $contenedores = [],
        ?int $instalacionId = null
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->zona = $zona;
        $this->diaSemana = $diaSemana;
        $this->turno = $turno;
        $this->cuadrillaId = $cuadrillaId;
        $this->contenedores = $contenedores;
        $this->instalacionId = $instalacionId;
    }

    public function getId(): int             { return $this->id; }
    public function getNombre(): string      { return $this->nombre; }
    public function getZona(): ?string       { return $this->zona; }
    public function getDiaSemana(): string   { return $this->diaSemana; }
    public function getTurno(): string       { return $this->turno; }
    public function getCuadrillaId(): ?int   { return $this->cuadrillaId; }
    public function getContenedores(): array { return $this->contenedores; }
    public function getInstalacionId(): ?int { return $this->instalacionId; }

    public function setNombre(string $n): void       { $this->nombre = $n; }
    public function setZona(?string $z): void        { $this->zona = $z; }
    public function setDiaSemana(string $d): void    { $this->diaSemana = $d; }
    public function setTurno(string $t): void        { $this->turno = $t; }
    public function setCuadrillaId(?int $c): void    { $this->cuadrillaId = $c; }
    public function setContenedores(array $c): void  { $this->contenedores = $c; }
    public function setInstalacionId(?int $i): void  { $this->instalacionId = $i; }

    /** Nombre legible del día, para mostrar en pantalla. */
    public function getDiaLegible(): string
    {
        return match ($this->diaSemana) {
            'martes'    => 'Martes',
            'miercoles' => 'Miércoles',
            'jueves'    => 'Jueves',
            'viernes'   => 'Viernes',
            'sabado'    => 'Sábado',
            'domingo'   => 'Domingo',
            default     => 'Lunes',
        };
    }

    public function jsonSerialize(): array
    {
        return [
            'id'           => $this->id,
            'nombre'       => $this->nombre,
            'zona'         => $this->zona,
            'diaSemana'    => $this->diaSemana,
            'diaLegible'   => $this->getDiaLegible(),
            'turno'        => $this->turno,
            'cuadrillaId'  => $this->cuadrillaId,
            'contenedores' => $this->contenedores,
            'instalacionId' => $this->instalacionId,
        ];
    }
}
