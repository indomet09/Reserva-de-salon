<?php

require_once __DIR__ . '/../Models/User.php';

class AdminController
{
    private $db;

    public function __construct()
    {
        // Require Admin Auth
        AuthController::requireAuth();
        if (!AuthController::isAdmin()) {
            header('Location: /');
            exit;
        }

        // Connect to Users DB (where settings table lives)
        $this->db = new PDO('sqlite:' . DB_USERS_PATH);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Render the settings page.
     * Fetches all current settings to populate the form.
     */
    public function index()
    {
        $settings = $this->getAllSettings();
        require __DIR__ . '/../../templates/admin/settings.php';
    }

    /**
     * Handle settings update request.
     * Validates CSRF, saves text inputs, and handles file uploads.
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/settings');
            exit;
        }

        if (!validateCsrfToken()) {
            die('Invalid CSRF Token');
        }

        // Added Dark Keys to validation
        $validKeys = ['app_name', 'primary_color', 'app_logo', 'login_logo', 'app_favicon', 'app_logo_dark', 'login_logo_dark'];

        // Update Text Settings
        foreach ($validKeys as $key) {
            if (isset($_POST[$key]) && !empty($_POST[$key])) {
                $this->saveSetting($key, trim($_POST[$key]));
            }
        }

        // Handle File Uploads (Light & Dark)
        $this->handleUpload('logo_file', 'app_logo');
        $this->handleUpload('login_logo_file', 'login_logo');
        $this->handleUpload('logo_dark_file', 'app_logo_dark');
        $this->handleUpload('login_logo_dark_file', 'login_logo_dark');
        $this->handleUpload('favicon_file', 'app_favicon');

        header('Location: /admin/settings?success=' . urlencode('Configuración actualizada correctamente'));
    }

    /**
     * Handle file upload for a specific setting.
     * Validates file type and moves it to public/uploads.
     *
     * @param string $fileInputName The name of the file input field.
     * @param string $settingKey The database key to store the file path.
     */
    private function handleUpload($fileInputName, $settingKey)
    {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileInputName];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'svg', 'ico'];

            if (in_array($ext, $allowed)) {
                // Ensure upload dir exists
                $uploadDir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Generate unique filename to prevent browser caching issues
                $filename = $settingKey . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $publicPath = '/uploads/' . $filename;
                    $this->saveSetting($settingKey, $publicPath);
                }
            }
        }
    }

    /**
     * Fetch all settings as an associative array.
     * @return array [key => value]
     */
    private function getAllSettings()
    {
        $stmt = $this->db->query("SELECT key, value FROM settings");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Save or update a single setting.
     * @param string $key
     * @param string $value
     */
    private function saveSetting($key, $value)
    {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$key, $value]);
    }
}
