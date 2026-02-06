<?php
/**
 * Configuración de Base de Datos
 * 
 * Sistema de Reservas de Salón - Compatible con NORTIC A6:2016
 * 
 * Este sistema usa dos bases de datos SQLite separadas:
 * - usuarios.db: Almacena usuarios y datos de autenticación
 * - reservas.db: Almacena las reservas del salón
 * 
 * @author INDOMET
 * @version 2.1.0
 * @license MIT
 */

// Rutas a los archivos de base de datos
define('DB_USERS_PATH', __DIR__ . '/../database/usuarios.db');
define('DB_RESERVAS_PATH', __DIR__ . '/../database/reservas.db');

// ============================================
// Cargar configuración personalizada si existe
// ============================================
$appConfigFile = __DIR__ . '/app.php';
if (file_exists($appConfigFile)) {
    require_once $appConfigFile;
}

// Definir valores por defecto si no están configurados
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Sistema de Reservas de Salón');
}

if (!defined('INSTITUTION_NAME')) {
    define('INSTITUTION_NAME', '');
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '2.1.0');
}

if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 3600);
}

// Definir roles si no están configurados
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
}

if (!defined('ROLE_MANAGER')) {
    define('ROLE_MANAGER', 'manager');
}

if (!defined('ROLE_USER')) {
    define('ROLE_USER', 'user');
}

/**
 * Obtiene la conexión PDO a la base de datos de usuarios
 * 
 * Usa el patrón singleton para reutilizar la conexión.
 */
function getUsersConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_USERS_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            die("Error de conexión a usuarios: " . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * Obtiene la conexión PDO a la base de datos de reservas
 */
function getReservasConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_RESERVAS_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            die("Error de conexión a reservas: " . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * Función legacy para compatibilidad hacia atrás
 */
function getConnection(): PDO
{
    return getReservasConnection();
}

/**
 * Retrieves a system setting from the database, with caching.
 *
 * @param string $key The setting key to retrieve.
 * @param mixed $default The default value if the key is not found.
 * @return mixed The setting value or the default.
 */
function app_setting($key, $default = null)
{
    static $settingsCache = null;

    if ($settingsCache === null) {
        try {
            $pdo = getUsersConnection();
            $stmt = $pdo->query("SELECT key, value FROM settings");
            $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settingsCache = [];
        }
    }

    return $settingsCache[$key] ?? $default;
}
