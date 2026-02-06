<?php
/**
 * Reservation Form Template (Create/Edit)
 */
$error = $_GET['error'] ?? null;
$editMode = isset($editMode) && $editMode === true;
$formAction = $editMode ? "reservations/edit/{$reservation['id']}" : 'reservations/create';
$pageTitle = $editMode ? 'Editar Reserva' : 'Nueva Reserva';

// Pre-fill form if editing
$area = $editMode ? htmlspecialchars($reservation['area']) : '';
$responsible = $editMode ? htmlspecialchars($reservation['responsible']) : '';
$numPeople = $editMode ? htmlspecialchars($reservation['num_people']) : '';
$reservationDate = $editMode ? htmlspecialchars($reservation['reservation_date']) : '';
$startTime = $editMode ? htmlspecialchars($reservation['start_time']) : '';
$endTime = $editMode ? htmlspecialchars($reservation['end_time']) : '';
$comment = $editMode ? htmlspecialchars($reservation['comment']) : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $pageTitle ?> - Sistema de Reservas">
    <title>
        <?= $pageTitle ?> - Reserva de Salón Julio Rib Santa María
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📅</text></svg>">
</head>

<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        width="28" height="28">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Reserva de Salón Julio Rib Santa María
                </div>
                <nav class="header-nav">
                    <span class="header-user">
                        👤
                        <?= htmlspecialchars($_SESSION['email'] ?? $_SESSION['user_email'] ?? 'Usuario') ?>
                    </span>
                    <a href="/logout" class="btn btn-secondary btn-sm">
                        🔒 Cerrar sesión
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <div class="card-header">
                <h1 class="card-title">
                    <?= $editMode ? '✏️' : '➕' ?>
                    <?= $pageTitle ?>
                </h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <span>⚠️</span>
                    <span>
                        <?= htmlspecialchars($error) ?>
                    </span>
                </div>
            <?php endif; ?>

            <form action="/<?= $formAction ?>" method="POST" id="reservationForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

                <div class="form-group">
                    <label for="area" class="form-label required">Área / Departamento</label>
                    <input type="text" id="area" name="area" class="form-input"
                        placeholder="Ej: Departamento de Recursos Humanos" value="<?= $area ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="responsible" class="form-label required">Responsable</label>
                    <input type="text" id="responsible" name="responsible" class="form-input"
                        placeholder="Nombre del responsable del evento" value="<?= $responsible ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="num_people" class="form-label required">Cantidad de Personas</label>
                        <input type="number" id="num_people" name="num_people" class="form-input" placeholder="25"
                            value="<?= $numPeople ?>" min="1" max="500" required>
                    </div>

                    <div class="form-group">
                        <label for="reservation_date" class="form-label required">Fecha</label>
                        <input type="date" id="reservation_date" name="reservation_date" class="form-input"
                            value="<?= $reservationDate ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time" class="form-label required">Hora de Inicio</label>
                        <input type="time" id="start_time" name="start_time" class="form-input"
                            value="<?= $startTime ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="end_time" class="form-label required">Hora de Fin</label>
                        <input type="time" id="end_time" name="end_time" class="form-input" value="<?= $endTime ?>"
                            required>
                        <p class="form-hint">Debe ser posterior a la hora de inicio</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="comment" class="form-label">Comentario / Requerimientos</label>
                    <textarea id="comment" name="comment" class="form-textarea"
                        placeholder="Ej: Monitor, café, agua, proyector, etc." rows="3"><?= $comment ?></textarea>
                </div>

                <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                    <span>ℹ️</span>
                    <span><strong>Importante:</strong> No se puede reservar en horarios que ya están ocupados. El
                        sistema validará automáticamente la disponibilidad.</span>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="/reservations" class="btn btn-secondary">
                        ← Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <?= $editMode ? '💾 Guardar Cambios' : '✅ Crear Reserva' ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="/js/app.js"></script>
    <script>
        // Client-side time validation
        document.getElementById('reservationForm').addEventListener('submit', function (e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;

            if (startTime >= endTime) {
                e.preventDefault();
                alert('La hora de fin debe ser posterior a la hora de inicio');
                document.getElementById('end_time').focus();
            }
        });
    </script>
</body>

</html>