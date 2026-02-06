<?php
/**
 * Script de Respaldo Automático - NORTIC A6
 * 
 * Este script realiza una copia de seguridad de las bases de datos SQLite.
 * Puede ser ejecutado manualmente o mediante CRON job.
 * 
 * Uso: php scripts/backup.php
 */

require_once __DIR__ . '/../config/database.php';

// Configuración
$backupDir = __DIR__ . '/../backups';
$retentionDays = 30; // Días de retención
$timestamp = date('Y-m-d_H-i-s');

// Crear directorio si no existe
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    // Crear .htaccess para prohibir acceso web directo
    file_put_contents($backupDir . '/.htaccess', "Order Deny,Allow\nDeny from all");
}

echo "=== Iniciando Respaldo del Sistema ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "Directorio: " . $backupDir . "\n\n";

// Archivos a respaldar
$databases = [
    'usuarios' => DB_USERS_PATH,
    'reservas' => DB_RESERVAS_PATH
];

foreach ($databases as $name => $path) {
    if (file_exists($path)) {
        $filename = "{$name}_{$timestamp}.db";
        $destination = $backupDir . '/' . $filename;

        if (copy($path, $destination)) {
            echo "✅ Respaldo exitoso: {$filename}\n";
        } else {
            echo "❌ Error al respaldar: {$name}\n";
        }
    } else {
        echo "⚠️ Base de datos no encontrada: {$path}\n";
    }
}

// Limpieza de respaldos antiguos
echo "\n=== Limpiando respaldos antiguos (> {$retentionDays} días) ===\n";
$files = glob($backupDir . '/*.db');
$deletedCount = 0;

foreach ($files as $file) {
    if (is_file($file)) {
        $fileAge = time() - filemtime($file);
        if ($fileAge > ($retentionDays * 86400)) {
            unlink($file);
            echo "🗑️ Eliminado: " . basename($file) . "\n";
            $deletedCount++;
        }
    }
}

if ($deletedCount === 0) {
    echo "No hay respaldos antiguos para eliminar.\n";
} else {
    echo "Total eliminados: {$deletedCount}\n";
}

echo "\n=== Respaldo Finalizado ===\n";
