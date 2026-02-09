<?php
/**
 * Login Page Template
 */
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Iniciar sesión - Sistema de Reservas de Salón">
    <title>Iniciar Sesión - <?= app_setting('app_name', 'Reserva de Salón') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="icon" href="<?= asset(app_setting('app_favicon', '/assets/logo.svg')) ?>">
    <style>
        :root {
            --primary:
                <?= app_setting('primary_color', '#3b82f6') ?>
            ;
            --primary-dark:
                <?= app_setting('primary_color', '#3b82f6') ?>
            ;
            /* Simplify for now or use calc in future */
        }
    </style>
</head>

<body class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <a href="<?= url('reservations') ?>" title="Ir al calendario">
                <img src="<?= asset(app_setting('login_logo', '/assets/calendar_icon.svg')) ?>" alt="Logo"
                    class="auth-logo-img" style="cursor: pointer;">
            </a>
            <h1 class="auth-title">Reserva de Salón</h1>
            <p class="auth-subtitle"><?= app_setting('app_name', 'Julio Rib Santa María') ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" role="alert">
                <span>⚠️</span>
                <span>
                    <?= htmlspecialchars($error) ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <span>✅</span>
                <span>
                    <?= htmlspecialchars($success) ?>
                </span>
            </div>
        <?php endif; ?>

        <form action="<?= url('login') ?>" method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

            <div class="form-group">
                <label for="email" class="form-label required">Correo electrónico</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="correo@ejemplo.com" required
                    autocomplete="email" autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label required">Contraseña</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required
                    autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                Iniciar Sesión
            </button>
        </form>

        <div class="auth-footer">
            <div class="theme-switch" style="justify-content: center; margin-top: 1rem;">
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
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        // Brand Configuration (Injected from PHP)
        const brandConfig = {
            loginLight: "<?= asset(app_setting('login_logo', '/assets/calendar_icon.svg')) ?>",
            loginDark: "<?= asset(app_setting('login_logo_dark') ?: app_setting('login_logo', '/assets/calendar_icon.svg')) ?>"
        };

        function toggleTheme() {
            const themeSwitch = document.getElementById('themeSwitch');
            const isDark = themeSwitch.checked;
            const newTheme = isDark ? 'dark' : 'light';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // Update Logo
            const logo = document.querySelector('.auth-logo-img');
            if (logo) {
                logo.src = isDark ? brandConfig.loginDark : brandConfig.loginLight;
            }
        }

        // Init Theme and attach event listener
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);

            const themeSwitch = document.getElementById('themeSwitch');
            if (themeSwitch) {
                themeSwitch.checked = savedTheme === 'dark';
                themeSwitch.addEventListener('change', toggleTheme);
            }

            const logo = document.querySelector('.auth-logo-img');
            if (logo) {
                logo.src = (savedTheme === 'dark') ? brandConfig.loginDark : brandConfig.loginLight;
            }
        })();
    </script>
</body>

</html>