<?php
require_once '../config.php';
requireRole('student');

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

if (!$courseId) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Перевірка доступу до курсу
$stmt = $db->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->execute([$userId, $courseId]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    setFlashMessage('error', 'У вас немає доступу до цього курсу');
    header('Location: ../courses.php');
    exit;
}

// Отримання інформації про курс
$stmt = $db->prepare("
    SELECT c.*, u.first_name, u.last_name
    FROM courses c
    LEFT JOIN users u ON c.trainer_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

// Отримання уроків
$stmt = $db->prepare("
    SELECT * FROM video_lessons 
    WHERE course_id = ? 
    ORDER BY order_number ASC
");
$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll();

// Поточний урок
$currentLessonId = isset($_GET['lesson']) ? (int)$_GET['lesson'] : ($lessons[0]['id'] ?? 0);
$currentLesson = null;
foreach ($lessons as $lesson) {
    if ($lesson['id'] == $currentLessonId) {
        $currentLesson = $lesson;
        break;
    }
}

if (!$currentLesson && !empty($lessons)) {
    $currentLesson = $lessons[0];
}

// Оновлення прогресу при POST запиті
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'complete_lesson') {
        // Логіка відзначення уроку як завершеного
        // Оновлення прогресу
        $completedLessons = isset($_POST['completed']) ? (int)$_POST['completed'] : 0;
        $totalLessons = count($lessons);
        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
        
        $stmt = $db->prepare("UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$progress, $userId, $courseId]);
        
        if ($progress >= 100) {
            $stmt = $db->prepare("UPDATE enrollments SET completed_at = NOW() WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$userId, $courseId]);
        }
        
        echo json_encode(['success' => true, 'progress' => $progress]);
        exit;
    }
}

$pageTitle = $course['title'];
include '../includes/header.php';
?>

<style>
    body {
        background: #1a1a1a;
    }
    
    .course-viewer {
        display: grid;
        grid-template-columns: 1fr 350px;
        min-height: calc(100vh - 80px);
        background: #1a1a1a;
    }
    
    .video-section {
        background: #000;
        padding: 0;
    }
    
    .video-container {
        position: relative;
        width: 100%;
        padding-top: 56.25%; /* 16:9 Aspect Ratio */
        background: #000;
    }
    
    .video-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .video-placeholder-icon {
        font-size: 5rem;
        margin-bottom: 20px;
    }
    
    .video-info {
        background: #2a2a2a;
        padding: 25px 30px;
        color: white;
    }
    
    .lesson-title {
        font-size: 1.8rem;
        margin-bottom: 15px;
        font-weight: 700;
    }
    
    .lesson-meta {
        display: flex;
        gap: 25px;
        color: #aaa;
        margin-bottom: 20px;
    }
    
    .lesson-description {
        color: #ccc;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    .lesson-actions {
        display: flex;
        gap: 15px;
    }
    
    .btn-complete {
        padding: 12px 25px;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-complete:hover {
        background: #218838;
        transform: translateY(-2px);
    }
    
    .btn-next {
        padding: 12px 25px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }
    
    .btn-next:hover {
        background: #5568d3;
        color: white;
        transform: translateY(-2px);
    }
    
    .sidebar {
        background: #2a2a2a;
        overflow-y: auto;
        max-height: calc(100vh - 80px);
    }
    
    .sidebar-header {
        padding: 25px;
        border-bottom: 1px solid #3a3a3a;
    }
    
    .course-title-sidebar {
        color: white;
        font-size: 1.2rem;
        margin-bottom: 10px;
        font-weight: 600;
    }
    
    .progress-info {
        color: #aaa;
        font-size: 0.9rem;
        margin-bottom: 12px;
    }
    
    .progress-bar-container {
        height: 6px;
        background: #3a3a3a;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: width 0.3s;
    }
    
    .lessons-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .lesson-item {
        padding: 18px 25px;
        border-bottom: 1px solid #3a3a3a;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .lesson-item:hover {
        background: #333;
    }
    
    .lesson-item.active {
        background: #667eea;
    }
    
    .lesson-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #3a3a3a;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    
    .lesson-item.active .lesson-number {
        background: white;
        color: #667eea;
    }
    
    .lesson-info-sidebar {
        flex: 1;
        min-width: 0;
    }
    
    .lesson-name {
        color: white;
        font-weight: 600;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .lesson-duration {
        color: #aaa;
        font-size: 0.85rem;
    }
    
    .lesson-status {
        color: #28a745;
        font-size: 1.2rem;
    }
    
    @media (max-width: 992px) {
        .course-viewer {
            grid-template-columns: 1fr;
        }
        
        .sidebar {
            max-height: 400px;
        }
    }
</style>

<div class="course-viewer">
    <!-- Video Section -->
    <div class="video-section">
        <?php if ($currentLesson): ?>
        <div class="video-container">
            <?php if (!empty($currentLesson['video_url'])): ?>
                <!-- Тут буде відео плеєр. Для прикладу використовуємо placeholder -->
                <div class="video-placeholder">
                    <div class="video-placeholder-icon">🎥</div>
                    <p style="font-size: 1.2rem;">Відео урок: <?= htmlspecialchars($currentLesson['title']) ?></p>
                    <p style="color: rgba(255,255,255,0.7);">URL: <?= htmlspecialchars($currentLesson['video_url']) ?></p>
                </div>
            <?php else: ?>
                <div class="video-placeholder">
                    <div class="video-placeholder-icon">📹</div>
                    <p>Відео ще не завантажено</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="video-info">
            <h1 class="lesson-title"><?= htmlspecialchars($currentLesson['title']) ?></h1>
            <div class="lesson-meta">
                <span>📚 Урок <?= $currentLesson['order_number'] ?> з <?= count($lessons) ?></span>
                <?php if ($currentLesson['duration_minutes']): ?>
                <span>⏱️ <?= $currentLesson['duration_minutes'] ?> хвилин</span>
                <?php endif; ?>
            </div>
            
            <?php if ($currentLesson['description']): ?>
            <div class="lesson-description">
                <?= nl2br(htmlspecialchars($currentLesson['description'])) ?>
            </div>
            <?php endif; ?>
            
            <div class="lesson-actions">
                <button class="btn-complete" onclick="markAsCompleted()">
                    ✅ Відмітити як пройдений
                </button>
                
                <?php
                // Знаходження наступного уроку
                $nextLesson = null;
                foreach ($lessons as $index => $lesson) {
                    if ($lesson['id'] == $currentLessonId && isset($lessons[$index + 1])) {
                        $nextLesson = $lessons[$index + 1];
                        break;
                    }
                }
                ?>
                
                <?php if ($nextLesson): ?>
                <a href="?id=<?= $courseId ?>&lesson=<?= $nextLesson['id'] ?>" class="btn-next">
                    Наступний урок →
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="course-title-sidebar"><?= htmlspecialchars($course['title']) ?></div>
            <div class="progress-info">
                Прогрес курсу: <strong><?= $enrollment['progress'] ?>%</strong>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?= $enrollment['progress'] ?>%" id="course-progress"></div>
            </div>
        </div>
        
        <ul class="lessons-list">
            <?php foreach ($lessons as $index => $lesson): ?>
            <li class="lesson-item <?= $lesson['id'] == $currentLessonId ? 'active' : '' ?>" 
                onclick="window.location.href='?id=<?= $courseId ?>&lesson=<?= $lesson['id'] ?>'">
                <div class="lesson-number"><?= $index + 1 ?></div>
                <div class="lesson-info-sidebar">
                    <div class="lesson-name"><?= htmlspecialchars($lesson['title']) ?></div>
                    <?php if ($lesson['duration_minutes']): ?>
                    <div class="lesson-duration">⏱️ <?= $lesson['duration_minutes'] ?> хв</div>
                    <?php endif; ?>
                </div>
                <div class="lesson-status">
                    <!-- Тут можна додати статус проходження -->
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
let completedLessons = 0;

function markAsCompleted() {
    completedLessons++;
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=complete_lesson&completed=' + completedLessons
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Оновлення прогресу
            document.getElementById('course-progress').style.width = data.progress + '%';
            alert('✅ Урок відмічено як пройдений!');
            
            if (data.progress >= 100) {
                alert('🎉 Вітаємо! Ви завершили курс!');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>

<?php include '../includes/footer.php'; ?>