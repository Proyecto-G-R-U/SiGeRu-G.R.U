<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioCuadrillas: almacén de cuadrillas con persistencia en archivo JSON
 * (mismo patrón que RepositorioUsuarios). Vive en api-usuarios/data/cuadrillas.json
 */
class RepositorioCuadrillas
{
    private string $archivo;

    public function __construct()
    {
        $this->archivo = __DIR__ . '/../../data/cuadrillas.json';
        $this->inicializarSiHaceFalta();
    }

    private function inicializarSiHaceFalta(): void
    {
        if (file_exists($this->archivo)) {
            return;
        }
        $carpeta = dirname($this->archivo);
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        // Una cuadrilla de ejemplo (con el operario de recolección de prueba, id 2)
        $iniciales = [
            ['id' => 1, 'nombre' => 'Cuadrilla Norte', 'zona' => 'Zona Norte', 'operarios' => [2]],
        ];
        $this->guardarCrudo($iniciales);
    }

    private function leerCrudo(): array
    {
        $contenido = file_get_contents($this->archivo);
        $datos = json_decode($contenido, true);
        return is_array($datos) ? $datos : [];
    }

    private function guardarCrudo(array $filas): void
    {
        file_put_contents(
            $this->archivo,
            json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function aObjeto(array $f): Cuadrilla
    {
        return new Cuadrilla($f['id'], $f['nombre'], $f['zona'] ?? null, $f['operarios'] ?? []);
    }

    /** @return Cuadrilla[] */
    public function todos(): array
    {
        return array_map([$this, 'aObjeto'], $this->leerCrudo());
    }

    public function buscarPorId(int $id): ?Cuadrilla
    {
        foreach ($this->leerCrudo() as $f) {
            if ((int)$f['id'] === $id) {
                return $this->aObjeto($f);
            }
        }
        return null;
    }

    public function agregar(Cuadrilla $c): void
    {
        $filas = $this->leerCrudo();
        $filas[] = [
            'id'        => $c->getId(),
            'nombre'    => $c->getNombre(),
            'zona'      => $c->getZona(),
            'operarios' => $c->getOperarios(),
        ];
        $this->guardarCrudo($filas);
    }

    public function eliminar(int $id): bool
    {
        $filas = $this->leerCrudo();
        $original = count($filas);
        $filas = array_values(array_filter($filas, fn($f) => (int)$f['id'] !== $id));
        if (count($filas) === $original) {
            return false;
        }
        $this->guardarCrudo($filas);
        return true;
    }

    /**
     * Reemplaza la lista de operarios de una cuadrilla. Devuelve true si la
     * cuadrilla existía y se actualizó, false si no existe.
     * @param int[] $operarios ids de operarios
     */
    public function actualizarOperarios(int $id, array $operarios): bool
    {
        $filas = $this->leerCrudo();
        $encontrado = false;
        foreach ($filas as $i => $f) {
            if ((int)$f['id'] === $id) {
                $filas[$i]['operarios'] = array_values(array_map('intval', $operarios));
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            return false;
        }
        $this->guardarCrudo($filas);
        return true;
    }

    /**
     * Agrega un operario a una cuadrilla, garantizando EXCLUSIVIDAD: primero lo
     * quita de cualquier otra cuadrilla en la que estuviera, y luego lo agrega
     * a la indicada. Así un operario nunca está en dos cuadrillas a la vez.
     * Devuelve true si la cuadrilla destino existe.
     */
    public function agregarOperario(int $cuadrillaId, int $operarioId): bool
    {
        $filas = $this->leerCrudo();
        $existeDestino = false;

        foreach ($filas as $i => $f) {
            $operarios = array_map('intval', $f['operarios'] ?? []);
            // Lo sacamos de todas las cuadrillas (por si estaba en otra).
            $operarios = array_values(array_filter($operarios, fn($op) => $op !== $operarioId));
            // Si esta es la cuadrilla destino, lo agregamos.
            if ((int)$f['id'] === $cuadrillaId) {
                $operarios[] = $operarioId;
                $existeDestino = true;
            }
            $filas[$i]['operarios'] = array_values($operarios);
        }

        if (!$existeDestino) {
            return false;
        }
        $this->guardarCrudo($filas);
        return true;
    }

    /** Quita un operario de una cuadrilla. Devuelve true si la cuadrilla existe. */
    public function quitarOperario(int $cuadrillaId, int $operarioId): bool
    {
        $filas = $this->leerCrudo();
        $encontrado = false;
        foreach ($filas as $i => $f) {
            if ((int)$f['id'] === $cuadrillaId) {
                $operarios = array_map('intval', $f['operarios'] ?? []);
                $filas[$i]['operarios'] = array_values(array_filter($operarios, fn($op) => $op !== $operarioId));
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            return false;
        }
        $this->guardarCrudo($filas);
        return true;
    }

    /**
     * Devuelve los ids de operarios que YA están en alguna cuadrilla.
     * Sirve para calcular los "libres".
     * @return int[]
     */
    public function operariosOcupados(): array
    {
        $ocupados = [];
        foreach ($this->leerCrudo() as $f) {
            foreach ($f['operarios'] ?? [] as $op) {
                $ocupados[] = (int)$op;
            }
        }
        return array_values(array_unique($ocupados));
    }

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
