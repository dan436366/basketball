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
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND is_active = 1 AND is_free = 1");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    setFlashMessage('error', 'Курс не знайдено або він не безкоштовний');
    header('Location: courses.php');
    exit;
}

// Перевірка ролі користувача
$user = getCurrentUser();
if ($user['role'] !== 'student') {
    setFlashMessage('error', 'Тільки студенти можуть записуватись на курси');
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

try {
    // Реєстрація на курс
    $stmt = $db->prepare("
        INSERT INTO enrollments (user_id, course_id, progress)
        VALUES (?, ?, 0)
    ");
    $stmt->execute([$_SESSION['user_id'], $courseId]);
    
    setFlashMessage('success', 'Ви успішно записались на безкоштовний курс!');
    header('Location: student/course-view.php?id=' . $courseId);
    exit;
    
} catch (PDOException $e) {
    setFlashMessage('error', 'Помилка запису на курс. Спробуйте пізніше.');
    header('Location: course.php?id=' . $courseId);
    exit;
}
?>