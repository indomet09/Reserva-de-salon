<?php
/**
 * User Model - Modelo de Usuarios
 * 
 * Maneja todas las operaciones de base de datos relacionadas
 * con usuarios. Usa la base de datos usuarios.db.
 * 
 * @author INDOMET
 * @version 2.1.0
 * @license MIT
 */

require_once __DIR__ . '/../../config/database.php';

class User
{
    // Conexión a la base de datos de usuarios
    private PDO $db;

    public function __construct()
    {
        // Usamos la base de datos específica de usuarios
        $this->db = getUsersConnection();
    }

    /**
     * Busca un usuario por su email
     * 
     * Útil para verificar si un email ya está registrado
     * o para obtener datos del usuario al iniciar sesión.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Busca un usuario por su ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Crea un nuevo usuario
     * 
     * La contraseña se hashea con bcrypt antes de guardar.
     * Retorna el ID del usuario creado o null si falla.
     */
    public function create(string $email, string $password, string $role = ROLE_USER): ?int
    {
        // Hasheamos la contraseña para guardarla de forma segura
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)"
        );

        try {
            $stmt->execute([$email, $passwordHash, $role]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            // Si el email ya existe, falla por la constraint UNIQUE
            return null;
        }
    }

    /**
     * Actualiza los datos de un usuario
     * 
     * Solo actualiza los campos que vienen en el array $data.
     * Si se pasa una nueva contraseña, se hashea automáticamente.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        // Solo agregamos los campos que vienen con datos
        if (!empty($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = $data['email'];
        }

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (!empty($data['role'])) {
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }

        // Si no hay nada que actualizar, salimos
        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";

        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Elimina un usuario por su ID
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Verifica las credenciales del usuario
     * 
     * Busca el usuario por email y compara la contraseña
     * usando password_verify para manejar el hash.
     */
    public function verifyCredentials(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        // Verificamos la contraseña contra el hash guardado
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return null;
    }

    /**
     * Obtiene todos los usuarios
     * 
     * Solo debería usarse desde la vista de administración.
     * No incluye el hash de la contraseña por seguridad.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT id, email, role, created_at FROM users ORDER BY role, email");
        return $stmt->fetchAll();
    }

    /**
     * Cuenta usuarios agrupados por rol
     * 
     * Útil para mostrar estadísticas en el dashboard.
     */
    public function countByRole(): array
    {
        $stmt = $this->db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['role']] = $row['count'];
        }
        return $result;
    }
}
