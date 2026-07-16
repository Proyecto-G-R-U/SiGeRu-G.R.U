<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioUsuarios: persistencia de usuarios en archivo JSON.
 *
 * HEREDA de RepositorioJson todo el motor común (leer/escribir el archivo,
 * todos(), buscarPorId(), eliminar(), proximoId()). Acá solo queda lo
 * específico de usuarios: el archivo, los datos de prueba, cómo se arma
 * cada subclase (herencia de Usuario), y los métodos propios.
 */
class RepositorioUsuarios extends RepositorioJson
{
    protected function nombreArchivo(): string
    {
        return 'usuarios.json';
    }

    protected function datosIniciales(): array
    {
        // Contraseña de todos los usuarios de prueba: 1234
        $hash = password_hash('1234', PASSWORD_DEFAULT);
        return [
            ['id' => 1, 'nombre' => 'Carlos Rodríguez', 'email' => 'admin@sigeru.uy',         'passwordHash' => $hash, 'rol' => 'administrador', 'especialidad' => null,            'cuadrilla' => null],
            ['id' => 2, 'nombre' => 'Marta Pérez',      'email' => 'recoleccion@sigeru.uy',   'passwordHash' => $hash, 'rol' => 'operario',      'especialidad' => 'recoleccion',   'cuadrilla' => 'Cuadrilla Norte'],
            ['id' => 3, 'nombre' => 'Julián Fernández', 'email' => 'clasificacion@sigeru.uy', 'passwordHash' => $hash, 'rol' => 'operario',      'especialidad' => 'clasificacion', 'cuadrilla' => null],
            ['id' => 4, 'nombre' => 'Ana Silva',        'email' => 'vertedero@sigeru.uy',     'passwordHash' => $hash, 'rol' => 'operario',      'especialidad' => 'vertedero',     'cuadrilla' => null],
            ['id' => 5, 'nombre' => 'Vecino de Prueba', 'email' => 'vecino@gmail.com',        'passwordHash' => $hash, 'rol' => 'vecino',        'especialidad' => null,            'cuadrilla' => null],
        ];
    }

    /**
     * Rearma el objeto con su subclase correcta según rol y, para operarios,
     * según su especialidad (acá se reconstruye la herencia al leer).
     */
    protected function aObjeto(array $f): object
    {
        if ($f['rol'] === 'operario') {
            $cuadrilla = $f['cuadrilla'] ?? null;
            return match ($f['especialidad'] ?? 'recoleccion') {
                'clasificacion' => new OperarioClasificacion($f['id'], $f['nombre'], $f['email'], $f['passwordHash'], $cuadrilla),
                'vertedero'     => new OperarioVertedero($f['id'], $f['nombre'], $f['email'], $f['passwordHash'], $cuadrilla),
                default         => new OperarioRecoleccion($f['id'], $f['nombre'], $f['email'], $f['passwordHash'], $cuadrilla),
            };
        }
        return match ($f['rol']) {
            'administrador' => new Administrador($f['id'], $f['nombre'], $f['email'], $f['passwordHash']),
            default         => new Vecino($f['id'], $f['nombre'], $f['email'], $f['passwordHash']),
        };
    }

    /** Convierte un Usuario en fila cruda para guardar. */
    private function aFila(Usuario $u): array
    {
        $fila = [
            'id'           => $u->getId(),
            'nombre'       => $u->getNombre(),
            'email'        => $u->getEmail(),
            'passwordHash' => $u->getPasswordHash(),
            'rol'          => $u->getRol(),
            'especialidad' => null,
            'cuadrilla'    => null,
        ];
        if ($u instanceof Operario) {
            $fila['especialidad'] = $u->getEspecialidad();
            $fila['cuadrilla']    = $u->getCuadrilla();
        }
        return $fila;
    }

    // ---------------- Métodos específicos de usuarios ----------------

    public function buscarPorEmail(string $email): ?Usuario
    {
        foreach ($this->leerCrudo() as $f) {
            if ($f['email'] === $email) {
                /** @var Usuario */
                return $this->aObjeto($f);
            }
        }
        return null;
    }

    public function agregar(Usuario $usuario): void
    {
        $filas = $this->leerCrudo();
        $filas[] = $this->aFila($usuario);
        $this->guardarCrudo($filas);
    }

    /**
     * Actualiza un usuario. Si no viene hash nuevo, conserva la contraseña
     * que ya tenía (para editar sin cambiarla). Devuelve true si existía.
     */
    public function actualizar(Usuario $usuario, ?string $hashNuevo = null): bool
    {
        $filas = $this->leerCrudo();
        foreach ($filas as $i => $f) {
            if ((int)$f['id'] === $usuario->getId()) {
                $nueva = $this->aFila($usuario);
                if ($hashNuevo === null || $hashNuevo === '') {
                    $nueva['passwordHash'] = $f['passwordHash'];
                }
                $filas[$i] = $nueva;
                $this->guardarCrudo($filas);
                return true;
            }
        }
        return false;
    }
}
