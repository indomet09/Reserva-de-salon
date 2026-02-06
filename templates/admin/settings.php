<?php
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

// Helper para valores
function val($key, $settings)
{
    return htmlspecialchars($settings[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-title">
                    <img src="<?= app_setting('app_logo', '/assets/logo.svg') ?>" class="header-logo"
                        style="height: 32px; width: auto;">
                    Admin Settings
                </div>
                <nav class="header-nav">
                    <a href="/reservations" class="btn btn-secondary btn-sm">← Volver al Dashboard</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 2rem;">
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header">
                <h1 class="card-title">⚙️ Configuración del Sistema</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="/admin/settings" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <!-- Identidad -->
                <h3 style="margin-top: 0;">Identidad</h3>
                <div class="form-group">
                    <label for="app_name">Nombre de la Institución / Sistema</label>
                    <input type="text" name="app_name" id="app_name" class="form-input"
                        value="<?= val('app_name', $settings) ?>" required>
                </div>

                <!-- Color -->
                <h3>Apariencia</h3>
                <div class="form-group">
                    <label for="primary_color">Color Primario</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" name="primary_color" id="primary_color"
                            value="<?= val('primary_color', $settings) ?: '#3b82f6' ?>"
                            style="height: 40px; width: 60px; padding: 0; border: none;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Selecciona el color de marca</span>
                    </div>
                </div>

                <!-- Logos -->
                <h3>Logotipos</h3>

                <div class="form-row">
                    <!-- Logo Header Light -->
                    <div class="form-group">
                        <label>Logo de Cabecera (Light)</label>
                        <div
                            style="margin-bottom: 10px; padding: 10px; border: 1px dashed var(--border); text-align: center;">
                            <img src="<?= val('app_logo', $settings) ?>" style="max-height: 50px;">
                        </div>
                        <input type="file" name="logo_file" accept=".svg,.png,.jpg,.jpeg">
                    </div>

                    <!-- Logo Header Dark -->
                    <div class="form-group">
                        <label>Logo de Cabecera (Dark)</label>
                        <div
                            style="margin-bottom: 10px; padding: 10px; border: 1px dashed var(--border); text-align: center; background: #333;">
                            <img src="<?= val('app_logo_dark', $settings) ?>" style="max-height: 50px;">
                        </div>
                        <input type="file" name="logo_dark_file" accept=".svg,.png,.jpg,.jpeg">
                    </div>
                </div>

                <div class="form-row">
                    <!-- Logo Login Light -->
                    <div class="form-group">
                        <label>Icono de Login (Light)</label>
                        <div
                            style="margin-bottom: 10px; padding: 10px; border: 1px dashed var(--border); text-align: center;">
                            <img src="<?= val('login_logo', $settings) ?>" style="max-height: 50px;">
                        </div>
                        <input type="file" name="login_logo_file" accept=".svg,.png,.jpg,.jpeg">
                    </div>

                    <!-- Logo Login Dark -->
                    <div class="form-group">
                        <label>Icono de Login (Dark)</label>
                        <div
                            style="margin-bottom: 10px; padding: 10px; border: 1px dashed var(--border); text-align: center; background: #333;">
                            <img src="<?= val('login_logo_dark', $settings) ?>" style="max-height: 50px;">
                        </div>
                        <input type="file" name="login_logo_dark_file" accept=".svg,.png,.jpg,.jpeg">
                    </div>
                </div>

                <!-- Favicon -->
                <div class="form-group">
                    <label>Favicon (ICO/PNG)</label>
                    <input type="file" name="favicon_file" accept=".ico,.png">
                    <small style="color: var(--text-muted);">Recomendado: 32x32px .ico</small>
                </div>

                <div
                    style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border); text-align: right;">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>