<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioFlotas: almacén de flotas con persistencia JSON.
 * Vive en api-recoleccion/data/flotas.json
 */
class RepositorioFlotas
{
    private string $archivo;

    public function __construct()
    {
        $this->archivo = __DIR__ . '/../../data/flotas.json';
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
        $iniciales = [
            ['id' => 1, 'nombre' => 'Flota Norte', 'zona' => 'Zona Norte'],
            ['id' => 2, 'nombre' => 'Flota Sur',   'zona' => 'Zona Sur'],
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

    private function aObjeto(array $f): Flota
    {
        return new Flota($f['id'], $f['nombre'], $f['zona'] ?? null);
    }

    /** @return Flota[] */
    public function todos(): array
    {
        return array_map([$this, 'aObjeto'], $this->leerCrudo());
    }

    public function buscarPorId(int $id): ?Flota
    {
        foreach ($this->leerCrudo() as $f) {
            if ((int)$f['id'] === $id) {
                return $this->aObjeto($f);
            }
        }
        return null;
    }

    public function agregar(Flota $flota): void
    {
        $filas = $this->leerCrudo();
        $filas[] = [
            'id'     => $flota->getId(),
            'nombre' => $flota->getNombre(),
            'zona'   => $flota->getZona(),
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
