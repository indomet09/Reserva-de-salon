<?php
/**
 * ReservationValidator Service
 * Validates reservation data including time conflict detection
 */

require_once __DIR__ . '/../Models/Reservation.php';

class ReservationValidator
{
    private Reservation $reservationModel;
    private array $errors = [];

    public function __construct()
    {
        $this->reservationModel = new Reservation();
    }

    /**
     * Validate reservation data
     */
    public function validate(array $data, ?string $excludeId = null): bool
    {
        $this->errors = [];

        // Required fields
        $requiredFields = ['area', 'responsible', 'num_people', 'reservation_date', 'start_time', 'end_time'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->errors[$field] = "El campo $field es obligatorio";
            }
        }

        if (!empty($this->errors)) {
            return false;
        }

        // Validate date is not in the past
        $today = date('Y-m-d');
        if ($data['reservation_date'] < $today) {
            $this->errors['reservation_date'] = 'No se pueden crear reservas en fechas pasadas';
        }

        // Validate times
        if ($data['start_time'] >= $data['end_time']) {
            $this->errors['end_time'] = 'La hora de fin debe ser posterior a la hora de inicio';
        }

        // Validate number of people
        if ((int) $data['num_people'] <= 0) {
            $this->errors['num_people'] = 'El número de personas debe ser mayor a 0';
        }

        // Check for time conflicts (the critical validation!)
        if (empty($this->errors)) {
            $hasConflict = $this->reservationModel->hasTimeConflict(
                $data['reservation_date'],
                $data['start_time'],
                $data['end_time'],
                $excludeId
            );

            if ($hasConflict) {
                $this->errors['time_conflict'] =
                    '¡Error! Ya existe una reserva que se cruza con el horario seleccionado. ' .
                    'Por favor elija un horario diferente.';
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Check if specific reservation times overlap
     * This is the core anti-overlap logic:
     * Two time ranges overlap if: start1 < end2 AND end1 > start2
     * 
     * Example:
     * Existing: 12:00 - 15:00
     * Request:  14:00 - 14:30  → CONFLICT (14:00 < 15:00 AND 14:30 > 12:00)
     * Request:  15:00 - 16:00  → OK (15:00 is NOT < 15:00)
     * Request:  10:00 - 12:00  → OK (12:00 is NOT > 12:00)
     */
    public function timesOverlap(
        string $start1,
        string $end1,
        string $start2,
        string $end2
    ): bool {
        return $start1 < $end2 && $end1 > $start2;
    }
}
