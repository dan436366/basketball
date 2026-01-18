<?php
require_once '../config.php';
requireRole('student');

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$userId = $_SESSION['user_id'];

if (!$courseId) {
    header('Location: my-courses.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Перевірка, чи курс завершено
$stmt = $db->prepare("
    SELECT e.*, c.title as course_title, u.first_name, u.last_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON e.user_id = u.id
    WHERE e.user_id = ? AND e.course_id = ? AND e.completed_at IS NOT NULL
");
$stmt->execute([$userId, $courseId]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    setFlashMessage('error', 'Ви ще не завершили цей курс');
    header('Location: my-courses.php');
    exit;
}

// Отримання інформації про тренера
$stmt = $db->prepare("
    SELECT u.first_name, u.last_name
    FROM courses c
    JOIN users u ON c.trainer_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$courseId]);
$trainer = $stmt->fetch();

$pageTitle = 'Сертифікат';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сертифікат про завершення курсу</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .certificate-container {
            background: white;
            max-width: 900px;
            width: 100%;
            padding: 60px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 15px solid #f8f9fa;
            position: relative;
        }
        
        .certificate-border {
            position: absolute;
            top: 40px;
            left: 40px;
            right: 40px;
            bottom: 40px;
            border: 3px solid #667eea;
            pointer-events: none;
        }
        
        .certificate-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .certificate-logo {
            font-size: 4rem;
            margin-bottom: 10px;
        }
        
        .certificate-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .certificate-subtitle {
            font-size: 1.2rem;
            color: #666;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        
        .certificate-body {
            text-align: center;
            margin: 40px 0;
            position: relative;
            z-index: 1;
        }
        
        .certificate-text {
            font-size: 1.1rem;
            color: #333;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        
        .recipient-name {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: #333;
            margin: 30px 0;
            font-weight: 700;
            border-bottom: 3px solid #667eea;
            display: inline-block;
            padding: 10px 40px;
        }
        
        .course-name {
            font-size: 1.8rem;
            color: #667eea;
            font-weight: 600;
            margin: 20px 0;
        }
        
        .certificate-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
            position: relative;
            z-index: 1;
        }
        
        .signature-block {
            text-align: center;
            flex: 1;
        }
        
        .signature-line {
            width: 200px;
            height: 2px;
            background: #333;
            margin: 0 auto 10px;
        }
        
        .signature-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .signature-title {
            color: #666;
            font-size: 0.9rem;
        }
        
        .certificate-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
            position: relative;
            z-index: 1;
        }
        
        .btn {
            padding: 12px 30px;
            margin: 0 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-print {
            background: #667eea;
            color: white;
        }
        
        .btn-print:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-back:hover {
            background: #f8f9fa;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            color: rgba(102, 126, 234, 0.05);
            pointer-events: none;
            font-weight: 700;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .actions {
                display: none;
            }
            
            .certificate-container {
                box-shadow: none;
                max-width: none;
            }
        }
        
        @media (max-width: 768px) {
            .certificate-container {
                padding: 30px 20px;
            }
            
            .certificate-title {
                font-size: 2rem;
            }
            
            .recipient-name {
                font-size: 2rem;
            }
            
            .course-name {
                font-size: 1.3rem;
            }
            
            .certificate-footer {
                flex-direction: column;
                gap: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border"></div>
        <div class="watermark">🏀</div>
        
        <div class="certificate-header">
            <div class="certificate-logo">🏀</div>
            <h1 class="certificate-title">Сертифікат</h1>
            <p class="certificate-subtitle">Basketball Learning</p>
        </div>
        
        <div class="certificate-body">
            <p class="certificate-text">
                Цей сертифікат підтверджує, що
            </p>
            
            <div class="recipient-name">
                <?= htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?>
            </div>
            
            <p class="certificate-text">
                успішно завершив(ла) онлайн-курс
            </p>
            
            <div class="course-name">
                "<?= htmlspecialchars($enrollment['course_title']) ?>"
            </div>
            
            <p class="certificate-text">
                з результатом <strong>100%</strong> виконання програми курсу
            </p>
        </div>
        
        <div class="certificate-footer">
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name">Basketball Learning</div>
                <div class="signature-title">Платформа онлайн-навчання</div>
            </div>
            
            <div class="signature-block">
                <div class="certificate-date">
                    Дата завершення<br>
                    <strong><?= formatDate($enrollment['completed_at']) ?></strong>
                </div>
            </div>
            
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name"><?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?></div>
                <div class="signature-title">Тренер курсу</div>
            </div>
        </div>
        
        <div class="actions">
            <button onclick="window.print()" class="btn btn-print">🖨️ Роздрукувати сертифікат</button>
            <a href="my-courses.php" class="btn btn-back">← Назад до курсів</a>
        </div>
    </div>
</body>
</html>