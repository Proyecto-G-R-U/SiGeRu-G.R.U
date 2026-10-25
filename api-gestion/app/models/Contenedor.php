<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Contenedor de residuos.
 *
 * Requerimientos: 3.1.1.2.1 (ABM), 3.1.1.2.2 (ubicación geográfica exacta),
 * 3.1.1.2.3 (tipo de residuo y estado).
 *
 * La ubicación se guarda de tres formas complementarias:
 *   - direccion: texto legible (ej. "Av. Italia y Propios")
 *   - lat / lng: coordenadas exactas para ubicarlo en el mapa
 *   - zona: el municipio al que pertenece, para poder AGRUPAR
 *
 * La zona no se deduce de lat/lng ni de direccion: con coordenadas sueltas
 * no se puede hacer GROUP BY, y la dirección es texto libre (dos personas
 * escriben la misma esquina distinto). Por eso es un campo propio, y por eso
 * vive en el contenedor y no en la incidencia: la incidencia ya apunta a un
 * contenedor, así que la zona sale con un JOIN y no se puede desincronizar.
 * La usan 3.1.1.3.4 (historial por zona), 3.1.4.1 (listado filtrado por zona)
 * y 3.1.4.3 (zonas críticas).
 *
 * Es NULL cuando el contenedor todavía no tiene zona asignada.
 */
class Contenedor implements \JsonSerializable
{
    /**
     * Zonas válidas: los municipios de Montevideo, que es como la
     * Intendencia organiza el servicio de limpieza.
     *
     * Está acá y en un solo lugar a propósito: para adaptar el sistema a
     * otra ciudad (requerimiento 1.1.2, flexibilidad en entornos urbanos)
     * alcanza con cambiar esta lista y la constante ZONAS del frontend.
     * No hace falta tocar la base: la columna es un VARCHAR.
     */
    public const ZONAS = [
        'Municipio A',
        'Municipio B',
        'Municipio C',
        'Municipio CH',
        'Municipio D',
        'Municipio E',
        'Municipio F',
        'Municipio G',
    ];

    /** true si $zona es null (sin asignar) o uno de los municipios válidos. */
    public static function zonaValida(?string $zona): bool
    {
        return $zona === null || in_array($zona, self::ZONAS, true);
    }

    private int $id;
    private string $codigo;
    private string $direccion;
    private string $tipoResiduo; // 'mezclado' | 'reciclable'
    private string $estado;       // INTEGRIDAD física: 'operativo' | 'roto'
    private string $nivelLlenado; // CUÁNTO TIENE: 'vacio' | 'lleno' | 'desbordado'
    private ?float $lat;
    private ?float $lng;
    private ?string $zona;        // municipio: 'Municipio CH', 'Municipio E'... o null

    public function __construct(
        int $id,
        string $codigo,
        string $direccion,
        string $tipoResiduo,
        string $estado,
        ?float $lat = null,
        ?float $lng = null,
        string $nivelLlenado = 'vacio',
        ?string $zona = null
    ) {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->direccion = $direccion;
        $this->tipoResiduo = $tipoResiduo;
        $this->estado = $estado;
        $this->nivelLlenado = $nivelLlenado;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->zona = $zona;
    }

    public function getId(): int              { return $this->id; }
    public function getCodigo(): string       { return $this->codigo; }
    public function getDireccion(): string    { return $this->direccion; }
    public function getTipoResiduo(): string  { return $this->tipoResiduo; }
    public function getEstado(): string       { return $this->estado; }
    public function getNivelLlenado(): string { return $this->nivelLlenado; }
    public function getLat(): ?float          { return $this->lat; }
    public function getLng(): ?float          { return $this->lng; }
    public function getZona(): ?string        { return $this->zona; }

    public function setCodigo(string $c): void      { $this->codigo = $c; }
    public function setDireccion(string $d): void   { $this->direccion = $d; }
    public function setTipoResiduo(string $t): void { $this->tipoResiduo = $t; }
    public function setEstado(string $e): void      { $this->estado = $e; }
    public function setNivelLlenado(string $n): void { $this->nivelLlenado = $n; }
    public function setLat(?float $l): void         { $this->lat = $l; }
    public function setLng(?float $l): void         { $this->lng = $l; }
    public function setZona(?string $z): void       { $this->zona = $z; }

    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'codigo'      => $this->codigo,
            'direccion'   => $this->direccion,
            'zona'        => $this->zona,
            'tipoResiduo' => $this->tipoResiduo,
            'estado'       => $this->estado,
            'nivelLlenado' => $this->nivelLlenado,
            'lat'         => $this->lat,
            'lng'         => $this->lng,
        ];
    }
}
