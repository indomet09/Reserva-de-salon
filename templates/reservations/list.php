<?php
/**
 * Reservations List Template
 * 
 * Displays the main dashboard using a Calendar or Table view.
 * Features:
 * - Dynamic AJAX loading for Calendar navigation
 * - Theme-aware branding (Light/Dark mode logos)
 * - Role-based access control (Users limited to Calendar view)
 * - Modal for reservation details/editing
 */
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
$view = $_GET['view'] ?? 'calendar'; // 'table' or 'calendar' - default to calendar

// Get stats
$reservationModel = new Reservation();
$stats = $reservationModel->getStats();

// Calendar data
$currentMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
$currentYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

// Ensure valid month/year
if ($currentMonth < 1) {
    $currentMonth = 12;
    $currentYear--;
}
if ($currentMonth > 12) {
    $currentMonth = 1;
    $currentYear++;
}

$monthReservations = $reservationModel->getByMonth($currentYear, $currentMonth);

// Group reservations by date for calendar
$reservationsByDate = [];
foreach ($monthReservations as $r) {
    $date = $r['reservation_date'];
    if (!isset($reservationsByDate[$date])) {
        $reservationsByDate[$date] = [];
    }
    $reservationsByDate[$date][] = $r;
}

$currentRole = AuthController::getCurrentRole();
// Enforce 'calendar' view for regular users
if ($currentRole === 'user') {
    $view = 'calendar';
}

$roleLabels = [
    'admin' => ['name' => 'Administrador', 'badge' => 'badge-danger'],
    'manager' => ['name' => 'Manejador', 'badge' => 'badge-warning'],
    'user' => ['name' => 'Usuario', 'badge' => 'badge-primary']
];

$roleInfo = $roleLabels[$currentRole] ?? ['name' => 'Usuario', 'badge' => 'badge-secondary'];

// AJAX Request Handler
if (isset($_GET['ajax_calendar'])) {
    require __DIR__ . '/calendar_partial.php';
    exit;
}

// Generate avatar URL from email
function getAvatarUrl($email, $style = 'identicon')
{
    $email = $email ?? ''; // Handle null
    $hash = md5(strtolower(trim($email)));
    // Abstract styles only - no human figures
    $styles = ['identicon', 'bottts', 'shapes', 'rings', 'initials'];
    $randomStyle = $styles[abs(crc32($email)) % count($styles)];
    return "https://api.dicebear.com/7.x/{$randomStyle}/svg?seed={$hash}";
}
$currentUserAvatar = getAvatarUrl($_SESSION['email'] ?? $_SESSION['user_email'] ?? '');

