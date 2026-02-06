<?php
/**
 * Punto de Entrada Principal - Router
 * 
 * Sistema de Reservas de Salón - Compatible NORTIC A6:2016
 * 
 * Este archivo recibe todas las peticiones HTTP y las dirige
 * al controlador y método correspondiente según la URL.
 * 
 * @author INDOMET
 * @version 2.1.0
 * @license MIT
 */

// Mostrar errores en desarrollo (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// Verificar si el sistema está instalado
// ============================================
$lockFile = __DIR__ . '/../config/.installed';
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Si no está instalado y no estamos en install.php, redirigir
if (!file_exists($lockFile) && strpos($requestUri, 'install.php') === false) {
    header('Location: /install.php');
    exit;
}

// Cargar configuración y dependencias
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Models/Reservation.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/ReservationController.php';
require_once __DIR__ . '/../src/Controllers/AdminController.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Extraer la ruta limpia de la URL
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = trim($requestUri, '/') ?: 'home';

// ============================================
// Funciones de protección CSRF
// ============================================

/**
 * Genera un token CSRF para proteger formularios
 * 
 * Se guarda en la sesión y se compara al recibir el form.
 */
function generateCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida que el token CSRF del formulario sea correcto
 */
function validateCsrfToken(): bool
{
    return isset($_POST['csrf_token']) &&
        isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// ============================================
// Manejo de rutas
// ============================================

try {
    switch ($route) {
        // ----------------------------------------
        // Página de inicio / Login
        // ----------------------------------------
        case 'home':
        case 'index.php':
        case '':
            // Si ya está logueado, ir a reservas
            if (AuthController::isAuthenticated()) {
                header('Location: reservations');
                exit;
            }
            include __DIR__ . '/../templates/login.php';
            break;

        // ----------------------------------------
        // Autenticación
        // ----------------------------------------
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!validateCsrfToken()) {
                    die('Token de seguridad inválido');
                }
                $auth = new AuthController();
                $auth->login();
            } else {
                include __DIR__ . '/../templates/login.php';
            }
            break;

        /*
                case 'register':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        if (!validateCsrfToken()) {
                            die('Token de seguridad inválido');
                        }
                        $auth = new AuthController();
                        $auth->register();
                    } else {
                        include __DIR__ . '/../templates/register.php';
                    }
                    break;
        */

        case 'logout':
            $auth = new AuthController();
            $auth->logout();
            break;

        // ----------------------------------------
        // Reservas
        // ----------------------------------------
        case 'reservations':
            AuthController::requireAuth();
            $controller = new ReservationController();
            $reservations = $controller->index();
            include __DIR__ . '/../templates/reservations/list.php';
            break;

        case 'reservations/new':
            AuthController::requireAuth();
            $editMode = false;
            $reservation = null;
            include __DIR__ . '/../templates/reservations/form.php';
            break;

        case 'reservations/create':
            AuthController::requireAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!validateCsrfToken()) {
                    die('Token de seguridad inválido');
                }
                $controller = new ReservationController();
                $controller->create();
            } else {
                header('Location: reservations/new');
                exit;
            }
            break;

        // ----------------------------------------
        // Exportación de datos
        // ----------------------------------------
        case 'reservations/export':
            AuthController::requireAuth();
            // Solo admin y manager pueden exportar
            if (!AuthController::canExport()) {
                header('Location: reservations?error=' . urlencode('No tiene permisos para exportar'));
                exit;
            }
            $controller = new ReservationController();
            $controller->exportExcel();
            break;

        case 'reservations/export-local':
            // Exporta a la carpeta Documentos/reservas/
            AuthController::requireAuth();
            if (!AuthController::canExport()) {
                header('Location: reservations?error=' . urlencode('No tiene permisos para exportar'));
                exit;
            }
            require_once __DIR__ . '/../scripts/monthly_export.php';
            $result = runMonthlyExport(false);
            if ($result['success']) {
                header('Location: reservations?success=' . urlencode($result['message']));
            } else {
                header('Location: reservations?error=' . urlencode($result['message']));
            }
            exit;
            break;

        // ----------------------------------------
        // Gestión de usuarios (solo admin)
        // ----------------------------------------
        case 'users':
            AuthController::requireAuth();
            if (!AuthController::isAdmin()) {
                header('Location: reservations?error=' . urlencode('Acceso denegado'));
                exit;
            }
            $userModel = new User();
            $users = $userModel->getAll();
            include __DIR__ . '/../templates/users/list.php';
            break;

        case 'users/new':
            AuthController::requireAuth();
            if (!AuthController::isAdmin()) {
                header('Location: reservations?error=' . urlencode('Acceso denegado'));
                exit;
            }
            $editMode = false;
            $user = null;
            include __DIR__ . '/../templates/users/form.php';
            break;

        // ----------------------------------------
        // Rutas dinámicas (con parámetros)
        // ----------------------------------------
        default:
            // Editar reserva: /reservations/edit/{id}
            if (preg_match('/^reservations\/edit\/([a-zA-Z0-9]+)$/', $route, $matches)) {
                AuthController::requireAuth();
                $id = $matches[1];
                $reservationModel = new Reservation();
                $reservation = $reservationModel->findById($id);

                if (!$reservation) {
                    header('Location: /reservations?error=' . urlencode('Reserva no encontrada'));
                    exit;
                }

                // Verificar permisos
                if (!AuthController::canModifyReservation($reservation)) {
                    header('Location: /reservations?error=' . urlencode('No tiene permisos'));
                    exit;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!validateCsrfToken()) {
                        die('Token de seguridad inválido');
                    }
                    $controller = new ReservationController();
                    $controller->update($id);
                } else {
                    $editMode = true;
                    include __DIR__ . '/../templates/reservations/form.php';
                }
            }
            // Eliminar reserva: /reservations/delete/{id}
            elseif (preg_match('/^reservations\/delete\/([a-zA-Z0-9]+)$/', $route, $matches)) {
                AuthController::requireAuth();
                $id = $matches[1];
                $controller = new ReservationController();
                $controller->delete($id);
            }
            // Editar usuario: /users/edit/{id}
            elseif (preg_match('/^users\/edit\/(\d+)$/', $route, $matches)) {
                AuthController::requireAuth();
                if (!AuthController::isAdmin()) {
                    header('Location: /reservations?error=' . urlencode('Acceso denegado'));
                    exit;
                }
                $id = (int) $matches[1];
                $userModel = new User();
                $user = $userModel->findById($id);

                if (!$user) {
                    header('Location: /users?error=' . urlencode('Usuario no encontrado'));
                    exit;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!validateCsrfToken()) {
                        die('Token de seguridad inválido');
                    }
                    $auth = new AuthController();
                    $auth->updateUser($id);
                } else {
                    $editMode = true;
                    include __DIR__ . '/../templates/users/form.php';
                }
            }
            // Eliminar usuario: /users/delete/{id}
            elseif (preg_match('/^users\/delete\/(\d+)$/', $route, $matches)) {
                AuthController::requireAuth();
                if (!AuthController::isAdmin()) {
                    header('Location: /reservations?error=' . urlencode('Acceso denegado'));
                    exit;
                }
                $id = (int) $matches[1];
                $auth = new AuthController();
                $auth->deleteUser($id);
            }
            // Crear usuario: /users/create
            elseif ($route === 'users/create') {
                AuthController::requireAuth();
                if (!AuthController::isAdmin()) {
                    header('Location: /reservations?error=' . urlencode('Acceso denegado'));
                    exit;
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!validateCsrfToken()) {
                        die('Token de seguridad inválido');
                    }
                    $auth = new AuthController();
                    $auth->createUser();
                } else {
                    header('Location: /users/new');
                    exit;
                }
            }
            // Configuración del Sistema (Admin)
            elseif ($route === 'admin/settings') {
                $controller = new AdminController();
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->update();
                } else {
                    $controller->index();
                }
            }
            // Ruta no encontrada
            else {
                http_response_code(404);
                echo '<h1>404 - Página no encontrada</h1>';
                echo '<p>Ruta: ' . htmlspecialchars($route) . '</p>';
                echo '<a href="/reservations">Volver a reservas</a>';
            }
            break;
    }
} catch (Exception $e) {
    // Registrar el error y mostrar mensaje genérico
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo '<h1>Error del servidor</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
