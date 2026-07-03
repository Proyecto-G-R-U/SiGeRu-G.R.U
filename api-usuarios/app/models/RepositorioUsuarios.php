<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioUsuarios: el "almacén" de usuarios, ahora CON PERSISTENCIA
 * EN ARCHIVO JSON.
 *
 * A diferencia de la versión en memoria (que perdía todo al terminar cada
 * petición), este repositorio guarda los usuarios en un archivo de texto
 * (data/usuarios.json). Así, cuando un vecino se auto-registra o el admin
 * crea un usuario, queda escrito en el disco y sobrevive entre peticiones:
 * en el próximo login ya está disponible.
 *
 * No es una base de datos formal (no hay MySQL ni SQL), es un archivo plano.
 * Cuando llegue la 2da entrega, se reemplaza SOLO este archivo por uno que
 * consulte MySQL; el resto del sistema (controlador, login, etc.) no cambia.
 */
class RepositorioUsuarios
{
    /** Ruta del archivo JSON donde se guardan los usuarios. */
    private string $archivo;

    public function __construct()
    {
        // El archivo vive en api-usuarios/data/usuarios.json
        $this->archivo = __DIR__ . '/../../data/usuarios.json';
        $this->inicializarSiHaceFalta();
    }

    /**
     * La primera vez (si el archivo no existe todavía), lo crea con los
     * usuarios de prueba. Las veces siguientes ya existe y no se toca.
     */
    private function inicializarSiHaceFalta(): void
    {
        if (file_exists($this->archivo)) {
            return;
        }

        // Nos aseguramos de que la carpeta data/ exista.
        $carpeta = dirname($this->archivo);
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        // Contraseña de todos los usuarios de prueba: 1234
        $hash = password_hash('1234', PASSWORD_DEFAULT);

        $iniciales = [
            ['id' => 1, 'nombre' => 'Carlos Rodríguez', 'email' => 'admin@sigeru.uy',         'passwordHash' => $hash, 'rol' => 'administrador', 'especialidad' => null,           'cuadrilla' => null],
            ['id' => 2, 'nombre' => 'Marta Pérez',      'email' => 'recoleccion@sigeru.uy',   'passwordHash' => $hash, 'rol' => 'operario',      'especialidad' => 'recoleccion',   'cuadrilla' => 'Cuadrilla Norte'],
            ['id' => 3, 'nombre' => 'Julián Fernández', 'email' => 'clasificacion@sigeru.uy', 'passwordHash' => $hash, 'rol' => 'operario',      'especialidad' => 'clasificacion', 'cuadrilla' => null],
            ['id' => 4, 'nombre' => 'Ana Silva',        'email' => 'vertedero@sigeru.uy',     'passwordHash' => $hash, 'rol' => 'operario',      'especialidad' => 'vertedero',     'cuadrilla' => null],
            ['id' => 5, 'nombre' => 'Vecino de Prueba', 'email' => 'vecino@gmail.com',        'passwordHash' => $hash, 'rol' => 'vecino',        'especialidad' => null,           'cuadrilla' => null],
        ];

        $this->guardarCrudo($iniciales);
    }

    // ============================================================
    //  Lectura / escritura del archivo JSON (nivel "crudo": arrays)
    // ============================================================

    /** Lee el archivo y devuelve un array de arrays (datos crudos). */
    private function leerCrudo(): array
    {
        $contenido = file_get_contents($this->archivo);
        $datos = json_decode($contenido, true);
        return is_array($datos) ? $datos : [];
    }

    /** Escribe el array de arrays al archivo JSON. */
    private function guardarCrudo(array $filas): void
    {
        file_put_contents(
            $this->archivo,
            json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Convierte una fila cruda (array del JSON) en el objeto Usuario correcto,
     * según su rol. Esto rearma la herencia al leer del archivo. Para los
     * operarios, además elige la subclase según la especialidad guardada.
     */
    private function aObjeto(array $f): Usuario
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

    /** Convierte un objeto Usuario en fila cruda (array) para guardar. */
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

    // ============================================================
    //  API pública del repositorio (lo que usa el controlador)
    // ============================================================

    /** Devuelve todos los usuarios como objetos. */
    public function todos(): array
    {
        return array_map([$this, 'aObjeto'], $this->leerCrudo());
    }

    /** Busca un usuario por email. Devuelve null si no existe. */
    public function buscarPorEmail(string $email): ?Usuario
    {
        foreach ($this->leerCrudo() as $f) {
            if ($f['email'] === $email) {
                return $this->aObjeto($f);
            }
        }
        return null;
    }

    /** Busca un usuario por id. Devuelve null si no existe. */
    public function buscarPorId(int $id): ?Usuario
    {
        foreach ($this->leerCrudo() as $f) {
            if ((int)$f['id'] === $id) {
                return $this->aObjeto($f);
            }
        }
        return null;
    }

    /** Agrega un usuario nuevo y persiste el archivo. */
    public function agregar(Usuario $usuario): void
    {
        $filas = $this->leerCrudo();
        $filas[] = $this->aFila($usuario);
        $this->guardarCrudo($filas);
    }

    /**
     * Elimina un usuario por id. Devuelve true si lo encontró y borró,
     * false si no existía. Persiste el archivo tras borrar.
     */
    public function eliminar(int $id): bool
    {
        $filas = $this->leerCrudo();
        $original = count($filas);
        // Nos quedamos con todos los que NO tienen ese id.
        $filas = array_values(array_filter($filas, fn($f) => (int)$f['id'] !== $id));
        if (count($filas) === $original) {
            return false; // no se borró nada (id inexistente)
        }
        $this->guardarCrudo($filas);
        return true;
    }

    /** Calcula el próximo id disponible (el mayor actual + 1). */
    public function proximoId(): int
    {
        $filas = $this->leerCrudo();
        $maxId = 0;
        foreach ($filas as $f) {
            if ((int)$f['id'] > $maxId) {
                $maxId = (int)$f['id'];
            }
        }
        return $maxId + 1;
    }
}
