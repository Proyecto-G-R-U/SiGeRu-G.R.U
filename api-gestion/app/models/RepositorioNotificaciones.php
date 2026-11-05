<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioNotificaciones: persistencia de los avisos del sistema.
 *
 * Lee la tabla usuario para saber a quién mandarle cada aviso (quiénes
 * integran una cuadrilla, quiénes son administradores). Es el mismo
 * criterio que ya seguía RepositorioCuadrillas: las APIs no se llaman
 * entre ellas, leen la base compartida.
 */
class RepositorioNotificaciones extends RepositorioSql
{
    /** Tope de notificaciones que se devuelven de una vez. */
    public const LIMITE = 30;

    protected function tabla(): string
    {
        return 'notificacion';
    }

    protected function aObjeto(array $f): object
    {
        return new Notificacion(
            (int)$f['id'],
            (int)$f['usuario_id'],
            $f['tipo'],
            $f['titulo'],
            $f['mensaje'],
            $f['gravedad'] ?? 'media',
            $f['incidencia_id'] !== null ? (int)$f['incidencia_id'] : null,
            (bool)$f['leida'],
            (string)$f['fecha']
        );
    }

    // ---------------- Lectura ----------------

    /**
     * Las notificaciones de UN usuario, las más nuevas primero y las no
     * leídas arriba.
     *
     * Siempre filtra por usuario_id: una notificación es de quien la
     * recibe, y nadie tiene por qué ver las de otro.
     *
     * @return Notificacion[]
     */
    public function paraUsuario(int $usuarioId, bool $soloNoLeidas = false): array
    {
        $where = 'WHERE usuario_id = ?';
        $params = [$usuarioId];
        if ($soloNoLeidas) {
            $where .= ' AND leida = 0';
        }
        return array_map(
            [$this, 'aObjeto'],
            $this->filas(
                "SELECT * FROM notificacion $where
                 ORDER BY leida ASC, fecha DESC, id DESC
                 LIMIT " . self::LIMITE,
                $params
            )
        );
    }

    public function contarNoLeidas(int $usuarioId): int
    {
        $f = $this->fila(
            'SELECT COUNT(*) AS n FROM notificacion WHERE usuario_id = ? AND leida = 0',
            [$usuarioId]
        );
        return (int)($f['n'] ?? 0);
    }

    // ---------------- Escritura ----------------

    /** Crea un aviso para un usuario. Devuelve el id generado. */
    public function crear(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensaje,
        string $gravedad = 'media',
        ?int $incidenciaId = null
    ): int {
        $this->consulta(
            'INSERT INTO notificacion (usuario_id, tipo, titulo, mensaje, gravedad, incidencia_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$usuarioId, $tipo, $titulo, mb_substr($mensaje, 0, 400), $gravedad, $incidenciaId]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * El mismo aviso para varios usuarios de una vez.
     *
     * @param int[] $usuarios
     * @return int cuántos avisos se crearon
     */
    public function crearParaVarios(
        array $usuarios,
        string $tipo,
        string $titulo,
        string $mensaje,
        string $gravedad = 'media',
        ?int $incidenciaId = null
    ): int {
        $n = 0;
        foreach (array_unique($usuarios) as $uid) {
            $this->crear((int)$uid, $tipo, $titulo, $mensaje, $gravedad, $incidenciaId);
            $n++;
        }
        return $n;
    }

    /**
     * Avisa a todos los operarios de una cuadrilla.
     * Si la cuadrilla está vacía no crea nada y devuelve 0, que es
     * distinto de fallar: una cuadrilla sin integrantes es un caso válido.
     */
    public function avisarACuadrilla(
        int $cuadrillaId,
        string $tipo,
        string $titulo,
        string $mensaje,
        string $gravedad = 'media',
        ?int $incidenciaId = null
    ): int {
        $filas = $this->filas(
            'SELECT id FROM usuario WHERE cuadrilla_id = ? AND rol = ?',
            [$cuadrillaId, 'operario']
        );
        return $this->crearParaVarios(
            array_column($filas, 'id'), $tipo, $titulo, $mensaje, $gravedad, $incidenciaId
        );
    }

    /**
     * Avisa a todos los administradores.
     *
     * @param int|null $excepto id de quien originó el evento: no tiene
     *                          sentido notificarle algo que acaba de hacer
     */
    public function avisarAAdmins(
        string $tipo,
        string $titulo,
        string $mensaje,
        string $gravedad = 'media',
        ?int $incidenciaId = null,
        ?int $excepto = null
    ): int {
        $filas = $this->filas('SELECT id FROM usuario WHERE rol = ?', ['administrador']);
        $ids = array_column($filas, 'id');
        if ($excepto !== null) {
            $ids = array_values(array_filter($ids, static fn($i): bool => (int)$i !== $excepto));
        }
        return $this->crearParaVarios($ids, $tipo, $titulo, $mensaje, $gravedad, $incidenciaId);
    }

    /**
     * Marca una notificación como leída.
     *
     * Pide el usuario además del id y lo mete en el WHERE: así nadie
     * puede marcar como leída una notificación ajena mandando un id
     * cualquiera. Devuelve false si no existe o si no es suya.
     */
    public function marcarLeida(int $id, int $usuarioId): bool
    {
        $existe = $this->fila(
            'SELECT id FROM notificacion WHERE id = ? AND usuario_id = ?',
            [$id, $usuarioId]
        );
        if ($existe === null) {
            return false;
        }
        $this->consulta(
            'UPDATE notificacion SET leida = 1 WHERE id = ? AND usuario_id = ?',
            [$id, $usuarioId]
        );
        return true;
    }

    /** Marca todas las del usuario. Devuelve cuántas cambiaron. */
    public function marcarTodasLeidas(int $usuarioId): int
    {
        $pendientes = $this->contarNoLeidas($usuarioId);
        if ($pendientes > 0) {
            $this->consulta(
                'UPDATE notificacion SET leida = 1 WHERE usuario_id = ? AND leida = 0',
                [$usuarioId]
            );
        }
        return $pendientes;
    }
}
