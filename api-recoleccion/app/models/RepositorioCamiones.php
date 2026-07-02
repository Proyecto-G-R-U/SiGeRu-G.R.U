<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioCamiones: almacén EN MEMORIA de la flota.
 * Sin base de datos todavía (1ra entrega). En la 2da entrega pasa a MySQL.
 */
class RepositorioCamiones
{
    /** @var Camion[] */
    private array $camiones = [];
    private int $siguienteId = 1;

    public function __construct()
    {
        $this->cargarDatosDePrueba();
    }

    private function cargarDatosDePrueba(): void
    {
        $this->camiones[] = new Camion($this->siguienteId++, 'STP 1234', 'Volvo FE', 'operativo', 'en_ruta');
        $this->camiones[] = new Camion($this->siguienteId++, 'STP 5678', 'Mercedes Atego', 'operativo', 'disponible');
        $this->camiones[] = new Camion($this->siguienteId++, 'STP 9012', 'Iveco Tector', 'mantenimiento', 'disponible');
    }

    public function todos(): array
    {
        return $this->camiones;
    }

    public function agregar(Camion $c): void
    {
        $this->camiones[] = $c;
    }

    public function proximoId(): int
    {
        return $this->siguienteId++;
    }
}
