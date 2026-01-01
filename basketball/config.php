<?php
// config.php - Основна конфігурація системи

// Налаштування бази даних
define('DB_HOST', 'localhost');
define('DB_NAME', 'basketball_learning');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Налаштування сесії
define('SESSION_NAME', 'basketball_session');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 днів

// Шляхи
define('BASE_URL', 'http://localhost/basketball');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ASSETS_DIR', __DIR__ . '/assets/');

// Налаштування безпеки
define('PASSWORD_MIN_LENGTH', 6);
define('HASH_COST', 10);

// Налаштування пагінації
define('ITEMS_PER_PAGE', 12);

// Клас для роботи з базою даних
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Помилка підключення до бази даних: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Запобігання клонуванню
    private function __clone() {}
    
    // Запобігання десеріалізації
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Функції для роботи з сесією
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Встановіть 1 для HTTPS
        session_name(SESSION_NAME);
        session_start();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, email, first_name, last_name, role, avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Функції безпеки
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Функції для роботи з повідомленнями
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Функція для форматування цін
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' грн';
}

// Функція для форматування дат
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}

// Функція для завантаження файлів
function uploadFile($file, $targetDir = 'uploads/', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Некоректний файл'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Помилка завантаження файлу'];
    }
    
    if ($file['size'] > 5000000) { // 5MB
        return ['success' => false, 'message' => 'Файл занадто великий (максимум 5MB)'];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'Недозволений тип файлу'];
    }
    
    $fileName = uniqid() . '_' . time() . '.' . $ext;
    $targetPath = UPLOAD_DIR . $targetDir . $fileName;
    
    if (!file_exists(dirname($targetPath))) {
        mkdir(dirname($targetPath), 0777, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Не вдалося зберегти файл'];
    }
    
    return ['success' => true, 'filename' => $fileName, 'path' => $targetPath];
}

// Ініціалізація сесії
startSession();
?>