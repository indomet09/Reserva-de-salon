<?php
/**
 * User Form Template (Create/Edit - Admin only)
 */
$error = $_GET['error'] ?? null;
$editMode = isset($editMode) && $editMode === true;
$formAction = $editMode ? "/users/edit/{$user['id']}" : '/users/create';
$pageTitle = $editMode ? 'Editar Usuario' : 'Nuevo Usuario';

// Pre-fill if editing
$email = $editMode ? htmlspecialchars($user['email']) : '';
$role = $editMode ? $user['role'] : 'user';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script>
        // Apply saved theme immediately to prevent flash
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-title">
                    <a href="<?= url('reservations') ?>" title="Ir al calendario">
                        <img src="<?= asset(app_setting('app_logo', '/assets/logo.png')) ?>" alt="INDOMET" class="header-logo" id="siteLogo"
                            data-logo-light="<?= asset(app_setting('app_logo', '/assets/logo.png')) ?>"
                            data-logo-dark="<?= asset(app_setting('app_logo_dark') ?: app_setting('app_logo', '/assets/logo.png')) ?>">
                    </a>
                </div>
                <nav class="header-nav">
                    <!-- Theme Switch -->
                    <div class="theme-switch">
                        <label class="switch" title="Cambiar a modo oscuro">
                            <input type="checkbox" id="themeSwitch" onchange="toggleTheme()">
                            <span class="switch-slider"></span>
                            <span class="switch-icons">
                                <svg class="icon icon-off" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="5" />
                                    <line x1="12" y1="1" x2="12" y2="3" />
                                    <line x1="12" y1="21" x2="12" y2="23" />
                                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                                    <line x1="1" y1="12" x2="3" y2="12" />
                                    <line x1="21" y1="12" x2="23" y2="12" />
                                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                                </svg>
                                <svg class="icon icon-on" viewBox="0 0 24 24">
                                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                                </svg>
                            </span>
                        </label>
                    </div>
                    <a href="<?= url('users') ?>" class="btn btn-secondary btn-sm">← Volver</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form action="<?= url($formAction) ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label required">Correo electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        value="<?= $email ?>"
                        required
                        <?= $editMode ? '' : 'autofocus' ?>
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label <?= $editMode ? '' : 'required' ?>">
                        Contraseña <?= $editMode ? '(dejar vacío para no cambiar)' : '' ?>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input"
                        minlength="6"
                        <?= $editMode ? '' : 'required' ?>
                    >
                </div>
                
                <div class="form-group">
                    <label for="role" class="form-label required">Rol</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>
                            Usuario - Solo puede gestionar sus propias reservas
                        </option>
                        <option value="manager" <?= $role === 'manager' ? 'selected' : '' ?>>
                            Manejador - Gestiona todas las reservas y exporta
                        </option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>
                            Administrador - Control total (usuarios + reservas)
                        </option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <a href="<?= url('users') ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <?= $editMode ? '💾 Guardar Cambios' : '✅ Crear Usuario' ?>
                    </button>
                </div>
            </form>
        </div>
    </main>
    
    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        function toggleTheme() {
            const themeSwitch = document.getElementById('themeSwitch');
            const isDark = themeSwitch.checked;
            const newTheme = isDark ? 'dark' : 'light';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // Update Logo
            const logo = document.getElementById('siteLogo');
            if (logo) {
                logo.src = isDark ? logo.dataset.logoDark : logo.dataset.logoLight;
            }
        }

        // Init Theme Switch and Logo
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const themeSwitch = document.getElementById('themeSwitch');
            if (themeSwitch) {
                themeSwitch.checked = savedTheme === 'dark';
            }

            // Set initial logo
            const logo = document.getElementById('siteLogo');
            if (logo && savedTheme === 'dark') {
                logo.src = logo.dataset.logoDark;
            }
        })();
    </script>
</body>
</html>
