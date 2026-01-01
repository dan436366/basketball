<?php
require_once 'config.php';
requireLogin();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$courseId) {
    header('Location: courses.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Отримання інформації про курс
$stmt = $db->prepare("
    SELECT c.*, u.first_name, u.last_name
    FROM courses c
    LEFT JOIN users u ON c.trainer_id = u.id
    WHERE c.id = ? AND c.is_active = 1
");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: courses.php');
    exit;
}

// Перевірка, чи користувач вже записаний
$stmt = $db->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->execute([$_SESSION['user_id'], $courseId]);
if ($stmt->fetch()) {
    setFlashMessage('info', 'Ви вже записані на цей курс');
    header('Location: student/dashboard.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = sanitizeInput($_POST['payment_method'] ?? '');
    $cardNumber = sanitizeInput($_POST['card_number'] ?? '');
    $cardName = sanitizeInput($_POST['card_name'] ?? '');
    $cardExpiry = sanitizeInput($_POST['card_expiry'] ?? '');
    $cardCvv = sanitizeInput($_POST['card_cvv'] ?? '');
    
    // Базова валідація
    if (empty($paymentMethod)) {
        $errors[] = 'Оберіть спосіб оплати';
    }
    
    if ($paymentMethod === 'card') {
        if (empty($cardNumber) || strlen(str_replace(' ', '', $cardNumber)) < 16) {
            $errors[] = 'Введіть коректний номер картки';
        }
        if (empty($cardName)) {
            $errors[] = 'Введіть ім\'я власника картки';
        }
        if (empty($cardExpiry)) {
            $errors[] = 'Введіть термін дії картки';
        }
        if (empty($cardCvv) || strlen($cardCvv) < 3) {
            $errors[] = 'Введіть CVV код';
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Створення платежу
            $transactionId = 'TXN' . time() . rand(1000, 9999);
            $stmt = $db->prepare("
                INSERT INTO payments (user_id, course_id, amount, payment_method, transaction_id, status)
                VALUES (?, ?, ?, ?, ?, 'completed')
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $courseId,
                $course['price'],
                $paymentMethod,
                $transactionId
            ]);
            
            // Реєстрація на курс
            $stmt = $db->prepare("
                INSERT INTO enrollments (user_id, course_id, progress)
                VALUES (?, ?, 0)
            ");
            $stmt->execute([$_SESSION['user_id'], $courseId]);
            
            $db->commit();
            
            setFlashMessage('success', 'Оплата успішна! Ви записані на курс.');
            header('Location: student/course-view.php?id=' . $courseId);
            exit;
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = 'Помилка обробки платежу. Спробуйте пізніше.';
        }
    }
}

$pageTitle = 'Оплата курсу';
include 'includes/header.php';
?>

<style>
    .payment-container {
        max-width: 900px;
        margin: 40px auto;
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 30px;
    }
    
    .payment-form {
        background: white;
        padding: 35px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .payment-title {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 25px;
        font-weight: 700;
    }
    
    .payment-methods {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .payment-method {
        position: relative;
    }
    
    .payment-method input[type="radio"] {
        display: none;
    }
    
    .payment-method label {
        display: block;
        padding: 20px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
    }
    
    .payment-method input[type="radio"]:checked + label {
        border-color: #667eea;
        background: #f8f9ff;
    }
    
    .payment-method label:hover {
        border-color: #667eea;
    }
    
    .method-icon {
        font-size: 2rem;
        margin-bottom: 8px;
    }
    
    .method-name {
        font-weight: 600;
        color: #333;
    }
    
    .card-form {
        display: none;
    }
    
    .card-form.active {
        display: block;
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
    
    .form-input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    .form-input:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .btn-pay {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-pay:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
    
    .order-summary {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        height: fit-content;
        position: sticky;
        top: 20px;
    }
    
    .summary-title {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 20px;
        font-weight: 700;
    }
    
    .course-summary {
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
        margin-bottom: 20px;
    }
    
    .course-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }
    
    .course-trainer {
        color: #666;
        font-size: 0.9rem;
    }
    
    .price-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        color: #666;
    }
    
    .price-row.total {
        border-top: 2px solid #f0f0f0;
        margin-top: 10px;
        padding-top: 20px;
        font-size: 1.3rem;
        font-weight: 700;
        color: #333;
    }
    
    .price-amount {
        color: #667eea;
    }
    
    .security-note {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        font-size: 0.9rem;
        color: #666;
        display: flex;
        align-items: center;
        gap: 10px;
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
        .payment-container {
            grid-template-columns: 1fr;
        }
        
        .order-summary {
            position: static;
        }
    }
</style>

<div class="container">
    <div class="payment-container">
        <!-- Payment Form -->
        <div class="payment-form">
            <h1 class="payment-title">💳 Оплата курсу</h1>
            
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
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" name="payment_method" id="card" value="card" checked>
                        <label for="card">
                            <div class="method-icon">💳</div>
                            <div class="method-name">Картка</div>
                        </label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" name="payment_method" id="paypal" value="paypal">
                        <label for="paypal">
                            <div class="method-icon">🅿️</div>
                            <div class="method-name">PayPal</div>
                        </label>
                    </div>
                </div>
                
                <div class="card-form active" id="card-form">
                    <div class="form-group">
                        <label class="form-label">Номер картки</label>
                        <input type="text" name="card_number" class="form-input" 
                               placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ім'я власника</label>
                        <input type="text" name="card_name" class="form-input" 
                               placeholder="TARAS SHEVCHENKO">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Термін дії</label>
                            <input type="text" name="card_expiry" class="form-input" 
                                   placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label class="form-label">CVV</label>
                            <input type="text" name="card_cvv" class="form-input" 
                                   placeholder="123" maxlength="3">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-pay">
                    Оплатити <?= formatPrice($course['price']) ?>
                </button>
                
                <div class="security-note">
                    <span>🔒</span>
                    <span>Ваші платіжні дані захищені SSL-шифруванням</span>
                </div>
            </form>
        </div>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <h2 class="summary-title">Деталі замовлення</h2>
            
            <div class="course-summary">
                <div class="course-name"><?= htmlspecialchars($course['title']) ?></div>
                <div class="course-trainer">
                    Тренер: <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?>
                </div>
            </div>
            
            <div class="price-row">
                <span>Вартість курсу</span>
                <span class="price-amount"><?= formatPrice($course['price']) ?></span>
            </div>
            
            <div class="price-row total">
                <span>До сплати</span>
                <span class="price-amount"><?= formatPrice($course['price']) ?></span>
            </div>
            
            <div class="security-note">
                <span>✓</span>
                <span>Доступ до курсу відразу після оплати</span>
            </div>
        </div>
    </div>
</div>

<script>
// Форматування номера картки
document.querySelector('input[name="card_number"]')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '');
    let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formatted;
});

// Форматування терміну дії
document.querySelector('input[name="card_expiry"]')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2, 4);
    }
    e.target.value = value;
});

// CVV тільки цифри
document.querySelector('input[name="card_cvv"]')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '');
});
</script>

<?php include 'includes/footer.php'; ?>