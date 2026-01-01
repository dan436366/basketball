<?php
require_once 'config.php';

// Якщо користувач вже увійшов, перенаправляємо на головну
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'role' => $_POST['role'] ?? 'student'
    ];
    
    // Валідація
    if (empty($formData['email']) || !validateEmail($formData['email'])) {
        $errors[] = 'Введіть коректний email';
    }
    
    if (empty($formData['first_name'])) {
        $errors[] = 'Введіть ім\'я';
    }
    
    if (empty($formData['last_name'])) {
        $errors[] = 'Введіть прізвище';
    }
    
    if (strlen($formData['password']) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Пароль повинен містити щонайменше ' . PASSWORD_MIN_LENGTH . ' символів';
    }
    
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Паролі не співпадають';
    }
    
    // Перевірка, чи email вже зайнятий
    if (empty($errors)) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$formData['email']]);
        
        if ($stmt->fetch()) {
            $errors[] = 'Користувач з таким email вже існує';
        }
    }
    
    // Якщо немає помилок, реєструємо користувача
    if (empty($errors)) {
        $hashedPassword = hashPassword($formData['password']);
        
        try {
            $stmt = $db->prepare("
                INSERT INTO users (email, password, first_name, last_name, phone, role)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $formData['email'],
                $hashedPassword,
                $formData['first_name'],
                $formData['last_name'],
                $formData['phone'],
                $formData['role']
            ]);
            
            setFlashMessage('success', 'Реєстрація успішна! Тепер ви можете увійти.');
            header('Location: login.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Помилка реєстрації. Спробуйте пізніше.';
        }
    }
}

$pageTitle = 'Реєстрація';
include 'includes/header.php';
?>

<style>
    .auth-container {
        max-width: 500px;
        margin: 60px auto;
        padding: 40px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    }
    
    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .auth-header h2 {
        color: #333;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .auth-header p {
        color: #666;
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    
    .form-control, .form-select {
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }
    
    .btn-register {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        margin-top: 20px;
        transition: all 0.3s;
    }
    
    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 20px;
        color: #666;
    }
    
    .auth-footer a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }
    
    .auth-footer a:hover {
        text-decoration: underline;
    }
    
    .error-list {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .error-list ul {
        margin: 0;
        padding-left: 20px;
        color: #721c24;
    }
</style>

<div class="container">
    <div class="auth-container">
        <div class="auth-header">
            <h2>🏀 Реєстрація</h2>
            <p>Створіть акаунт для доступу до курсів</p>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="error-list">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ім'я *</label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?= htmlspecialchars($formData['first_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Прізвище *</label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?= htmlspecialchars($formData['last_name'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Телефон</label>
                <input type="tel" name="phone" class="form-control" 
                       value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" 
                       placeholder="+380 (XX) XXX-XX-XX">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Я хочу бути *</label>
                <select name="role" class="form-select" required>
                    <option value="student" <?= ($formData['role'] ?? '') === 'student' ? 'selected' : '' ?>>
                        Учнем (навчатись баскетболу)
                    </option>
                    <option value="trainer" <?= ($formData['role'] ?? '') === 'trainer' ? 'selected' : '' ?>>
                        Тренером (викладати курси)
                    </option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Пароль *</label>
                <input type="password" name="password" class="form-control" 
                       placeholder="Мінімум <?= PASSWORD_MIN_LENGTH ?> символів" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Підтвердження паролю *</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-register">Зареєструватись</button>
        </form>
        
        <div class="auth-footer">
            Вже маєте акаунт? <a href="login.php">Увійти</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>