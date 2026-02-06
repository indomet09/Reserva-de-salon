<?php
/**
 * ReservationController - Controlador de Reservas
 * 
 * Maneja todas las operaciones CRUD de reservas:
 * listar, crear, editar, eliminar y exportar.
 * 
 * @author INDOMET
 * @version 2.1.0
 * @license MIT
 */

require_once __DIR__ . '/../Models/Reservation.php';
require_once __DIR__ . '/../Services/ReservationValidator.php';
require_once __DIR__ . '/AuthController.php';

class ReservationController
{
    // Modelo para operaciones con la base de datos
    private Reservation $reservationModel;

    // Validador para verificar datos de reservas
    private ReservationValidator $validator;

    public function __construct()
    {
        $this->reservationModel = new Reservation();
        $this->validator = new ReservationValidator();
    }

    /**
     * Lista las reservas según el rol del usuario
     * 
     * Admin y Manager ven todas las reservas.
     * Los usuarios regulares solo ven las suyas.
     */
    public function index(): array
    {
        AuthController::requireAuth();

        if (AuthController::isAdmin() || AuthController::isManager()) {
            // Administradores y manejadores ven todo
            return $this->reservationModel->getAll();
        } else {
            // Usuarios regulares solo ven sus reservas
            return $this->reservationModel->getByUserId(AuthController::getCurrentUserId());
        }
    }

    /**
     * Crea una nueva reserva
     * 
     * Recibe los datos del formulario, los valida y guarda.
     * Verifica que no haya conflictos de horario.
     */
    public function create(): void
    {
        AuthController::requireAuth();

        // Solo aceptamos peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/reservations/new');
            return;
        }

        // Armamos el array con los datos de la reserva
        $data = [
            'user_id' => AuthController::getCurrentUserId(),
            'user_email' => AuthController::getCurrentUserEmail(),
            'area' => trim($_POST['area'] ?? ''),
            'responsible' => trim($_POST['responsible'] ?? ''),
            'num_people' => (int) ($_POST['num_people'] ?? 0),
            'reservation_date' => $_POST['reservation_date'] ?? '',
            'start_time' => $_POST['start_time'] ?? '',
            'end_time' => $_POST['end_time'] ?? '',
            'comment' => trim($_POST['comment'] ?? '')
        ];

        // Validamos los datos antes de guardar
        if (!$this->validator->validate($data)) {
            $error = $this->validator->getFirstError();
            $this->redirectWithError('/reservations/new', $error);
            return;
        }

        // Intentamos crear la reserva
        $reservationId = $this->reservationModel->create($data);

        if ($reservationId) {
            $this->redirectWithSuccess('/reservations', '¡Reserva creada exitosamente!');
        } else {
            $this->redirectWithError('/reservations/new', 'Error al crear la reserva');
        }
    }

    /**
     * Actualiza una reserva existente
     * 
     * Primero verifica que el usuario tenga permisos,
     * luego valida los datos y finalmente actualiza.
     */
    public function update(string $id): void
    {
        AuthController::requireAuth();

        // Buscamos la reserva para verificar permisos
        $reservation = $this->reservationModel->findById($id);

        if (!$reservation) {
            $this->redirectWithError('/reservations', 'Reserva no encontrada');
            return;
        }

        // Verificamos que el usuario pueda modificar esta reserva
        if (!AuthController::canModifyReservation($reservation)) {
            $this->redirectWithError('/reservations', 'No tiene permisos para modificar esta reserva');
            return;
        }

        // Preparamos los datos actualizados
        $data = [
            'area' => trim($_POST['area'] ?? ''),
            'responsible' => trim($_POST['responsible'] ?? ''),
            'num_people' => (int) ($_POST['num_people'] ?? 0),
            'reservation_date' => $_POST['reservation_date'] ?? '',
            'start_time' => $_POST['start_time'] ?? '',
            'end_time' => $_POST['end_time'] ?? '',
            'comment' => trim($_POST['comment'] ?? '')
        ];

        // Validamos los datos (pasamos el ID para excluir esta reserva del check de conflictos)
        if (!$this->validator->validate($data, $id)) {
            $error = $this->validator->getFirstError();
            $this->redirectWithError("/reservations/edit/$id", $error);
            return;
        }

        if ($this->reservationModel->update($id, $data)) {
            $this->redirectWithSuccess('/reservations', '¡Reserva actualizada exitosamente!');
        } else {
            $this->redirectWithError("/reservations/edit/$id", 'Error al actualizar la reserva');
        }
    }

    /**
     * Elimina una reserva
     * 
     * Solo se permite si el usuario tiene permisos sobre esa reserva.
     */
    public function delete(string $id): void
    {
        AuthController::requireAuth();

        $reservation = $this->reservationModel->findById($id);

        if (!$reservation) {
            $this->redirectWithError('/reservations', 'Reserva no encontrada');
            return;
        }

        // Verificamos permisos antes de eliminar
        if (!AuthController::canDeleteReservation($reservation)) {
            $this->redirectWithError('/reservations', 'No tiene permisos para eliminar esta reserva');
            return;
        }

        if ($this->reservationModel->delete($id)) {
            $this->redirectWithSuccess('/reservations', 'Reserva eliminada exitosamente');
        } else {
            $this->redirectWithError('/reservations', 'Error al eliminar la reserva');
        }
    }

    /**
     * Exporta todas las reservas a un archivo CSV
     * 
     * Solo admin y manager pueden exportar.
     * El archivo se genera con codificación UTF-8 compatible con Excel.
     */
    public function exportExcel(): void
    {
        AuthController::requireAuth();

        // Verificamos permisos de exportación
        if (!AuthController::canExport()) {
            $this->redirectWithError('/reservations', 'No tiene permisos para exportar');
            return;
        }

        // Obtenemos todas las reservas
        $reservations = $this->reservationModel->getAll();

        // Configuramos los headers para descarga de CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reservas_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        // Agregamos BOM para que Excel reconozca los caracteres especiales
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Escribimos los encabezados
        fputcsv($output, ['Área', 'Responsable', 'Personas', 'Fecha', 'Hora Inicio', 'Hora Fin', 'Comentario', 'Fecha Solicitud']);

        // Escribimos cada reserva
        foreach ($reservations as $r) {
            $createdAt = !empty($r['created_at']) ? date('d/m/Y', strtotime($r['created_at'])) : 'N/A';
            fputcsv($output, [
                $r['area'],
                $r['responsible'],
                $r['num_people'],
                $r['reservation_date'],
                $r['start_time'],
                $r['end_time'],
                $r['comment'] ?? '',
                $createdAt
            ]);
        }

        fclose($output);
        exit;
    }

    // ============================================
    // Métodos auxiliares de redirección
    // ============================================

    private function redirect(string $path): void
    {
        header("Location: $path");
        exit;
    }

    private function redirectWithError(string $path, string $message): void
    {
        header("Location: $path?error=" . urlencode($message));
        exit;
    }

    private function redirectWithSuccess(string $path, string $message): void
    {
        header("Location: $path?success=" . urlencode($message));
        exit;
    }
}
