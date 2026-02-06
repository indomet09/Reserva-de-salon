<?php
/**
 * Database Setup Script
 * Creates separate SQLite databases for users and reservations
 * 
 * USAGE: php database/setup.php
 */

chdir(dirname(__DIR__));

require_once 'config/database.php';

echo "===========================================\n";
echo "Sistema de Reservas - Setup SQLite\n";
echo "===========================================\n\n";

// ============================================
// Setup Users Database
// ============================================
echo "📦 Configurando base de datos de USUARIOS...\n";

$usersDb = getUsersConnection();

$usersDb->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'user' CHECK(role IN ('admin', 'manager', 'user')),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
");

echo "  ✓ Tabla 'users' creada en usuarios.db\n";

// ============================================
// Setup Reservations Database
// ============================================
echo "📦 Configurando base de datos de RESERVAS...\n";

$reservasDb = getReservasConnection();

$reservasDb->exec("
CREATE TABLE IF NOT EXISTS reservations (
    id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    user_email TEXT,
    area TEXT NOT NULL,
    responsible TEXT NOT NULL,
    num_people INTEGER NOT NULL,
    reservation_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    comment TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_reservations_date ON reservations(reservation_date);
CREATE INDEX IF NOT EXISTS idx_reservations_date_time ON reservations(reservation_date, start_time, end_time);
CREATE INDEX IF NOT EXISTS idx_reservations_user ON reservations(user_id);

CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id TEXT,
    details TEXT,
    ip_address TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_audit_action ON audit_log(action);
CREATE INDEX IF NOT EXISTS idx_audit_date ON audit_log(created_at);
");

echo "  ✓ Tablas 'reservations' y 'audit_log' creadas en reservas.db\n\n";

// ============================================
// Migrate Users from OLD/usuarios.txt
// ============================================
$usersFile = __DIR__ . '/../OLD/usuarios.txt';

if (file_exists($usersFile)) {
    echo "📥 Migrando usuarios desde OLD/usuarios.txt...\n";

    $usersData = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $usersInserted = 0;

    $insertUserStmt = $usersDb->prepare(
        "INSERT OR REPLACE INTO users (email, password_hash, role) VALUES (?, ?, ?)"
    );

    foreach ($usersData as $line) {
        $parts = explode('|', trim($line));
        if (count($parts) < 2)
            continue;

        $email = trim($parts[0]);
        $password = trim($parts[1]);

        if (empty($email) || empty($password))
            continue;

        // Set role: admin@salon.com = admin
        $role = ($email === 'admin@salon.com') ? ROLE_ADMIN : ROLE_USER;

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $insertUserStmt->execute([$email, $passwordHash, $role]);
            $usersInserted++;
        } catch (PDOException $e) {
            // Skip errors
        }
    }

    echo "  ✓ Usuarios migrados: $usersInserted\n\n";
} else {
    echo "⚠ No se encontró OLD/usuarios.txt\n";
    echo "  Creando usuario administrador por defecto...\n";

    $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
    $usersDb->prepare("INSERT OR IGNORE INTO users (email, password_hash, role) VALUES (?, ?, ?)")
        ->execute(['admin@salon.com', $adminHash, ROLE_ADMIN]);

    echo "  ✓ Admin: admin@salon.com / admin123\n\n";
}

// ============================================
// Migrate Reservations from OLD/reservas.txt
// ============================================
$reservasFile = __DIR__ . '/../OLD/reservas.txt';

if (file_exists($reservasFile)) {
    echo "📥 Migrando reservas desde OLD/reservas.txt...\n";

    $reservasData = file($reservasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $reservasInserted = 0;

    // Get user IDs from users DB
    $userMap = [];
    $usersResult = $usersDb->query("SELECT id, email FROM users");
    while ($row = $usersResult->fetch()) {
        $userMap[$row['email']] = $row['id'];
    }

    $insertReservaStmt = $reservasDb->prepare(
        "INSERT OR REPLACE INTO reservations 
         (id, user_id, user_email, area, responsible, num_people, reservation_date, start_time, end_time, comment) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($reservasData as $line) {
        $parts = explode('|', trim($line));
        if (count($parts) < 8)
            continue;

        $id = trim($parts[0]);
        $email = trim($parts[1]);
        $area = trim($parts[2]);
        $responsible = trim($parts[3]);
        $numPeople = abs((int) trim($parts[4]));
        $date = trim($parts[5]);
        $startTime = trim($parts[6]);
        $endTime = trim($parts[7]);
        $comment = isset($parts[8]) ? trim($parts[8]) : '';

        // Get user ID or create temp user
        if (!isset($userMap[$email])) {
            $tempHash = password_hash('temp123', PASSWORD_BCRYPT);
            $usersDb->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'user')")
                ->execute([$email, $tempHash]);
            $userMap[$email] = $usersDb->lastInsertId();
        }

        try {
            $insertReservaStmt->execute([
                $id,
                $userMap[$email],
                $email,
                $area,
                $responsible,
                $numPeople,
                $date,
                $startTime,
                $endTime,
                $comment
            ]);
            $reservasInserted++;
        } catch (PDOException $e) {
            // Skip errors
        }
    }

    echo "  ✓ Reservas migradas: $reservasInserted\n\n";
} else {
    echo "⚠ No se encontró OLD/reservas.txt - sin reservas que migrar\n\n";
}

// ============================================
// Summary
// ============================================
echo "===========================================\n";
echo "✅ SETUP COMPLETADO\n";
echo "===========================================\n\n";

$userCount = $usersDb->query("SELECT COUNT(*) FROM users")->fetchColumn();
$reservaCount = $reservasDb->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

echo "📊 Estadísticas:\n";
echo "  - Usuarios: $userCount (en database/usuarios.db)\n";
echo "  - Reservas: $reservaCount (en database/reservas.db)\n\n";

echo "🔐 Roles de usuarios:\n";
echo "  - Admin: Gestiona usuarios y todas las reservas\n";
echo "  - Manejador: Gestiona reservas y exporta\n";
echo "  - Usuario: Solo sus propias reservas\n\n";

echo "🚀 Para iniciar el servidor:\n";
echo "   php -S localhost:8000 -t public\n\n";
echo "   Luego abrir: http://localhost:8000\n\n";
