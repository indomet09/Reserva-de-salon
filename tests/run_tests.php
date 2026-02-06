<?php
/**
 * Suite de Pruebas Unitarias e Integración (Nativo)
 * 
 * Cumplimiento NORTIC A6:2016 - Sección 2.02 (Desarrollo)
 * Ejecuta pruebas de lógica de negocio y flujo de datos.
 * 
 * Uso: php tests/run_tests.php
 */

// Configuración de entorno de pruebas
define('TEST_MODE', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Models/Reservation.php';

// No namespaces defined in source files
// use App\Models\User;
// use App\Models\Reservation;

class TestRunner
{
    private $passed = 0;
    private $failed = 0;
    private $pdoUsers;
    private $pdoReservas;

    public function __construct()
    {
        $this->pdoUsers = getUsersConnection();
        $this->pdoReservas = getReservasConnection();
        echo "=== Iniciando Suite de Pruebas NORTIC A6 ===\n\n";
    }

    public function assert($condition, $message)
    {
        if ($condition) {
            echo "✅ PASS: $message\n";
            $this->passed++;
        } else {
            echo "❌ FAIL: $message\n";
            $this->failed++;
        }
    }

    public function run()
    {
        $this->testDatabaseConnection();
        $this->testUserHashing();
        $this->testUserCreationAndAuth();
        $this->testReservationFlow();

        $this->report();
    }

    private function testDatabaseConnection()
    {
        echo "--- Pruebas de Infraestructura ---\n";
        $this->assert($this->pdoUsers instanceof PDO, "Conexión DB Usuarios activa");
        $this->assert($this->pdoReservas instanceof PDO, "Conexión DB Reservas activa");
    }

    private function testUserHashing()
    {
        echo "\n--- Pruebas Unitarias (Lógica) ---\n";
        $password = "secret123";
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->assert(password_verify($password, $hash), "Verificación de hash de contraseña");
        $this->assert(!password_verify("wrong", $hash), "Rechazo de contraseña incorrecta");
    }

    private function testUserCreationAndAuth()
    {
        echo "\n--- Pruebas de Integración (Usuarios) ---\n";
        $userModel = new User(); // Constructor handles connection internaly
        $testEmail = 'test_unit_' . time() . '@example.com';

        // 1. Crear usuario
        $userId = $userModel->create($testEmail, 'pass123', 'user');
        $this->assert($userId > 0, "Creación de usuario exitosa (ID: $userId)");

        // 2. Buscar usuario
        $user = $userModel->findByEmail($testEmail);
        $this->assert($user && $user['email'] === $testEmail, "Búsqueda de usuario por Email");

        // 3. Verificar credenciales (Simulación AuthController)
        $isValid = password_verify('pass123', $user['password_hash']); // Field is password_hash
        $this->assert($isValid, "Autenticación de usuario creado");

        // Limpieza
        $this->pdoUsers->exec("DELETE FROM users WHERE id = $userId");
    }

    private function testReservationFlow()
    {
        echo "\n--- Pruebas de Integración (Reservas) ---\n";
        $reservationModel = new Reservation(); // Constructor handles connection internaly

        // Datos de prueba
        $data = [
            'user_id' => 99999, // ID simulado
            'user_email' => 'tester@example.com',
            'area' => 'Sala de Pruebas',
            'responsible' => 'Bot Tester',
            'num_people' => 5,
            'reservation_date' => date('Y-m-d', strtotime('+1 day')),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'comment' => 'Test Unitario'
        ];

        // 1. Crear reserva
        try {
            $id = $reservationModel->create($data);
            $this->assert($id !== null, "Inserción de reserva sin errores (ID: $id)");
            // Store ID for cleanup and checks
            $data['id'] = $id;
        } catch (Exception $e) {
            $this->assert(false, "Error al crear reserva: " . $e->getMessage());
        }

        // 2. Verificar existencia
        $res = $reservationModel->findById($data['id']);
        $this->assert($res && $res['area'] === 'Sala de Pruebas', "Persistencia de datos verificada");

        // 3. Verificar conflicto (Mismo horario)
        $hasConflict = $reservationModel->hasTimeConflict(
            $data['reservation_date'],
            '08:30', // Solapa
            '09:30'
        );
        $this->assert($hasConflict === true, "Detección de conflictos de horario");

        // Limpieza
        if (isset($data['id'])) {
            $reservationModel->delete($data['id']);
        }
    }

    private function report()
    {
        echo "\n=== Resumen de Pruebas ===\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo "Pasaron: " . $this->passed . " ✅\n";
        echo "Fallaron: " . $this->failed . " ❌\n";

        if ($this->failed > 0) {
            exit(1);
        } else {
            exit(0);
        }
    }
}

// Ejecutar
$test = new TestRunner();
$test->run();
