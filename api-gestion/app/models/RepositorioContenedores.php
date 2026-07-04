<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioContenedores: almacén de contenedores con persistencia JSON
 * (mismo patrón que usuarios y camiones). Vive en api-gestion/data/contenedores.json
 */
class RepositorioContenedores
{
    private string $archivo;

    public function __construct()
    {
        $this->archivo = __DIR__ . '/../../data/contenedores.json';
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
        // Datos de prueba con coordenadas reales de Montevideo.
        $iniciales = [
            ['id' => 1, 'codigo' => 'CNT-102', 'direccion' => 'Av. Italia y Bulevar Artigas', 'tipoResiduo' => 'reciclable', 'estado' => 'operativo',  'lat' => -34.9020, 'lng' => -56.1550],
            ['id' => 2, 'codigo' => 'CNT-403', 'direccion' => 'Rambla y Buceo',               'tipoResiduo' => 'mezclado',   'estado' => 'desbordado', 'lat' => -34.9110, 'lng' => -56.1360],
            ['id' => 3, 'codigo' => 'CNT-210', 'direccion' => 'Pocitos, 26 de Marzo',         'tipoResiduo' => 'reciclable', 'estado' => 'operativo',  'lat' => -34.9095, 'lng' => -56.1520],
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

    private function aObjeto(array $f): Contenedor
    {
        return new Contenedor(
            $f['id'],
            $f['codigo'],
            $f['direccion'] ?? ($f['ubicacion'] ?? ''),
            $f['tipoResiduo'],
            $f['estado'],
            isset($f['lat']) && $f['lat'] !== null ? (float)$f['lat'] : null,
            isset($f['lng']) && $f['lng'] !== null ? (float)$f['lng'] : null
        );
    }

    private function aFila(Contenedor $c): array
    {
        return [
            'id'          => $c->getId(),
            'codigo'      => $c->getCodigo(),
            'direccion'   => $c->getDireccion(),
            'tipoResiduo' => $c->getTipoResiduo(),
            'estado'      => $c->getEstado(),
            'lat'         => $c->getLat(),
            'lng'         => $c->getLng(),
        ];
    }

    /** @return Contenedor[] */
    public function todos(): array
    {
        return array_map([$this, 'aObjeto'], $this->leerCrudo());
    }

    public function buscarPorId(int $id): ?Contenedor
    {
        foreach ($this->leerCrudo() as $f) {
            if ((int)$f['id'] === $id) {
                return $this->aObjeto($f);
            }
        }
        return null;
    }

    public function agregar(Contenedor $c): void
    {
        $filas = $this->leerCrudo();
        $filas[] = $this->aFila($c);
        $this->guardarCrudo($filas);
    }

    /** Actualiza un contenedor existente (por id). Devuelve true si existía. */
    public function actualizar(Contenedor $c): bool
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
