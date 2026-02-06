#!/usr/bin/env php
<?php
/**
 * Monthly Auto-Export Script
 * 
 * Este script exporta las reservas del mes anterior a un archivo CSV
 * en la carpeta Documentos/reservas del usuario.
 * 
 * Uso:
 *   - Ejecutar manualmente: php scripts/monthly_export.php
 *   - Programar con cron (Linux): 0 0 1 * * /usr/bin/php /path/to/scripts/monthly_export.php
 *   - Programar con Task Scheduler (Windows)
 * 
 * También puede ser llamado desde la aplicación web para exportar manualmente.
 */

// Bootstrap
require_once __DIR__ . '/../config/database.php';

/**
 * Get the user's Documents folder path (cross-platform)
 */
function getDocumentsPath(): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows: use USERPROFILE or HOMEDRIVE+HOMEPATH
        $home = getenv('USERPROFILE') ?: (getenv('HOMEDRIVE') . getenv('HOMEPATH'));
        return $home . DIRECTORY_SEPARATOR . 'Documents';
    } else {
        // Linux/Mac: use HOME
        $home = getenv('HOME') ?: '/tmp';
        // Try common document folder names
        $docPaths = [
            $home . '/Documentos',    // Spanish
            $home . '/Documents',     // English
            $home . '/Documentos'     // Fallback
        ];

        foreach ($docPaths as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        // Default to Documents if none exist
        return $home . '/Documents';
    }
}

/**
 * Get reservations from the previous month
 */
function getPreviousMonthReservations(): array
{
    $db = getReservasConnection();

    // Calculate previous month's date range
    $previousMonth = new DateTime('first day of last month');
    $startDate = $previousMonth->format('Y-m-01');
    $endDate = $previousMonth->format('Y-m-t');

    $stmt = $db->prepare(
        "SELECT * FROM reservations WHERE reservation_date BETWEEN ? AND ? ORDER BY reservation_date ASC, start_time ASC"
    );
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all past reservations (before today)
 */
function getPastReservations(): array
{
    $db = getReservasConnection();
    $today = date('Y-m-d');

    $stmt = $db->prepare(
        "SELECT * FROM reservations WHERE reservation_date < ? ORDER BY reservation_date ASC, start_time ASC"
    );
    $stmt->execute([$today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Export reservations to CSV file
 */
function exportToCSV(array $reservations, string $filePath): bool
{
    $output = fopen($filePath, 'w');

    if (!$output) {
        return false;
    }

    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Headers
    fputcsv($output, [
        'Área',
        'Responsable',
        'Personas',
        'Fecha',
        'Hora Inicio',
        'Hora Fin',
        'Comentario',
        'Fecha Solicitud'
    ]);

    // Data rows
    foreach ($reservations as $r) {
        $createdAt = !empty($r['created_at']) ? date('d/m/Y', strtotime($r['created_at'])) : 'N/A';
        fputcsv($output, [
            $r['area'],
            $r['responsible'],
            $r['num_people'],
            date('d/m/Y', strtotime($r['reservation_date'])),
            substr($r['start_time'], 0, 5),
            substr($r['end_time'], 0, 5),
            $r['comment'] ?? '',
            $createdAt
        ]);
    }

    fclose($output);
    return true;
}

/**
 * Main export function
 */
function runMonthlyExport(bool $verbose = true): array
{
    $result = [
        'success' => false,
        'message' => '',
        'file' => '',
        'count' => 0
    ];

    // Get Documents path
    $documentsPath = getDocumentsPath();
    $reservasPath = $documentsPath . DIRECTORY_SEPARATOR . 'reservas';

    // Create reservas folder if it doesn't exist
    if (!is_dir($reservasPath)) {
        if (!mkdir($reservasPath, 0755, true)) {
            $result['message'] = "Error: No se pudo crear la carpeta: $reservasPath";
            if ($verbose)
                echo $result['message'] . "\n";
            return $result;
        }
        if ($verbose)
            echo "Carpeta creada: $reservasPath\n";
    }

    // Get past reservations
    $reservations = getPastReservations();
    $result['count'] = count($reservations);

    if (empty($reservations)) {
        $result['success'] = true;
        $result['message'] = "No hay reservas pasadas para exportar.";
        if ($verbose)
            echo $result['message'] . "\n";
        return $result;
    }

    // Generate filename with date range
    $previousMonth = new DateTime('first day of last month');
    $monthName = strftime('%B_%Y', $previousMonth->getTimestamp());
    $timestamp = date('Y-m-d_His');
    $fileName = "reservas_hasta_{$timestamp}.csv";
    $filePath = $reservasPath . DIRECTORY_SEPARATOR . $fileName;

    // Export to CSV
    if (exportToCSV($reservations, $filePath)) {
        $result['success'] = true;
        $result['file'] = $filePath;
        $result['message'] = "Exportadas {$result['count']} reservas a: $filePath";
        if ($verbose)
            echo $result['message'] . "\n";
    } else {
        $result['message'] = "Error: No se pudo escribir el archivo: $filePath";
        if ($verbose)
            echo $result['message'] . "\n";
    }

    return $result;
}

// Run if executed directly from command line
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0])) {
    echo "=== Exportación Mensual de Reservas ===\n";
    echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
    echo "----------------------------------------\n";

    $result = runMonthlyExport(true);

    echo "----------------------------------------\n";
    echo "Estado: " . ($result['success'] ? 'ÉXITO' : 'ERROR') . "\n";

    exit($result['success'] ? 0 : 1);
}
