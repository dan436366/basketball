<?php
require_once 'config.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

$errors = [];
$success = false;

// Отримання даних користувача
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Обробка оновлення профілю
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $experienceYears = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : null;
        
        if (empty($firstName)) {
            $errors[] = 'Введіть ім\'я';
        }
        
        if (empty($lastName)) {
            $errors[] = 'Введіть прізвище';
        }
        
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, phone = ?, bio = ?, experience_years = ?
                    WHERE id = ?
                ");
                $stmt->execute([$firstName, $lastName, $phone, $bio, $experienceYears, $userId]);
                
                setFlashMessage('success', 'Профіль успішно оновлено');
                header('Location: profile.php');
                exit;
                
            } catch (PDOException $e) {
                $errors[] = 'Помилка оновлення профілю';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword)) {
            $errors[] = 'Введіть поточний пароль';
        } elseif (!verifyPassword($currentPassword, $user['password'])) {
            $errors[] = 'Поточний пароль невірний';
        }
        
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Новий пароль повинен містити щонайменше ' . PASSWORD_MIN_LENGTH . ' символів';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Паролі не співпадають';
        }
        
        if (empty($errors)) {
            try {
                $hashedPassword = hashPassword($newPassword);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                setFlashMessage('success', 'Пароль успішно змінено');
                header('Location: profile.php');
                exit;
                
            } catch (PDOException $e) {
                $errors[] = 'Помилка зміни пароля';
            }
        }
    }
}

// Статистика користувача
$stats = [];

if ($user['role'] === 'student') {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats['courses'] = $stmt->fetch()['total'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ? AND completed_at IS NOT NULL");
    $stmt->execute([$userId]);
    $stats['completed'] = $stmt->fetch()['total'];
} elseif ($user['role'] === 'trainer') {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM courses WHERE trainer_id = ?");
    $stmt->execute([$userId]);
    $stats['courses'] = $stmt->fetch()['total'];
    
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT e.user_id) as total 
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE c.trainer_id = ?
    ");
    $stmt->execute([$userId]);
    $stats['students'] = $stmt->fetch()['total'];
}

$pageTitle = 'Мій профіль';
include 'includes/header.php';
?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
    }
    
    .profile-info-header {
        display: flex;
        align-items: center;
        gap: 30px;
    }
    
    .profile-avatar-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: white;
        color: #667eea;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 700;
        border: 4px solid rgba(255,255,255,0.3);
    }
    
    .profile-details h1 {
        font-size: 2.5rem;
        margin-bottom: 10px;
        font-weight: 700;
    }
    
    .profile-role {
        font-size: 1.1rem;
        opacity: 0.9;
    }
    
    .profile-content {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 30px;
        margin-bottom: 60px;
    }
    
    .sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        text-align: center;
    }
    
    .stat-value {
        font-size: 2.5rem;
        color: #667eea;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
    }
    
    .info-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #666;
        font-weight: 600;
    }
    
    .info-value {
        color: #333;
    }
    
    .main-content {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .form-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .form-title {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 20px;
        font-weight: 700;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-input,
    .form-textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    .form-textarea {
        min-height: 100px;
        resize: vertical;
        font-family: inherit;
    }
    
    .form-input:focus,
    .form-textarea:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .btn-save {
        padding: 12px 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
    
    @media (max-width: 992px) {
        .profile-content {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Profile Header -->
<section class="profile-header">
    <div class="container">
        <div class="profile-info-header">
            <div class="profile-avatar-large">
                <?= strtoupper(mb_substr($user['first_name'], 0, 1)) ?>
            </div>
            <div class="profile-details">
                <h1><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
                <div class="profile-role">
                    <?php
                    $roles = ['student' => '👨‍🎓 Учень', 'trainer' => '👨‍🏫 Тренер', 'admin' => '🛠️ Адміністратор'];
                    echo $roles[$user['role']];
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <div class="profile-content">
        <!-- Sidebar -->
        <div class="sidebar">
            <?php if ($user['role'] === 'student'): ?>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['courses'] ?></div>
                    <div class="stat-label">Курсів</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['completed'] ?></div>
                    <div class="stat-label">Завершено</div>
                </div>
            <?php elseif ($user['role'] === 'trainer'): ?>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['courses'] ?></div>
                    <div class="stat-label">Курсів</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['students'] ?></div>
                    <div class="stat-label">Учнів</div>
                </div>
            <?php endif; ?>
            
            <div class="info-card">
                <h3 style="margin-bottom: 15px; font-weight: 600;">Інформація</h3>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Телефон:</span>
                    <span class="info-value"><?= $user['phone'] ? htmlspecialchars($user['phone']) : 'Не вказано' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Дата реєстрації:</span>
                    <span class="info-value"><?= formatDate($user['created_at']) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Edit Profile Form -->
            <div class="form-card">
                <h2 class="form-title">✏️ Редагувати профіль</h2>
                
                <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Ім'я</label>
                            <input type="text" name="first_name" class="form-input" 
                                   value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Прізвище</label>
                            <input type="text" name="last_name" class="form-input" 
                                   value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Телефон</label>
                        <input type="tel" name="phone" class="form-input" 
                               value="<?= htmlspecialchars($user['phone']) ?>"
                               placeholder="+380 (XX) XXX-XX-XX">
                    </div>
                    
                    <?php if ($user['role'] === 'trainer'): ?>
                    <div class="form-group">
                        <label class="form-label">Досвід (років)</label>
                        <input type="number" name="experience_years" class="form-input" 
                               value="<?= htmlspecialchars($user['experience_years']) ?>"
                               min="0" placeholder="10">
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label">Про себе</label>
                        <textarea name="bio" class="form-textarea" 
                                  placeholder="Розкажіть про себе..."><?= htmlspecialchars($user['bio']) ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn-save">Зберегти зміни</button>
                </form>
            </div>
            
            <!-- Change Password Form -->
            <div class="form-card">
                <h2 class="form-title">🔒 Зміна паролю</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Поточний пароль</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Новий пароль</label>
                        <input type="password" name="new_password" class="form-input" 
                               placeholder="Мінімум <?= PASSWORD_MIN_LENGTH ?> символів" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Підтвердіть новий пароль</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-save">Змінити пароль</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>