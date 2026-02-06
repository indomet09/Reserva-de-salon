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
    <link rel="stylesheet" href="css/app.css">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📅</text></svg>">
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

        <form action="register" method="POST" id="registerForm">
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
            <p>¿Ya tienes cuenta? <a href="./">Inicia sesión</a></p>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>

</html>