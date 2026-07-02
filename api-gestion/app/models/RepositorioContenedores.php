<?php
declare(strict_types=1);

namespace App\Models;

/**
 * RepositorioContenedores: almacén EN MEMORIA de contenedores.
 * Sin base de datos todavía (1ra entrega). En la 2da entrega pasa a MySQL.
 */
class RepositorioContenedores
{
    /** @var Contenedor[] */
    private array $contenedores = [];
    private int $siguienteId = 1;

    public function __construct()
    {
        $this->cargarDatosDePrueba();
    }

    private function cargarDatosDePrueba(): void
    {
        $this->contenedores[] = new Contenedor($this->siguienteId++, 'CNT-102', 'Av. Italia y Propios', 'reciclable', 'operativo');
        $this->contenedores[] = new Contenedor($this->siguienteId++, 'CNT-403', 'Buceo, Rambla', 'mezclado', 'desbordado');
        $this->contenedores[] = new Contenedor($this->siguienteId++, 'CNT-210', 'Pocitos, 26 de Marzo', 'reciclable', 'operativo');
    }

    public function todos(): array
    {
        return $this->contenedores;
    }

    public function agregar(Contenedor $c): void
    {
        $this->contenedores[] = $c;
    }

    public function proximoId(): int
    {
        return $this->siguienteId++;
    }
}
