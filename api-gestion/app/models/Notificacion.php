<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Notificación: un aviso dentro del sistema para UN usuario concreto.
 *
 * Cubre el punto "sistema de notificaciones o alertas" de la letra de la
 * tercera entrega. No hay mail ni push: el usuario las ve en la campana
 * del encabezado. Esa decisión es a propósito — el correo depende de un
 * servidor SMTP que no se puede garantizar en una defensa, y el push
 * necesita HTTPS y permisos del navegador.
 *
 * Hay una fila por destinatario, no por evento. Si a una cuadrilla de tres
 * se le asigna una incidencia se crean tres notificaciones, y cada operario
 * marca la suya como leída sin afectar a los demás.
 */
class Notificacion implements \JsonSerializable
{
    public const TIPOS = [
        'incidencia_nueva',
        'incidencia_asignada',
        'incidencia_resuelta',
        'aviso',
    ];

    private int $id;
    private int $usuarioId;
    private string $tipo;
    private string $titulo;
    private string $mensaje;
    private string $gravedad;       // 'baja' | 'media' | 'alta'
    private ?int $incidenciaId;
    private bool $leida;
    private string $fecha;

    public function __construct(
        int $id,
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensaje,
        string $gravedad = 'media',
        ?int $incidenciaId = null,
        bool $leida = false,
        string $fecha = ''
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->tipo = in_array($tipo, self::TIPOS, true) ? $tipo : 'aviso';
        $this->titulo = $titulo;
        $this->mensaje = $mensaje;
        $this->gravedad = $gravedad;
        $this->incidenciaId = $incidenciaId;
        $this->leida = $leida;
        $this->fecha = $fecha;
    }

    public function getId(): int            { return $this->id; }
    public function getUsuarioId(): int     { return $this->usuarioId; }
    public function getTipo(): string       { return $this->tipo; }
    public function getTitulo(): string     { return $this->titulo; }
    public function getMensaje(): string    { return $this->mensaje; }
    public function getGravedad(): string   { return $this->gravedad; }
    public function getIncidenciaId(): ?int { return $this->incidenciaId; }
    public function estaLeida(): bool       { return $this->leida; }
    public function getFecha(): string      { return $this->fecha; }

    public function marcarLeida(): void     { $this->leida = true; }

    /** Icono de Material Icons que le corresponde, según el tipo. */
    public function getIcono(): string
    {
        switch ($this->tipo) {
            case 'incidencia_nueva':    return 'report_problem';
            case 'incidencia_asignada': return 'assignment_ind';
            case 'incidencia_resuelta': return 'task_alt';
            default:                    return 'notifications';
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id'           => $this->id,
            'usuarioId'    => $this->usuarioId,
            'tipo'         => $this->tipo,
            'titulo'       => $this->titulo,
            'mensaje'      => $this->mensaje,
            'gravedad'     => $this->gravedad,
            'incidenciaId' => $this->incidenciaId,
            'leida'        => $this->leida,
            'fecha'        => $this->fecha,
            'icono'        => $this->getIcono(),
        ];
    }
}
