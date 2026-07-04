<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioCamiones: almacén de camiones con persistencia JSON
 * (mismo patrón que usuarios). Vive en api-recoleccion/data/camiones.json
 */
class RepositorioCamiones
{
    private string $archivo;

    public function __construct()
    {
        $this->archivo = __DIR__ . '/../../data/camiones.json';
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
        // Datos de prueba: 3 camiones repartidos en las 2 flotas iniciales.
        $iniciales = [
            ['id' => 1, 'patente' => 'STP 1234', 'modelo' => 'Volvo FE',       'estado' => 'operativo',     'disponibilidad' => 'en_ruta',    'flotaId' => 1, 'cuadrillaId' => 1],
            ['id' => 2, 'patente' => 'STP 5678', 'modelo' => 'Mercedes Atego', 'estado' => 'operativo',     'disponibilidad' => 'disponible', 'flotaId' => 1, 'cuadrillaId' => null],
            ['id' => 3, 'patente' => 'STP 9012', 'modelo' => 'Iveco Tector',   'estado' => 'mantenimiento', 'disponibilidad' => 'disponible', 'flotaId' => 2, 'cuadrillaId' => null],
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

    private function aObjeto(array $f): Camion
    {
        return new Camion(
            $f['id'],
            $f['patente'],
            $f['modelo'],
            $f['estado'],
            $f['disponibilidad'],
            isset($f['flotaId']) ? ($f['flotaId'] !== null ? (int)$f['flotaId'] : null) : null,
            isset($f['cuadrillaId']) ? ($f['cuadrillaId'] !== null ? (int)$f['cuadrillaId'] : null) : null
        );
    }

    private function aFila(Camion $c): array
    {
        return [
            'id'             => $c->getId(),
            'patente'        => $c->getPatente(),
            'modelo'         => $c->getModelo(),
            'estado'         => $c->getEstado(),
            'disponibilidad' => $c->getDisponibilidad(),
            'flotaId'        => $c->getFlotaId(),
            'cuadrillaId'    => $c->getCuadrillaId(),
        ];
    }

    /** @return Camion[] */
    public function todos(): array
    {
        return array_map([$this, 'aObjeto'], $this->leerCrudo());
    }

    public function buscarPorId(int $id): ?Camion
    {
        foreach ($this->leerCrudo() as $f) {
            if ((int)$f['id'] === $id) {
                return $this->aObjeto($f);
            }
        }
        return null;
    }

    public function agregar(Camion $c): void
    {
        $filas = $this->leerCrudo();
        $filas[] = $this->aFila($c);
        $this->guardarCrudo($filas);
    }

    /** Reemplaza un camión existente (por id) con su versión actualizada. */
    public function actualizar(Camion $c): bool
    {
        $filas = $this->leerCrudo();
        $encontrado = false;
        foreach ($filas as $i => $f) {
            if ((int)$f['id'] === $c->getId()) {
                $filas[$i] = $this->aFila($c);
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
     * Asigna una cuadrilla a un camión garantizando EXCLUSIVIDAD: quita esa
     * cuadrilla de cualquier OTRO camión que la tuviera, y la deja solo en el
     * camión indicado. Así una cuadrilla nunca está en dos camiones a la vez.
     * Si $cuadrillaId es null, simplemente deja el camión sin cuadrilla.
     * Devuelve true si el camión existe.
     */
    public function asignarCuadrillaExclusiva(int $camionId, ?int $cuadrillaId): bool
    {
        $filas = $this->leerCrudo();
        $existe = false;

        foreach ($filas as $i => $f) {
            // Si otro camión tenía esta cuadrilla, se la quitamos.
            if ($cuadrillaId !== null && (int)$f['id'] !== $camionId
                && isset($f['cuadrillaId']) && (int)$f['cuadrillaId'] === $cuadrillaId) {
                $filas[$i]['cuadrillaId'] = null;
            }
            // Al camión destino le ponemos la cuadrilla (o null).
            if ((int)$f['id'] === $camionId) {
                $filas[$i]['cuadrillaId'] = $cuadrillaId;
                $existe = true;
            }
        }

        if (!$existe) {
            return false;
        }
        $this->guardarCrudo($filas);
        return true;
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
