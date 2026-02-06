<?php
/**
 * Import reservations from XLS file
 */
chdir(dirname(__DIR__));
require_once 'config/database.php';

$db = getReservasConnection();
$usersDb = getUsersConnection();

echo "=== Importando reservas desde OLD/reservas.xls ===\n\n";

// Read file
$lines = file('OLD/reservas.xls', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
array_shift($lines); // Remove header

// Get user map
$userMap = [];
$stmt = $usersDb->query('SELECT id, email FROM users');
while ($row = $stmt->fetch()) {
    $userMap[strtolower($row['email'])] = $row['id'];
}

$inserted = 0;
$skipped = 0;

$insertStmt = $db->prepare('INSERT OR IGNORE INTO reservations 
    (id, user_id, user_email, area, responsible, num_people, reservation_date, start_time, end_time, comment) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

foreach ($lines as $line) {
    $parts = explode("\t", $line);
    if (count($parts) < 8) {
        $skipped++;
        continue;
    }

    $id = trim($parts[0]);
    $email = trim($parts[1]);
    $area = trim($parts[2]);
    $responsible = trim($parts[3]);
    $numPeople = (int) $parts[4];
    $date = trim($parts[5]);
    $startTime = trim($parts[6]);
    $endTime = trim($parts[7]);
    $comment = isset($parts[8]) ? trim($parts[8]) : '';

    // Find or create user
    $emailLower = strtolower($email);
    if (!isset($userMap[$emailLower])) {
        $hash = password_hash('temp123', PASSWORD_BCRYPT);
        $usersDb->prepare('INSERT OR IGNORE INTO users (email, password_hash, role) VALUES (?, ?, "user")')
            ->execute([$email, $hash]);
        $userMap[$emailLower] = $usersDb->lastInsertId();
    }

    try {
        $insertStmt->execute([
            $id,
            $userMap[$emailLower],
            $email,
            $area,
            $responsible,
            $numPeople,
            $date,
            $startTime,
            $endTime,
            $comment
        ]);
        $inserted++;
        echo "✓ $date | $area\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        $skipped++;
    }
}

echo "\n=== IMPORTACIÓN COMPLETADA ===\n";
echo "Insertadas: $inserted\n";
echo "Omitidas (duplicadas): $skipped\n";
echo "Total reservas en BD: " . $db->query('SELECT COUNT(*) FROM reservations')->fetchColumn() . "\n";
