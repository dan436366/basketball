<?php
require_once 'config.php';

// Якщо користувач вже увійшов, перенаправляємо
if (isLoggedIn()) {
    $user = getCurrentUser();
    
    if ($user['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($user['role'] === 'trainer') {
        header('Location: trainer/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Валідація
    if (empty($email) || !validateEmail($email)) {
        $errors[] = 'Введіть коректний email';
    }
    
    if (empty($password)) {
        $errors[] = 'Введіть пароль';
    }
    
    // Перевірка облікових даних
    if (empty($errors)) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Успішний вхід
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            
            setFlashMessage('success', 'Ласкаво просимо, ' . htmlspecialchars($user['first_name']) . '!');
            
            // Перенаправлення в залежності від ролі
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($user['role'] === 'trainer') {
                header('Location: trainer/dashboard.php');
            } else {
                header('Location: student/dashboard.php');
            }
            exit;
        } else {
            $errors[] = 'Невірний email або пароль';
        }
    }
}

$pageTitle = 'Вхід';
include 'includes/header.php';
?>

<style>
    .auth-container {
        max-width: 450px;
        margin: 80px auto;
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
    
    .form-control {
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }
    
    .btn-login {
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
    
    .btn-login:hover {
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
    
    .demo-accounts {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
        font-size: 0.9rem;
    }
    
    .demo-accounts h6 {
        color: #1976d2;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .demo-accounts p {
        margin: 5px 0;
        color: #0d47a1;
    }
    
    .demo-accounts strong {
        color: #0d47a1;
    }
</style>

<div class="container">
    <div class="auth-container">
        <div class="auth-header">
            <h2>🏀 Вхід</h2>
            <p>Увійдіть в свій акаунт</p>
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
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-login">Увійти</button>
        </form>
        
        <div class="auth-footer">
            Не маєте акаунту? <a href="register.php">Зареєструватись</a>
        </div>
        
        <div class="demo-accounts">
            <h6>Тестові акаунти:</h6>
            <p><strong>Адмін:</strong> admin@basketball.com / admin123</p>
            <p><strong>Тренер:</strong> trainer1@basketball.com / trainer123</p>
            <p><strong>Учень:</strong> student@basketball.com / student123</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>