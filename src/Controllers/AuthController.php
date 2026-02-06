<?php
/**
 * AuthController - Controlador de Autenticación
 * 
 * Maneja todo lo relacionado con la autenticación de usuarios:
 * login, logout, registro y gestión de usuarios (solo admin).
 * 
 * @author INDOMET
 * @version 2.1.0
 * @license MIT
 */

// Iniciamos la sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Models/User.php';

class AuthController
{
    // Modelo para operaciones con la base de datos de usuarios
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Procesa el inicio de sesión
     * 
     * Valida las credenciales y crea la sesión del usuario.
     * Si las credenciales son correctas, redirige al calendario.
     */
    public function login(): void
    {
        // Solo aceptamos peticiones POST para el login
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/');
            return;
        }

        // Limpiamos los datos de entrada
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validamos que no estén vacíos
        if (empty($email) || empty($password)) {
            $this->redirectWithError('/', 'Por favor complete todos los campos');
            return;
        }

        // Verificamos las credenciales en la base de datos
        $user = $this->userModel->verifyCredentials($email, $password);

        if ($user) {
            // Regeneramos el ID de sesión para prevenir session fixation
            session_regenerate_id(true);

            // Guardamos los datos del usuario en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            $this->redirect('/reservations');
        } else {
            $this->redirectWithError('/', 'Correo o contraseña incorrectos');
        }
    }

    /**
     * Procesa el registro de nuevos usuarios
     * 
     * Valida los datos del formulario y crea el usuario.
     * Los nuevos usuarios siempre tienen rol 'user'.
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        // Validaciones básicas
        if (empty($email) || empty($password)) {
            $this->redirectWithError('/register', 'Complete todos los campos');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithError('/register', 'Email inválido');
            return;
        }

        if (strlen($password) < 6) {
            $this->redirectWithError('/register', 'La contraseña debe tener al menos 6 caracteres');
            return;
        }

        if ($password !== $confirmPassword) {
            $this->redirectWithError('/register', 'Las contraseñas no coinciden');
            return;
        }

        // Verificamos que el email no esté en uso
        if ($this->userModel->findByEmail($email)) {
            $this->redirectWithError('/register', 'El correo ya está registrado');
            return;
        }

        // Creamos el usuario con rol básico
        $userId = $this->userModel->create($email, $password, ROLE_USER);

        if ($userId) {
            $this->redirectWithSuccess('/', 'Registro exitoso. Inicie sesión.');
        } else {
            $this->redirectWithError('/register', 'Error al crear usuario');
        }
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout(): void
    {
        session_destroy();
        $this->redirect('/');
    }

    /**
     * Crea un nuevo usuario (solo administradores)
     * 
     * Permite asignar cualquier rol al nuevo usuario.
     */
    public function createUser(): void
    {
        // Solo administradores pueden crear usuarios
        if (!self::isAdmin()) {
            $this->redirect('/reservations');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? ROLE_USER;

        if (empty($email) || empty($password)) {
            $this->redirectWithError('/users/new', 'Complete todos los campos');
            return;
        }

        // Nos aseguramos de que el rol sea válido
        if (!in_array($role, [ROLE_ADMIN, ROLE_MANAGER, ROLE_USER])) {
            $role = ROLE_USER;
        }

        if ($this->userModel->findByEmail($email)) {
            $this->redirectWithError('/users/new', 'El correo ya está registrado');
            return;
        }

        $userId = $this->userModel->create($email, $password, $role);

        if ($userId) {
            $this->redirectWithSuccess('/users', 'Usuario creado exitosamente');
        } else {
            $this->redirectWithError('/users/new', 'Error al crear usuario');
        }
    }

    /**
     * Actualiza los datos de un usuario (solo administradores)
     * 
     * Permite cambiar email, contraseña y/o rol.
     */
    public function updateUser(int $id): void
    {
        if (!self::isAdmin()) {
            $this->redirect('/reservations');
            return;
        }

        // Solo actualizamos los campos que fueron enviados
        $data = [];

        if (!empty($_POST['email'])) {
            $data['email'] = trim($_POST['email']);
        }

        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }

        if (!empty($_POST['role']) && in_array($_POST['role'], [ROLE_ADMIN, ROLE_MANAGER, ROLE_USER])) {
            $data['role'] = $_POST['role'];
        }

        if ($this->userModel->update($id, $data)) {
            $this->redirectWithSuccess('/users', 'Usuario actualizado exitosamente');
        } else {
            $this->redirectWithError("/users/edit/$id", 'Error al actualizar usuario');
        }
    }

    /**
     * Elimina un usuario (solo administradores)
     * 
     * No se permite eliminar el propio usuario para evitar
     * quedarse sin acceso al sistema.
     */
    public function deleteUser(int $id): void
    {
        if (!self::isAdmin()) {
            $this->redirect('/reservations');
            return;
        }

        // Protección: no te puedes eliminar a ti mismo
        if ($id === self::getCurrentUserId()) {
            $this->redirectWithError('/users', 'No puede eliminar su propia cuenta');
            return;
        }

        if ($this->userModel->delete($id)) {
            $this->redirectWithSuccess('/users', 'Usuario eliminado exitosamente');
        } else {
            $this->redirectWithError('/users', 'Error al eliminar usuario');
        }
    }

    // ============================================
    // Métodos estáticos para verificar permisos
    // Estos se usan desde cualquier parte del código
    // ============================================

    /**
     * Verifica si hay un usuario autenticado
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Verifica si el usuario actual es administrador
     */
    public static function isAdmin(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_ADMIN;
    }

    /**
     * Verifica si el usuario actual es manejador
     */
    public static function isManager(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_MANAGER;
    }

    /**
     * Verifica si el usuario actual es usuario regular
     */
    public static function isUser(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_USER;
    }

    /**
     * Obtiene el ID del usuario actual
     */
    public static function getCurrentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /**
     * Obtiene el email del usuario actual
     */
    public static function getCurrentUserEmail(): ?string
    {
        return $_SESSION['email'] ?? null;
    }

    /**
     * Obtiene el rol del usuario actual
     */
    public static function getCurrentRole(): string
    {
        return $_SESSION['role'] ?? ROLE_USER;
    }

    /**
     * Verifica si el usuario puede exportar datos
     * Solo admin y manager tienen este permiso
     */
    public static function canExport(): bool
    {
        return self::isAdmin() || self::isManager();
    }

    /**
     * Verifica si el usuario puede modificar una reserva específica
     * 
     * Admin y Manager pueden modificar todas.
     * Los usuarios solo pueden modificar las suyas.
     */
    public static function canModifyReservation(array $reservation): bool
    {
        if (self::isAdmin() || self::isManager()) {
            return true;
        }
        return $reservation['user_id'] == self::getCurrentUserId();
    }

    /**
     * Verifica si el usuario puede eliminar una reserva específica
     * 
     * Mismas reglas que para modificar.
     */
    public static function canDeleteReservation(array $reservation): bool
    {
        if (self::isAdmin() || self::isManager()) {
            return true;
        }
        return $reservation['user_id'] == self::getCurrentUserId();
    }

    /**
     * Requiere que el usuario esté autenticado
     * 
     * Si no lo está, lo redirige al login.
     * También verifica que la sesión no haya expirado.
     */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: /');
            exit;
        }

        // Verificamos si la sesión ha expirado
        if (
            isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)
        ) {
            session_destroy();
            header('Location: /?error=' . urlencode('Sesión expirada'));
            exit;
        }

        // Actualizamos el tiempo de última actividad
        $_SESSION['last_activity'] = time();
    }

    // ============================================
    // Métodos auxiliares de redirección
    // ============================================

    private function redirect(string $path): void
    {
        header("Location: $path");
        exit;
    }

    private function redirectWithError(string $path, string $message): void
    {
        header("Location: $path?error=" . urlencode($message));
        exit;
    }

    private function redirectWithSuccess(string $path, string $message): void
    {
        header("Location: $path?success=" . urlencode($message));
        exit;
    }
}
