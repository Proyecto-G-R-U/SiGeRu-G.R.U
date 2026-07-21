<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioUsuarios: persistencia de usuarios en MySQL.
 *
 * HEREDA de RepositorioSql el motor común (conexión PDO, todos(),
 * buscarPorId(), eliminar(), proximoId()). Acá solo lo específico:
 * la tabla, cómo se rearma cada subclase (herencia de Usuario), la
 * siembra de datos de prueba y los métodos propios.
 */
class RepositorioUsuarios extends RepositorioSql
{
    public function __construct()
    {
        parent::__construct();
        $this->sembrarSiVacio();
    }

    protected function tabla(): string
    {
        return 'usuario';
    }

    /**
     * Si la tabla está vacía, crea los usuarios de prueba. Se hace acá y no
     * en base.sql porque el hash de '1234' debe generarlo password_hash()
     * de PHP (un hash escrito a mano en SQL no validaría en el login).
     * Contraseña de todos: 1234
     */
    private function sembrarSiVacio(): void
    {
        $f = $this->fila('SELECT COUNT(*) AS cant FROM usuario');
        if ((int)$f['cant'] > 0) {
            return;
        }
        $hash = password_hash('1234', PASSWORD_DEFAULT);
        $sql = 'INSERT INTO usuario (id, nombre, email, password_hash, rol, especialidad, cuadrilla_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $this->consulta($sql, [1, 'Carlos Rodríguez', 'admin@sigeru.uy',         $hash, 'administrador', null,            null]);
        $this->consulta($sql, [2, 'Marta Pérez',      'recoleccion@sigeru.uy',   $hash, 'operario',      'recoleccion',   1]);
        $this->consulta($sql, [3, 'Julián Fernández', 'clasificacion@sigeru.uy', $hash, 'operario',      'clasificacion', null]);
        $this->consulta($sql, [4, 'Ana Silva',        'vertedero@sigeru.uy',     $hash, 'operario',      'vertedero',     null]);
        $this->consulta($sql, [5, 'Vecino de Prueba', 'vecino@gmail.com',        $hash, 'vecino',        null,            null]);
    }

    /**
     * Rearma el objeto con su subclase correcta según rol y, para operarios,
     * según su especialidad (la herencia se reconstruye al leer de la BD).
     */
    protected function aObjeto(array $f): object
    {
        $id    = (int)$f['id'];
        $hash  = $f['password_hash'];
        if ($f['rol'] === 'operario') {
            // El objeto guarda la cuadrilla como texto informativo (id o null).
            $cuadrilla = $f['cuadrilla_id'] !== null ? (string)$f['cuadrilla_id'] : null;
            return match ($f['especialidad'] ?? 'recoleccion') {
                'clasificacion' => new OperarioClasificacion($id, $f['nombre'], $f['email'], $hash, $cuadrilla),
                'vertedero'     => new OperarioVertedero($id, $f['nombre'], $f['email'], $hash, $cuadrilla),
                default         => new OperarioRecoleccion($id, $f['nombre'], $f['email'], $hash, $cuadrilla),
            };
        }
        return match ($f['rol']) {
            'administrador' => new Administrador($id, $f['nombre'], $f['email'], $hash),
            default         => new Vecino($id, $f['nombre'], $f['email'], $hash),
        };
    }

    // ---------------- Métodos específicos de usuarios ----------------

    public function buscarPorEmail(string $email): ?Usuario
    {
        $f = $this->fila('SELECT * FROM usuario WHERE email = ?', [$email]);
        /** @var ?Usuario */
        return $f === null ? null : $this->aObjeto($f);
    }

    public function agregar(Usuario $u): void
    {
        // Un usuario nuevo nunca nace asignado a una cuadrilla: eso se
        // gestiona después desde el apartado Cuadrillas.
        $esp = $u instanceof Operario ? $u->getEspecialidad() : null;
        $this->consulta(
            'INSERT INTO usuario (id, nombre, email, password_hash, rol, especialidad, cuadrilla_id)
             VALUES (?, ?, ?, ?, ?, ?, NULL)',
            [$u->getId(), $u->getNombre(), $u->getEmail(), $u->getPasswordHash(), $u->getRol(), $esp]
        );
    }

    /**
     * Actualiza un usuario. Conserva la contraseña si no viene hash nuevo, y
     * conserva la cuadrilla asignada SALVO que el usuario deje de ser operario
     * de recolección (en ese caso se lo desvincula, porque las cuadrillas son
     * de recolección). Devuelve true si existía.
     */
    public function actualizar(Usuario $u, ?string $hashNuevo = null): bool
    {
        $esp = $u instanceof Operario ? $u->getEspecialidad() : null;

        if ($hashNuevo !== null && $hashNuevo !== '') {
            $stmt = $this->consulta(
                'UPDATE usuario SET nombre = ?, email = ?, password_hash = ?, rol = ?, especialidad = ? WHERE id = ?',
                [$u->getNombre(), $u->getEmail(), $hashNuevo, $u->getRol(), $esp, $u->getId()]
            );
        } else {
            $stmt = $this->consulta(
                'UPDATE usuario SET nombre = ?, email = ?, rol = ?, especialidad = ? WHERE id = ?',
                [$u->getNombre(), $u->getEmail(), $u->getRol(), $esp, $u->getId()]
            );
        }

        // Si ya no es operario de recolección, no puede seguir en una cuadrilla.
        if ($esp !== 'recoleccion') {
            $this->consulta('UPDATE usuario SET cuadrilla_id = NULL WHERE id = ?', [$u->getId()]);
        }

        // rowCount puede ser 0 si no cambió nada; verificamos existencia real.
        return $this->fila('SELECT id FROM usuario WHERE id = ?', [$u->getId()]) !== null;
    }
}
