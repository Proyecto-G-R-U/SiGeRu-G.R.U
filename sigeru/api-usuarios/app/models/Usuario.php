<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Clase base abstracta Usuario.
 *
 * Es "abstracta" porque un Usuario a secas no existe en el sistema:
 * siempre va a ser un Administrador, un Operario o un Vecino. Sirve
 * para reunir en un solo lugar todo lo que esos tres tipos COMPARTEN
 * (id, nombre, email, contraseña) y evitar repetir ese código.
 *
 * Requerimientos que apoya: 3.1.1.1.1 (ABM de usuarios internos),
 * 3.1.1.1.2 (roles), 3.1.3.1 (auto-registro de vecinos).
 */
abstract class Usuario implements \JsonSerializable
{
    // Propiedades "protected": las ven esta clase y sus hijas,
    // pero no el código de afuera. Eso es ENCAPSULAMIENTO.
    protected int $id;
    protected string $nombre;
    protected string $email;
    protected string $passwordHash;

    public function __construct(int $id, string $nombre, string $email, string $passwordHash)
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
    }

    /**
     * Método ABSTRACTO: la clase base obliga a cada hija a definir
     * su propio rol, pero no dice cuál. Cada subclase lo completa.
     * Esto es POLIMORFISMO: mismo método, distinta respuesta según el tipo.
     */
    abstract public function getRol(): string;

    // ---- Getters (lectura controlada de las propiedades) ----
    public function getId(): int          { return $this->id; }
    public function getNombre(): string   { return $this->nombre; }
    public function getEmail(): string    { return $this->email; }

    // ---- Setters (modificación controlada, para el ABM) ----
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setEmail(string $email): void   { $this->email = $email; }

    /**
     * Verifica una contraseña en texto plano contra el hash guardado.
     * Nunca guardamos la contraseña real: guardamos su hash (password_hash)
     * y comparamos con password_verify. Esto es seguridad básica (requisito
     * de la letra, pág. 12: "seguridad informática a nivel de software").
     */
    public function verificarPassword(string $passwordPlano): bool
    {
        return password_verify($passwordPlano, $this->passwordHash);
    }

    /**
     * Devuelve el hash de la contraseña. Lo usa SOLO el repositorio para
     * guardar el usuario en el archivo. Ojo: no se incluye en jsonSerialize(),
     * así que nunca sale en la respuesta JSON al frontend.
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * Define cómo se convierte este objeto a JSON cuando la API responde.
     * Nunca incluimos el passwordHash en la respuesta, por seguridad.
     */
    public function jsonSerialize(): array
    {
        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
            'email'  => $this->email,
            'rol'    => $this->getRol(),
        ];
    }
}
