<?php
/**
 * Asistente de Instalación - Sistema de Reservas
 * Diseño Flat / SVG - NORTIC A6
 */

// Si ya está instalado, redirigir
if (file_exists(__DIR__ . '/../config/.installed')) {
    header('Location: /');
    exit;
}

// Configuración
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$error = '';
$success = '';

// Funciones Helper
function generateRandomSecret($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

function getSvgIcon($name)
{
    $icons = [
        'check' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
        'error' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>',
        'server' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>',
        'user' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>',
        'done' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        'logo' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'
    ];
    return $icons[$name] ?? '';
}

// Lógica de Pasos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'step1':
                $_SESSION['install_config'] = $_POST;
                header('Location: ?step=2');
                exit;

            case 'step2':
                // Validación básica
                if ($_POST['password'] !== $_POST['password_confirm']) {
                    $error = "Las contraseñas no coinciden.";
                } else {
                    // Guardamos configuración y ejecutamos instalación
                    $appName = $_SESSION['install_config']['app_name'] ?? 'Reservas del Salón';
                    $adminEmail = $_POST['email'];
                    $adminPass = $_POST['password'];
                    $adminName = $_POST['name'];

                    // 1. Crear directorios
                    $dbDir = __DIR__ . '/../database';
                    if (!is_dir($dbDir))
                        mkdir($dbDir, 0755, true);

                    // 2. Conexión y Creación de Tablas
                    try {
                        // DB Usuarios
                        $usersDb = new PDO("sqlite:$dbDir/usuarios.db");
                        $usersDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $usersDb->exec("CREATE TABLE IF NOT EXISTS users (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            email TEXT UNIQUE NOT NULL,
                            password_hash TEXT NOT NULL,
                            role TEXT NOT NULL DEFAULT 'user',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )");

                        // DB Reservas
                        $reservasDb = new PDO("sqlite:$dbDir/reservas.db");
                        $reservasDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $reservasDb->exec("CREATE TABLE IF NOT EXISTS reservations (
                            id TEXT PRIMARY KEY,
                            user_id INTEGER NOT NULL,
                            user_email TEXT,
                            area TEXT NOT NULL,
                            responsible TEXT NOT NULL,
                            num_people INTEGER NOT NULL,
                            reservation_date DATE NOT NULL,
                            start_time TIME NOT NULL,
                            end_time TIME NOT NULL,
                            comment TEXT,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )");

                        // Tabla Settings (Nuevo Sistema de Branding)
                        $usersDb->exec("CREATE TABLE IF NOT EXISTS settings (
                            key TEXT PRIMARY KEY,
                            value TEXT,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )");

                        // Insertar Settings por Defecto
                        $stmt = $usersDb->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
                        $defaultSettings = [
                            'app_name' => $appName,
                            'app_logo' => '/assets/logo.svg', // Default logo path
                            'primary_color' => '#3b82f6',
                            'login_logo' => '/assets/calendar_icon.svg'
                        ];
                        foreach ($defaultSettings as $k => $v) {
                            $stmt->execute([$k, $v]);
                        }

                        // 3. Crear Admin
                        $hash = password_hash($adminPass, PASSWORD_BCRYPT);
                        $stmt = $usersDb->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'admin')");
                        $stmt->execute([$adminEmail, $hash]);
                        $userId = $usersDb->lastInsertId();

                        // 4. Generar config/app.php
                        $configContent = "<?php\n";
                        $configContent .= "define('APP_NAME', '" . addslashes($appName) . "');\n";
                        $configContent .= "define('APP_SECRET', '" . generateRandomSecret() . "');\n";
                        $configContent .= "define('APP_DEBUG', false);\n";
                        $configContent .= "define('SESSION_LIFETIME', 3600);\n";

                        file_put_contents(__DIR__ . '/../config/app.php', $configContent);

                        // 5. AUTO-LOGIN
                        if (session_status() === PHP_SESSION_NONE)
                            session_start();
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['email'] = $adminEmail;
                        $_SESSION['role'] = 'admin';
                        $_SESSION['last_activity'] = time();

                        // 6. Marcar como instalado
                        file_put_contents(__DIR__ . '/../config/.installed', date('Y-m-d H:i:s'));

                        header('Location: ?step=3'); // Éxito
                        exit;

                    } catch (Exception $e) {
                        $error = "Error durante la instalación: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Reservas</title>
    <style>
        /* Flat Design & Tailwind-ish Colors */
        :root {
            --primary: #3b82f6;
            /* Blue 500 */
            --primary-dark: #2563eb;
            --bg: #f3f4f6;
            /* Gray 100 */
            --surface: #ffffff;
            --text-main: #1f2937;
            /* Gray 800 */
            --text-muted: #6b7280;
            /* Gray 500 */
            --border: #e5e7eb;
            /* Gray 200 */
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: var(--surface);
            width: 100%;
            max-width: 500px;
            border-radius: 12px;
            /* Smooth corners */
            box-shadow: 0 4px 6px -1px androidx(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        .header {
            background: var(--surface);
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .header h1 {
            margin: 1rem 0 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .header p {
            color: var(--text-muted);
            margin: 0;
        }

        .header-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: #eff6ff;
            color: var(--primary);
            border-radius: 16px;
        }

        .content {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.875rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .req-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem;
        }

        .req-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .req-item:last-child {
            border-bottom: none;
        }

        .req-icon {
            margin-right: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* SVG Utility Classes */
        .w-6 {
            width: 1.5rem;
        }

        .h-6 {
            height: 1.5rem;
        }

        .w-8 {
            width: 2rem;
        }

        .h-8 {
            height: 2rem;
        }

        .w-12 {
            width: 3rem;
        }

        .h-12 {
            height: 3rem;
        }

        .text-green-500 {
            color: #10b981;
        }

        .text-red-500 {
            color: #ef4444;
        }

        .text-blue-600 {
            color: #2563eb;
        }

        .mx-auto {
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <div class="header-icon">
                <?= getSvgIcon('logo') ?>
            </div>
            <h1>Instalador de Sistema</h1>
            <p>Configuración inicial rápida</p>
        </div>

        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- Paso 1: Requisitos -->
                <form method="POST">
                    <input type="hidden" name="action" value="step1">

                    <h3 style="margin-top:0;">Verificación de Entorno</h3>
                    <ul class="req-list">
                        <li class="req-item">
                            <span
                                class="req-icon"><?= version_compare(PHP_VERSION, '7.4.0', '>=') ? getSvgIcon('check') : getSvgIcon('error') ?></span>
                            <span>PHP 7.4+ (Detectado: <?= PHP_VERSION ?>)</span>
                        </li>
                        <li class="req-item">
                            <span
                                class="req-icon"><?= in_array('sqlite', PDO::getAvailableDrivers()) ? getSvgIcon('check') : getSvgIcon('error') ?></span>
                            <span>Driver SQLite (PDO)</span>
                        </li>
                        <li class="req-item">
                            <span
                                class="req-icon"><?= is_writable(__DIR__ . '/../config') ? getSvgIcon('check') : getSvgIcon('error') ?></span>
                            <span>Permisos de Escritura (config/)</span>
                        </li>
                    </ul>

                    <div class="form-group">
                        <label for="app_name">Nombre de la Institución / Sistema</label>
                        <input type="text" name="app_name" id="app_name" value="Reservas del Salón" required>
                    </div>

                    <button type="submit" class="btn">Continuar &rarr;</button>
                </form>

            <?php elseif ($step === 2): ?>
                <!-- Paso 2: Usuario Admin -->
                <form method="POST">
                    <input type="hidden" name="action" value="step2">

                    <div style="text-align:center; margin-bottom:1.5rem;">
                        <?= getSvgIcon('user') ?>
                        <h3>Crear Administrador</h3>
                    </div>

                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="name" required placeholder="Ej. Admin Principal">
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" required placeholder="admin@ejemplo.com">
                    </div>

                    <div class="form-group">
                        <label>Contraseña Maestra</label>
                        <input type="password" name="password" required minlength="8">
                    </div>

                    <div class="form-group">
                        <label>Confirmar Contraseña</label>
                        <input type="password" name="password_confirm" required minlength="8">
                    </div>

                    <button type="submit" class="btn">Instalar y Acceder</button>
                </form>

            <?php elseif ($step === 3): ?>
                <!-- Paso 3: Éxito -->
                <div style="text-align: center;">
                    <?= getSvgIcon('done') ?>
                    <h2 style="color: var(--text-main); margin-bottom: 0.5rem;">¡Instalación Completada!</h2>
                    <p style="color: var(--text-muted); margin-bottom: 2rem;">El sistema ha sido configurado correctamente.
                    </p>

                    <a href="/" class="btn">Ir al Dashboard &rarr;</a>
                    <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                        Se ha iniciado sesión automáticamente.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>