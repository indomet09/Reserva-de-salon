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
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-title">👥 <?= $pageTitle ?></div>
                <nav class="header-nav">
                    <a href="/users" class="btn btn-secondary btn-sm">← Volver</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form action="<?= $formAction ?>" method="POST">
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
                    <a href="/users" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <?= $editMode ? '💾 Guardar Cambios' : '✅ Crear Usuario' ?>
                    </button>
                </div>
            </form>
        </div>
    </main>
    
    <script src="/js/app.js"></script>
</body>
</html>
