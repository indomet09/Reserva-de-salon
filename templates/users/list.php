<?php
/**
 * Users List Template (Admin only)
 * Modernized with SVG icons, theme toggle, and user dropdown
 */
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

// Count by role
$roleLabels = [
    'admin' => ['label' => 'Administrador', 'class' => 'badge-danger'],
    'manager' => ['label' => 'Manejador', 'class' => 'badge-warning'],
    'user' => ['label' => 'Usuario', 'class' => 'badge-primary']
];

// Generate avatar URL from email
function getAvatarUrl($email, $style = 'identicon')
{
    $hash = md5(strtolower(trim($email)));
    // Abstract styles only - no human figures
    $styles = ['identicon', 'bottts', 'shapes', 'rings', 'initials'];
    $randomStyle = $styles[abs(crc32($email)) % count($styles)];
    return "https://api.dicebear.com/7.x/{$randomStyle}/svg?seed={$hash}";
}

$currentUserRole = $_SESSION['role'] ?? 'user';
$currentUserAvatar = getAvatarUrl($_SESSION['email']);
$currentRoleInfo = $roleLabels[$currentUserRole] ?? ['label' => $currentUserRole, 'class' => 'badge-secondary'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <!-- GSAP Animation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        // Apply saved theme immediately
        (function () {
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
                    <img src="/assets/logo.png" alt="INDOMET" class="header-logo" id="siteLogo">
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

                    <a href="/reservations" class="btn btn-secondary btn-sm">
                        <svg class="icon" viewBox="0 0 16 16" fill="currentColor">
                            <path
                                d="M5 0a1 1 0 0 1 1 1v1h4V1a1 1 0 1 1 2 0v1h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h2V1a1 1 0 0 1 1-1zM2 6v8h12V6H2z" />
                        </svg>
                        Reservas
                    </a>

                    <!-- User Dropdown -->
                    <div class="user-dropdown" id="userDropdown">
                        <img src="<?= $currentUserAvatar ?>" alt="Avatar" class="user-avatar" id="userAvatar">
                        <div class="dropdown-content" id="dropdownContent">
                            <div class="dropdown-header">
                                <img src="<?= $currentUserAvatar ?>" alt="Avatar" class="dropdown-avatar">
                                <div class="dropdown-info">
                                    <div class="dropdown-email"><?= htmlspecialchars($_SESSION['email']) ?></div>
                                    <div class="dropdown-role">
                                        <span
                                            class="badge <?= $currentRoleInfo['class'] ?>"><?= $currentRoleInfo['label'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-actions">
                                <a href="/logout" class="dropdown-btn logout">
                                    <svg class="icon" viewBox="0 0 16 16" fill="currentColor">
                                        <path
                                            d="M6.5 1a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zM11 2.5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0-.5.5v11a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 .5-.5v-11zm-5-.5a1.5 1.5 0 0 0-1.5 1.5v11a1.5 1.5 0 0 0 1.5 1.5h5a1.5 1.5 0 0 0 1.5-1.5v-11a1.5 1.5 0 0 0-1.5-1.5h-5z" />
                                        <path d="M10.5 10a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 1 0v3a.5.5 0 0 1-.5.5z" />
                                    </svg>
                                    Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <svg class="icon" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <svg class="icon" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="page-header">
                <h2 class="page-title">
                    <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M1.5 6.5C1.5 3.46243 3.96243 1 7 1C10.0376 1 12.5 3.46243 12.5 6.5C12.5 9.53757 10.0376 12 7 12C3.96243 12 1.5 9.53757 1.5 6.5Z" />
                        <path
                            d="M14.4999 6.5C14.4999 8.00034 14.0593 9.39779 13.3005 10.57C14.2774 11.4585 15.5754 12 16.9999 12C20.0375 12 22.4999 9.53757 22.4999 6.5C22.4999 3.46243 20.0375 1 16.9999 1C15.5754 1 14.2774 1.54153 13.3005 2.42996C14.0593 3.60221 14.4999 4.99966 14.4999 6.5Z" />
                        <path
                            d="M0 18C0 15.7909 1.79086 14 4 14H10C12.2091 14 14 15.7909 14 18V22C14 22.5523 13.5523 23 13 23H1C0.447716 23 0 22.5523 0 22V18Z" />
                        <path
                            d="M16 18V23H23C23.5522 23 24 22.5523 24 22V18C24 15.7909 22.2091 14 20 14H14.4722C15.4222 15.0615 16 16.4633 16 18Z" />
                    </svg>
                    Usuarios del Sistema
                </h2>
                <div class="action-buttons">
                    <a href="/users/new" class="btn btn-primary">
                        <svg class="icon" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M10 1H6V6L1 6V10H6V15H10V10H15V6L10 6V1Z" />
                        </svg>
                        Nuevo Usuario
                    </a>
                </div>
            </div>

            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <strong>Roles:</strong>
                <span class="badge badge-danger">Admin</span> = Gestiona usuarios y todas las reservas |
                <span class="badge badge-warning">Manejador</span> = Gestiona todas las reservas y exporta |
                <span class="badge badge-primary">Usuario</span> = Solo sus propias reservas
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u):
                            $roleInfo = $roleLabels[$u['role']] ?? ['label' => $u['role'], 'class' => 'badge-secondary'];
                            $userAvatar = getAvatarUrl($u['email']);
                            ?>
                            <tr>
                                <td style="width: 50px;">
                                    <img src="<?= $userAvatar ?>" alt="Avatar"
                                        style="width: 36px; height: 36px; border-radius: 50%;">
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge <?= $roleInfo['class'] ?>">
                                        <?= $roleInfo['label'] ?>
                                    </span>
                                </td>
                                <td><?= $u['created_at'] ? date('d/m/Y', strtotime($u['created_at'])) : '-' ?></td>
                                <td class="table-actions">
                                    <a href="/users/edit/<?= $u['id'] ?>" class="btn btn-secondary btn-icon" title="Editar">
                                        <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.84 1.83 3.75 3.75 1.84-1.83zM3 17.25V21h3.75l11-10.99-3.75-3.75L3 17.25z" />
                                        </svg>
                                    </a>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="/users/delete/<?= $u['id'] ?>" class="btn btn-danger btn-icon" title="Eliminar"
                                            onclick="return confirm('¿Eliminar este usuario?')">
                                            <svg class="icon" viewBox="0 0 16 16" fill="currentColor">
                                                <path
                                                    d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" />
                                                <path
                                                    d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        // Theme toggle function
        function toggleTheme() {
            const themeSwitch = document.getElementById('themeSwitch');
            const isDark = themeSwitch.checked;
            const newTheme = isDark ? 'dark' : 'light';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            const logo = document.getElementById('siteLogo');
            if (logo) {
                gsap.to(logo, {
                    opacity: 0, scale: 0.95, duration: 0.15,
                    onComplete: () => {
                        logo.src = isDark ? '/assets/logo-dark.png' : '/assets/logo.png';
                        gsap.to(logo, { opacity: 1, scale: 1, duration: 0.15 });
                    }
                });
            }
        }

        // User dropdown functionality
        const userDropdown = document.getElementById('userDropdown');
        const dropdownContent = document.getElementById('dropdownContent');
        const userAvatar = document.getElementById('userAvatar');
        let hoverTimeout;

        // Click to toggle
        userAvatar.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownContent.classList.toggle('show');
        });

        // Hover for 3 seconds to show
        userDropdown.addEventListener('mouseenter', () => {
            hoverTimeout = setTimeout(() => {
                dropdownContent.classList.add('show');
            }, 3000);
        });

        userDropdown.addEventListener('mouseleave', () => {
            clearTimeout(hoverTimeout);
            // Small delay before closing
            setTimeout(() => {
                if (!dropdownContent.matches(':hover')) {
                    dropdownContent.classList.remove('show');
                }
            }, 300);
        });

        // Click outside to close
        document.addEventListener('click', () => {
            dropdownContent.classList.remove('show');
        });

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function () {
            const theme = localStorage.getItem('theme') || 'light';
            const themeSwitch = document.getElementById('themeSwitch');
            const logo = document.getElementById('siteLogo');

            if (themeSwitch) themeSwitch.checked = theme === 'dark';
            if (logo && theme === 'dark') logo.src = '/assets/logo-dark.png';

            // GSAP Entrance Animations
            gsap.set('.header', { y: -20, opacity: 0 });
            gsap.set('.card', { y: 40, opacity: 0 });
            gsap.set('tbody tr', { x: -20, opacity: 0 });

            gsap.to('.header', { y: 0, opacity: 1, duration: 0.6, ease: 'power3.out' });
            gsap.to('.card', { y: 0, opacity: 1, duration: 0.6, ease: 'power2.out', delay: 0.2 });
            gsap.to('tbody tr', { x: 0, opacity: 1, duration: 0.4, stagger: 0.05, ease: 'power2.out', delay: 0.4 });
        });
    </script>
</body>

</html>