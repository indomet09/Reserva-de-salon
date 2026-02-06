<?php
/**
 * Base Layout Template
 */
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Reservas de Salón - <?= htmlspecialchars(APP_NAME) ?>">
    <title>
        <?= htmlspecialchars($pageTitle) ?>
    </title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- App Styles -->
    <link rel="stylesheet" href="/css/app.css">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📅</text></svg>">
</head>

<body>
    <?php if (AuthController::isAuthenticated()): ?>
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="header-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <?= htmlspecialchars(APP_NAME) ?>
                    </div>
                    <nav class="header-nav">
                        <span class="header-user">
                            👤
                            <?= htmlspecialchars($_SESSION['email']) ?>
                            <?php if (AuthController::isAdmin()): ?>
                                <span class="badge badge-warning" style="margin-left: 0.5rem;">Admin</span>
                            <?php endif; ?>
                        </span>
                        <a href="/logout" class="btn btn-secondary btn-sm">
                            🔒 Cerrar sesión
                        </a>
                    </nav>
                </div>
            </div>
        </header>
    <?php endif; ?>

    <main>
        <?= $content ?? '' ?>
    </main>

    <script src="/js/app.js"></script>
</body>

</html>