// Month names in Spanish
$monthNames = [
    '',
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre'
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - <?= app_setting('app_name', APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" href="<?= app_setting('app_favicon', '/assets/logo.svg') ?>">
    <!-- GSAP Animation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <style>
        :root {
            --primary: <?= app_setting('primary_color', '#3b82f6') ?>;
        }
        /* Adjust darker shade slightly manually if needed, or rely on opacity */
    </style>
    <script>
        // Apply saved theme immediately to prevent flash
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
                    <img src="<?= app_setting('app_logo', '/assets/logo.svg') ?>" alt="Sistema" class="header-logo" id="siteLogo">
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
                    <?php if (AuthController::isAdmin()): ?>
                        <a href="/admin/settings" class="btn btn-secondary btn-sm" style="margin-right: 0.5rem;"><svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg> Configuración</a>
                        <a href="/users" class="btn btn-secondary btn-sm"><svg class="icon" viewBox="0 0 24 24">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg> Usuarios</a>
                    <?php endif; ?>

                    <!-- User Dropdown -->
                    <div class="user-dropdown" id="userDropdown">
                        <img src="<?= $currentUserAvatar ?>" alt="Avatar" class="user-avatar" id="userAvatar">
                        <div class="dropdown-content" id="dropdownContent">
                            <div class="dropdown-header">
                                <img src="<?= $currentUserAvatar ?>" alt="Avatar" class="dropdown-avatar">
                                <div class="dropdown-info">
                                    <div class="dropdown-email"><?= htmlspecialchars($_SESSION['email']) ?></div>
                                    <div class="dropdown-role">
                                        <span class="badge <?= $roleInfo['badge'] ?>"><?= $roleInfo['name'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-actions">
                                <a href="/logout" class="dropdown-btn logout">
                                    <svg class="icon" viewBox="0 0 24 24">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                        <polyline points="16 17 21 12 16 7" />
                                        <line x1="21" y1="12" x2="9" y2="12" />
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
            <div class="alert alert-error"><svg class="icon" viewBox="0 0 24 24">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                    <line x1="12" y1="9" x2="12" y2="13" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><svg class="icon" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12" />
                </svg> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Stats Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Reservas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['today'] ?></div>
                <div class="stat-label">Hoy</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['upcoming'] ?></div>
                <div class="stat-label">Próximas</div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card">
            <div class="page-header">
                <h2 class="page-title">
                    <?php if (AuthController::isAdmin() || AuthController::isManager()): ?>
                        <svg class="icon" viewBox="0 0 24 24">
                            <line x1="8" y1="6" x2="21" y2="6" />
                            <line x1="8" y1="12" x2="21" y2="12" />
                            <line x1="8" y1="18" x2="21" y2="18" />
                            <line x1="3" y1="6" x2="3.01" y2="6" />
                            <line x1="3" y1="12" x2="3.01" y2="12" />
                            <line x1="3" y1="18" x2="3.01" y2="18" />
                        </svg> Todas las Reservas
                    <?php else: ?>
                        <svg class="icon" viewBox="0 0 24 24">
                            <line x1="8" y1="6" x2="21" y2="6" />
                            <line x1="8" y1="12" x2="21" y2="12" />
                            <line x1="8" y1="18" x2="21" y2="18" />
                            <line x1="3" y1="6" x2="3.01" y2="6" />
                            <line x1="3" y1="12" x2="3.01" y2="12" />
                            <line x1="3" y1="18" x2="3.01" y2="18" />
                        </svg> Mis Reservas
                    <?php endif; ?>
                </h2>
                <div class="action-buttons">
                    <!-- View Switch (Admin/Manager only) -->
                     <?php if ($currentRole !== 'user'): ?>
                    <div class="view-switch">
                        <a href="?view=table" class="view-switch-option <?= $view === 'table' ? 'active' : '' ?>">
                            <svg class="icon" viewBox="0 0 24 24">
                                <line x1="8" y1="6" x2="21" y2="6" />
                                <line x1="8" y1="12" x2="21" y2="12" />
                                <line x1="8" y1="18" x2="21" y2="18" />
                                <line x1="3" y1="6" x2="3.01" y2="6" />
                                <line x1="3" y1="12" x2="3.01" y2="12" />
                                <line x1="3" y1="18" x2="3.01" y2="18" />
                            </svg>
                            Tabla
                        </a>
                        <a href="?view=calendar" class="view-switch-option <?= $view === 'calendar' ? 'active' : '' ?>">
                            <svg class="icon" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                            Calendario
                        </a>
                    </div>
                    <?php endif; ?>
                    <a href="/reservations/new" class="btn btn-primary"><svg class="icon" viewBox="0 0 24 24">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg> Nueva Reserva</a>
                    <?php if (AuthController::canExport()): ?>
                        <a href="/reservations/export" class="btn btn-secondary"><svg class="icon" viewBox="0 0 24 24">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg> Exportar</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($view === 'calendar'): ?>
                <!-- CALENDAR VIEW -->
                <div id="calendarContainer">
                    <?php require __DIR__ . '/calendar_partial.php'; ?>
                </div>
            <?php else: ?>
                <!-- TABLE VIEW -->
                <?php if (empty($reservations)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><svg class="icon" viewBox="0 0 24 24" fill="currentColor" style="width:48px;height:48px;"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg></div>
                        <h3>No hay reservas</h3>
                        <p>Comienza creando tu primera reserva</p>
                        <a href="/reservations/new" class="btn btn-primary"><svg class="icon" viewBox="0 0 16 16" fill="currentColor"><path d="M10 1H6V6L1 6V10H6V15H10V10H15V6L10 6V1Z"/></svg> Nueva Reserva</a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table" id="reservationsTable">
                            <thead>
                                <tr>
                                    <th>Área</th>
                                    <th>Responsable</th>
                                    <th>Personas</th>
                                    <th>Fecha</th>
                                    <th>Hora Inicio</th>
                                    <th>Hora Fin</th>
                                    <th>Comentario</th>
                                    <th>Fecha Solicitud</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $r):
                                    $isPast = strtotime($r['reservation_date']) < strtotime('today');
                                    $isToday = $r['reservation_date'] === date('Y-m-d');
                                    $rowClass = $isPast ? 'row-past' : ($isToday ? 'row-today' : '');
                                    $canModify = AuthController::canModifyReservation($r);
                                    $canDelete = AuthController::canDeleteReservation($r);
                                    $createdAt = !empty($r['created_at']) ? date('d/m/Y', strtotime($r['created_at'])) : 'N/A';
                                    ?>
                                    <tr class="<?= $rowClass ?>" onclick="openReservationModal('<?= $r['id'] ?>')"
                                        style="cursor: pointer;">
                                        <td><?= htmlspecialchars($r['area']) ?></td>
                                        <td><?= htmlspecialchars($r['responsible']) ?></td>
                                        <td class="text-center"><?= $r['num_people'] ?></td>
                                        <td>
                                            <?php if ($isToday): ?>
                                                <span class="badge badge-success">HOY</span>
                                            <?php endif; ?>
                                            <?= date('d/m/Y', strtotime($r['reservation_date'])) ?>
                                        </td>
                                        <td><?= substr($r['start_time'], 0, 5) ?></td>
                                        <td><?= substr($r['end_time'], 0, 5) ?></td>
                                        <td class="text-small"><?= htmlspecialchars($r['comment'] ?? '') ?></td>
                                        <td class="text-small text-muted"><?= $createdAt ?></td>
                                        <td class="table-actions">
                                            <?php if ($canModify): ?>
                                                <a href="/reservations/edit/<?= $r['id'] ?>" class="btn btn-secondary btn-icon"
                                                    title="Editar"><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.84 1.83 3.75 3.75 1.84-1.83zM3 17.25V21h3.75l11-10.99-3.75-3.75L3 17.25z"/></svg></a>
                                            <?php endif; ?>
                                            <?php if ($canDelete): ?>
                                                <a href="/reservations/delete/<?= $r['id'] ?>" class="btn btn-danger btn-icon"
                                                    title="Eliminar" onclick="return confirm('¿Eliminar esta reserva?')"><svg class="icon" viewBox="0 0 16 16" fill="currentColor"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> <?= APP_NAME ?> - Sistema de Reservas v<?= APP_VERSION ?></p>
        </div>
    </footer>

    <!-- Reservation Detail Modal -->
    <div id="reservationModal" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title">📅 Detalle de Reserva</h3>
                <button class="modal-close" onclick="closeModal()" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-row">
                    <span class="detail-label">Área:</span>
                    <span class="detail-value highlight" id="modal-area"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Responsable:</span>
                    <span class="detail-value" id="modal-responsible"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fecha:</span>
                    <span class="detail-value" id="modal-date"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Horario:</span>
                    <span class="detail-value" id="modal-time"></span>
                </div>

                <!-- Admin/Manager only fields -->
                <?php if (AuthController::isAdmin() || AuthController::isManager()): ?>
                    <div class="detail-row admin-only">
                        <span class="detail-label">Personas:</span>
                        <span class="detail-value" id="modal-people"></span>
                    </div>
                    <div class="detail-row admin-only">
                        <span class="detail-label">Comentario:</span>
                        <span class="detail-value" id="modal-comment"></span>
                    </div>
                    <div class="detail-row admin-only">
                        <span class="detail-label">Solicitado:</span>
                        <span class="detail-value" id="modal-created"></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer" id="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">Cerrar</button>
            </div>
        </div>
    </div>

    <script src="/js/app.js"></script>
    <script>
        // Brand Configuration (Injected from PHP)
        const brandConfig = {
            logoLight: "<?= app_setting('app_logo', '/assets/logo.svg') ?>",
            logoDark: "<?= app_setting('app_logo_dark') ?: app_setting('app_logo', '/assets/logo.svg') ?>"
        };

        // Store current user role for JS
        const userRole = '<?= AuthController::getCurrentRole() ?>';
        const currentUserId = <?= AuthController::getCurrentUserId() ?>;

        // All reservations data for modal (encode as JSON)
        const reservationsData = <?= json_encode(
            array_merge($reservations ?? [], $monthReservations ?? []),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;

        function openReservationModal(reservationId) {
            const r = reservationsData.find(res => res.id === reservationId);
            if (!r) return;

            // Basic info (visible to all)
            document.getElementById('modal-area').textContent = r.area;
            document.getElementById('modal-responsible').textContent = r.responsible;
            document.getElementById('modal-date').textContent = formatDate(r.reservation_date);
            document.getElementById('modal-time').textContent = r.start_time.substring(0, 5) + ' - ' + r.end_time.substring(0, 5);

            // Admin/Manager only fields
            if (userRole === 'admin' || userRole === 'manager') {
                document.getElementById('modal-people').textContent = r.num_people + ' personas';
                document.getElementById('modal-comment').textContent = r.comment || 'Sin comentarios';
                document.getElementById('modal-created').textContent = r.created_at ? formatDate(r.created_at.split(' ')[0]) : 'N/A';
            }

            // Show edit button if user can modify
            const actionsDiv = document.getElementById('modal-actions');
            const canModify = (userRole === 'admin' || userRole === 'manager' || r.user_id == currentUserId);

            // Reset actions
            actionsDiv.innerHTML = '<button class="btn btn-secondary" onclick="closeModal()">Cerrar</button>';

            if (canModify) {
                const editBtn = document.createElement('a');
                editBtn.href = '/reservations/edit/' + r.id;
                editBtn.className = 'btn btn-primary';
                editBtn.innerHTML = '✏️ Editar';
                actionsDiv.appendChild(editBtn);
            }

            document.getElementById('reservationModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('reservationModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function formatDate(dateStr) {
            const parts = dateStr.split('-');
            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });

        // Theme toggle function
        function toggleTheme() {
            const themeSwitch = document.getElementById('themeSwitch');
            const isDark = themeSwitch.checked;
            const newTheme = isDark ? 'dark' : 'light';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // Switch logo with smooth animation
            const logo = document.getElementById('siteLogo');
            if (logo) {
                const newSrc = isDark ? brandConfig.logoDark : brandConfig.logoLight;
                
                // Only animate if src is different
                if (logo.src.replace(window.location.origin, '') !== newSrc) {
                    gsap.to(logo, {
                        opacity: 0,
                        scale: 0.95,
                        duration: 0.15,
                        onComplete: () => {
                            logo.src = newSrc;
                            gsap.to(logo, { opacity: 1, scale: 1, duration: 0.15 });
                        }
                    });
                }
            }
        }

        // Apply correct theme and logo on page load with GSAP animations
        document.addEventListener('DOMContentLoaded', function () {
            const theme = localStorage.getItem('theme') || 'light';
            const themeSwitch = document.getElementById('themeSwitch');
            const logo = document.getElementById('siteLogo');

            // Sync switch state
            if (themeSwitch) {
                themeSwitch.checked = theme === 'dark';
            }

            // Sync logo
            if (logo) {
                logo.src = (theme === 'dark') ? brandConfig.logoDark : brandConfig.logoLight;
            }

            // User dropdown functionality
            const userDropdown = document.getElementById('userDropdown');
            const dropdownContent = document.getElementById('dropdownContent');
            const userAvatar = document.getElementById('userAvatar');
            let hoverTimeout;

            if (userAvatar && dropdownContent) {
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
            }

            // AJAX Calendar Navigation
            document.addEventListener('click', function(e) {
                const link = e.target.closest('.ajax-calendar-nav');
                if (link) {
                    e.preventDefault();
                    e.stopPropagation(); // Stop propagation immediately

                    const url = link.href + '&ajax_calendar=1';
                    const container = document.getElementById('calendarContainer');
                    if(!container) return; // safety check

                    // Add loading state
                    gsap.to(container, { opacity: 0.5, duration: 0.2 });

                    fetch(url)
                        .then(response => response.text())
                        .then(html => {
                            container.innerHTML = html;
                            // Animate in
                            gsap.fromTo(container, 
                                { opacity: 0.5, y: 10 }, 
                                { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out' }
                            );
                            
                            // Re-animate cells if desired
                            gsap.fromTo('.calendar-cell', 
                                { scale: 0.95, opacity: 0 }, 
                                { scale: 1, opacity: 1, duration: 0.2, stagger: 0.01, ease: 'back.out(1.2)' }
                            );
                        })
                        .catch(err => {
                            console.error('Calendar load failed', err);
                            window.location.href = link.href; // Fallback
                        });
                }
            });
        });
    </script>
</body>

</html>