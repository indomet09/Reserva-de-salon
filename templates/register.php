<?php
/**
 * Register Page Template
 */
$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro - Sistema de Reservas de Salón">
    <title>Registro - Reserva de Salón Julio Rib Santa María</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📅</text></svg>">
    <script>
        // Apply saved theme immediately
        (function () {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>

<body class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">📝</div>
            <h1 class="auth-title">Crear Cuenta</h1>
            <p class="auth-subtitle">Sistema de Reservas</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" role="alert">
                <span>⚠️</span>
                <span>
                    <?= htmlspecialchars($error) ?>
                </span>
            </div>
        <?php endif; ?>

        <form action="<?= url('register') ?>" method="POST" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

            <div class="form-group">
                <label for="email" class="form-label required">Correo electrónico</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="correo@ejemplo.com" required
                    autocomplete="email" autofocus>
                <p class="form-hint">Utilice su correo institucional</p>
            </div>

            <div class="form-group">
                <label for="password" class="form-label required">Contraseña</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required
                    minlength="6" autocomplete="new-password">
                <p class="form-hint">Mínimo 6 caracteres</p>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label required">Confirmar contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                    placeholder="••••••••" required minlength="6" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                Crear Cuenta
            </button>
        </form>

        <div class="auth-footer">
            <p>¿Ya tienes cuenta? <a href="<?= url('/') ?>">Inicia sesión</a></p>
            <div class="theme-toggle" style="justify-content: center; margin-top: 1rem;">
                <label class="switch">
                    <input type="checkbox" id="themeSwitch" onclick="toggleTheme()">
                    <span class="switch-slider"></span>
                </label>
                <span style="font-size: 0.9rem; color: var(--text-muted); margin-left:8px;">🌓</span>
            </div>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        function toggleTheme() {
            const themeSwitch = document.getElementById('themeSwitch');
            const isDark = themeSwitch.checked;
            const newTheme = isDark ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }
        // Init theme switch state
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const themeSwitch = document.getElementById('themeSwitch');
            if (themeSwitch) themeSwitch.checked = savedTheme === 'dark';
        })();
    </script>
</body>

</html>