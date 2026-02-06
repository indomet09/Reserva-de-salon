<?php
/**
 * Reservation Model - Modelo de Reservas
 * 
 * Maneja todas las operaciones de base de datos relacionadas
 * con reservas. Usa la base de datos reservas.db.
 * 
 * @author INDOMET
 * @version 2.1.0
 * @license MIT
 */

require_once __DIR__ . '/../../config/database.php';

class Reservation
{
    // Conexión a la base de datos de reservas
    private PDO $db;

    public function __construct()
    {
        // Usamos la base de datos específica de reservas
        $this->db = getReservasConnection();
    }

    /**
     * Obtiene todas las reservas
     * 
     * Ordenadas por fecha y hora de inicio.
     * Para la vista de administradores y manejadores.
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM reservations ORDER BY reservation_date ASC, start_time ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene las reservas de un usuario específico
     * 
     * Para la vista de usuarios regulares que solo
     * pueden ver sus propias reservas.
     */
    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM reservations WHERE user_id = ? ORDER BY reservation_date ASC, start_time ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene las reservas de un mes específico
     * 
     * Para mostrar el calendario mensual.
     * Recibe año y mes como números.
     */
    public function getByMonth(int $year, int $month): array
    {
        // Calculamos el primer y último día del mes
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $stmt = $this->db->prepare(
            "SELECT * FROM reservations WHERE reservation_date BETWEEN ? AND ? ORDER BY reservation_date ASC, start_time ASC"
        );
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene las reservas de una fecha específica
     * 
     * Para ver el detalle de un día en el calendario.
     */
    public function getByDate(string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM reservations WHERE reservation_date = ? ORDER BY start_time ASC"
        );
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    /**
     * Busca una reserva por su ID
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([$id]);
        $reservation = $stmt->fetch();
        return $reservation ?: null;
    }

    /**
     * Crea una nueva reserva
     * 
     * Genera un ID único y guarda todos los datos.
     * Retorna el ID de la reserva creada o null si falla.
     */
    public function create(array $data): ?string
    {
        // Generamos un ID único para la reserva
        $id = $this->generateId();

        $stmt = $this->db->prepare(
            "INSERT INTO reservations 
             (id, user_id, user_email, area, responsible, num_people, reservation_date, start_time, end_time, comment) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        try {
            $stmt->execute([
                $id,
                $data['user_id'],
                $data['user_email'] ?? '',
                $data['area'],
                $data['responsible'],
                $data['num_people'],
                $data['reservation_date'],
                $data['start_time'],
                $data['end_time'],
                $data['comment'] ?? ''
            ]);
            return $id;
        } catch (PDOException $e) {
            error_log("Error al crear reserva: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualiza una reserva existente
     */
    public function update(string $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE reservations 
             SET area = ?, responsible = ?, num_people = ?, 
                 reservation_date = ?, start_time = ?, end_time = ?, comment = ?
             WHERE id = ?"
        );

        return $stmt->execute([
            $data['area'],
            $data['responsible'],
            $data['num_people'],
            $data['reservation_date'],
            $data['start_time'],
            $data['end_time'],
            $data['comment'] ?? '',
            $id
        ]);
    }

    /**
     * Elimina una reserva por su ID
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM reservations WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Verifica si hay conflicto de horarios
     * 
     * Busca reservas que se superpongan con el horario dado.
     * Se puede excluir una reserva específica (para edición).
     * 
     * Retorna true si HAY conflicto, false si no hay.
     */
    public function hasTimeConflict(
        string $date,
        string $startTime,
        string $endTime,
        ?string $excludeId = null
    ): bool {
        // Buscamos reservas donde los horarios se superpongan
        $sql = "SELECT COUNT(*) FROM reservations 
                WHERE reservation_date = ? 
                AND start_time < ? 
                AND end_time > ?";

        $params = [$date, $endTime, $startTime];

        // Si estamos editando, excluimos la reserva actual
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene estadísticas de reservas
     * 
     * Para mostrar en el dashboard: total, hoy y próximas.
     */
    public function getStats(): array
    {
        $today = date('Y-m-d');

        $total = $this->db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

        $todayCount = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date = ?");
        $todayCount->execute([$today]);

        $upcoming = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date >= ?");
        $upcoming->execute([$today]);

        return [
            'total' => (int) $total,
            'today' => (int) $todayCount->fetchColumn(),
            'upcoming' => (int) $upcoming->fetchColumn()
        ];
    }

    /**
     * Genera un ID único para la reserva
     * 
     * Usa bytes aleatorios convertidos a hexadecimal.
     */
    private function generateId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
