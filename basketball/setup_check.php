<?php
// setup_check.php - Файл для перевірки налаштувань системи
// ВИДАЛІТЬ цей файл після успішного налаштування!

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Перевірка налаштувань - Basketball Learning</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .check-item.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .check-item.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .check-item.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .status {
            font-weight: bold;
            padding: 5px 15px;
            border-radius: 20px;
        }
        
        .status.ok {
            background: #28a745;
            color: white;
        }
        
        .status.fail {
            background: #dc3545;
            color: white;
        }
        
        .status.warn {
            background: #ffc107;
            color: #333;
        }
        
        .info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        
        .info h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .info code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        
        .btn-continue {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 20px;
            transition: transform 0.3s;
        }
        
        .btn-continue:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏀 Перевірка налаштувань системи</h1>
        
        <?php
        $allGood = true;
        
        // Перевірка PHP версії
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '7.4.0', '>=');
        ?>
        
        <div class="check-item <?= $phpOk ? 'success' : 'error' ?>">
            <span>PHP версія: <?= $phpVersion ?></span>
            <span class="status <?= $phpOk ? 'ok' : 'fail' ?>"><?= $phpOk ? 'OK' : 'ПОМИЛКА' ?></span>
        </div>
        
        <?php
        if (!$phpOk) {
            echo '<p style="color: #dc3545; padding: 10px;">Потрібна PHP версія 7.4 або вище</p>';
            $allGood = false;
        }
        
        // Перевірка PDO
        $pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
        ?>
        
        <div class="check-item <?= $pdoOk ? 'success' : 'error' ?>">
            <span>PDO MySQL</span>
            <span class="status <?= $pdoOk ? 'ok' : 'fail' ?>"><?= $pdoOk ? 'OK' : 'ПОМИЛКА' ?></span>
        </div>
        
        <?php
        if (!$pdoOk) {
            echo '<p style="color: #dc3545; padding: 10px;">PDO MySQL розширення не встановлено</p>';
            $allGood = false;
        }
        
        // Перевірка підключення до бази даних
        $dbOk = false;
        $dbError = '';
        try {
            $dsn = "mysql:host=localhost;charset=utf8mb4";
            $pdo = new PDO($dsn, 'root', '');
            
            // Перевірка існування бази даних
            $stmt = $pdo->query("SHOW DATABASES LIKE 'basketball_learning'");
            if ($stmt->rowCount() > 0) {
                $dbOk = true;
            } else {
                $dbError = 'База даних basketball_learning не знайдена';
            }
        } catch (PDOException $e) {
            $dbError = $e->getMessage();
        }
        ?>
        
        <div class="check-item <?= $dbOk ? 'success' : 'error' ?>">
            <span>База даних: basketball_learning</span>
            <span class="status <?= $dbOk ? 'ok' : 'fail' ?>"><?= $dbOk ? 'OK' : 'ПОМИЛКА' ?></span>
        </div>
        
        <?php
        if (!$dbOk) {
            echo '<p style="color: #dc3545; padding: 10px;">Помилка: ' . htmlspecialchars($dbError) . '</p>';
            $allGood = false;
        }
        
        // Перевірка папок
        $uploadsExists = is_dir(__DIR__ . '/uploads');
        $uploadsWritable = $uploadsExists && is_writable(__DIR__ . '/uploads');
        ?>
        
        <div class="check-item <?= $uploadsExists ? 'success' : 'warning' ?>">
            <span>Папка uploads/</span>
            <span class="status <?= $uploadsExists ? 'ok' : 'warn' ?>">
                <?= $uploadsExists ? 'OK' : 'НЕ ЗНАЙДЕНО' ?>
            </span>
        </div>
        
        <?php if ($uploadsExists && !$uploadsWritable): ?>
            <p style="color: #856404; padding: 10px;">Папка uploads/ існує, але немає прав на запис</p>
        <?php endif; ?>
        
        <?php
        $includesExists = is_dir(__DIR__ . '/includes');
        ?>
        
        <div class="check-item <?= $includesExists ? 'success' : 'warning' ?>">
            <span>Папка includes/</span>
            <span class="status <?= $includesExists ? 'ok' : 'warn' ?>">
                <?= $includesExists ? 'OK' : 'НЕ ЗНАЙДЕНО' ?>
            </span>
        </div>
        
        <?php
        // Визначення поточного URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = dirname($_SERVER['SCRIPT_NAME']);
        $currentUrl = $protocol . '://' . $host . $script;
        ?>
        
        <div class="info">
            <h3>📋 Інформація про систему</h3>
            <p><strong>Поточний URL:</strong> <code><?= htmlspecialchars($currentUrl) ?></code></p>
            <p><strong>Шлях до файлів:</strong> <code><?= htmlspecialchars(__DIR__) ?></code></p>
            <p><strong>PHP версія:</strong> <code><?= $phpVersion ?></code></p>
        </div>
        
        <?php if ($allGood): ?>
            <div class="info" style="background: #d4edda; border-color: #28a745;">
                <h3 style="color: #155724;">✅ Всі перевірки пройдено успішно!</h3>
                <p style="color: #155724;">Система готова до роботи. Натисніть кнопку нижче, щоб перейти на головну сторінку.</p>
            </div>
            <a href="index.php" class="btn-continue">Перейти на головну сторінку</a>
        <?php else: ?>
            <div class="info" style="background: #f8d7da; border-color: #dc3545;">
                <h3 style="color: #721c24;">❌ Виявлено помилки</h3>
                <p style="color: #721c24;">Будь ласка, виправте помилки вище перед продовженням.</p>
                
                <?php if (!$dbOk): ?>
                <p style="color: #721c24; margin-top: 15px;"><strong>Для створення бази даних:</strong></p>
                <ol style="color: #721c24; margin-left: 20px;">
                    <li>Відкрийте phpMyAdmin: <code>http://localhost/phpmyadmin</code></li>
                    <li>Створіть нову базу даних з назвою: <code>basketball_learning</code></li>
                    <li>Виконайте SQL-скрипт з файлу basketball_db</li>
                </ol>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 20px;">
            <h3>⚠️ Важливо!</h3>
            <p>Після успішного налаштування <strong>ВИДАЛІТЬ</strong> цей файл (setup_check.php) з міркувань безпеки!</p>
        </div>
    </div>
</body>
</html>