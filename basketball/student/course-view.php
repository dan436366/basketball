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

// Отримання уроків з інформацією про завершення
$stmt = $db->prepare("
    SELECT vl.*, lp.is_completed, lp.completed_at
    FROM video_lessons vl
    LEFT JOIN lesson_progress lp ON vl.id = lp.lesson_id AND lp.user_id = ?
    WHERE vl.course_id = ?
    ORDER BY vl.order_number ASC
");
$stmt->execute([$userId, $courseId]);
$lessons = $stmt->fetchAll();

// Поточний урок
$currentLessonId = isset($_GET['lesson']) ? (int)$_GET['lesson'] : ($lessons[0]['id'] ?? 0);
$currentLesson = null;
$currentLessonIndex = 0;

foreach ($lessons as $index => $lesson) {
    if ($lesson['id'] == $currentLessonId) {
        $currentLesson = $lesson;
        $currentLessonIndex = $index;
        break;
    }
}

if (!$currentLesson && !empty($lessons)) {
    $currentLesson = $lessons[0];
}

// Обробка позначення уроку як завершеного
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_complete') {
        $lessonId = intval($_POST['lesson_id'] ?? 0);
        
        try {
            // Перевіряємо, чи існує запис
            $stmt = $db->prepare("SELECT id FROM lesson_progress WHERE user_id = ? AND lesson_id = ?");
            $stmt->execute([$userId, $lessonId]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Оновлюємо
                $stmt = $db->prepare("
                    UPDATE lesson_progress 
                    SET is_completed = TRUE, completed_at = NOW() 
                    WHERE user_id = ? AND lesson_id = ?
                ");
            } else {
                // Створюємо новий запис
                $stmt = $db->prepare("
                    INSERT INTO lesson_progress (user_id, lesson_id, is_completed, completed_at)
                    VALUES (?, ?, TRUE, NOW())
                ");
            }
            $stmt->execute([$userId, $lessonId]);
            
            // Оновлюємо загальний прогрес курсу
            $stmt = $db->prepare("
                SELECT COUNT(*) as total_lessons,
                       SUM(CASE WHEN lp.is_completed = 1 THEN 1 ELSE 0 END) as completed_lessons
                FROM video_lessons vl
                LEFT JOIN lesson_progress lp ON vl.id = lp.lesson_id AND lp.user_id = ?
                WHERE vl.course_id = ?
            ");
            $stmt->execute([$userId, $courseId]);
            $progress = $stmt->fetch();
            
            $totalLessons = $progress['total_lessons'];
            $completedLessons = $progress['completed_lessons'];
            $progressPercent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
            
            $stmt = $db->prepare("UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$progressPercent, $userId, $courseId]);
            
            if ($progressPercent >= 100) {
                $stmt = $db->prepare("UPDATE enrollments SET completed_at = NOW() WHERE user_id = ? AND course_id = ? AND completed_at IS NULL");
                $stmt->execute([$userId, $courseId]);
            }
            
            echo json_encode(['success' => true, 'progress' => $progressPercent]);
            exit;
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
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
        padding-top: 56.25%;
        background: #000;
    }
    
    .video-player {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    .video-player video {
        width: 100%;
        height: 100%;
        object-fit: contain;
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
        padding: 30px;
        color: white;
    }
    
    .lesson-header-info {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 20px;
        gap: 20px;
    }
    
    .lesson-main-info {
        flex: 1;
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
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    
    .lesson-actions-top {
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-width: 200px;
    }
    
    .lesson-description {
        color: #ccc;
        line-height: 1.6;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #3a3a3a;
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
        width: 100%;
        font-size: 0.95rem;
    }
    
    .btn-complete:hover {
        background: #218838;
        transform: translateY(-2px);
    }
    
    .btn-complete.completed {
        background: #6c757d;
        cursor: default;
    }
    
    .btn-complete:disabled {
        opacity: 0.7;
        cursor: not-allowed;
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
        display: block;
        text-align: center;
        transition: all 0.3s;
        width: 100%;
        font-size: 0.95rem;
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
    
    .lesson-item.completed {
        opacity: 0.7;
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
    
    .lesson-item.completed .lesson-number {
        background: #28a745;
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
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    
    .status-completed {
        color: #28a745;
    }
    
    @media (max-width: 992px) {
        .course-viewer {
            grid-template-columns: 1fr;
        }
        
        .sidebar {
            max-height: 400px;
        }
        
        .lesson-header-info {
            flex-direction: column;
        }
        
        .lesson-actions-top {
            width: 100%;
        }
    }
</style>

<div class="course-viewer">
    <!-- Video Section -->
    <div class="video-section">
        <?php if ($currentLesson): ?>
        <div class="video-container">
            <?php if ($currentLesson['video_file']): ?>
                <?php
                // Шлях до відео
                $videoFile = $currentLesson['video_file'];
                $videoPath = '/basketball/uploads/videos/' . $videoFile;
                
                // Визначення розширення
                $videoExt = strtolower(pathinfo($videoFile, PATHINFO_EXTENSION));
                
                // Перевірка існування файлу
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . $videoPath;
                $fileExists = file_exists($fullPath);
                ?>
                
                <?php if ($fileExists): ?>
                <div class="video-player">
                    <video controls preload="metadata" style="width: 100%; height: 100%; background: #000;">
                        <source src="<?= htmlspecialchars($videoPath) ?>" type="video/<?= $videoExt === 'mov' ? 'quicktime' : $videoExt ?>">
                        Ваш браузер не підтримує відтворення відео.
                    </video>
                </div>
                <?php else: ?>
                <div class="video-placeholder">
                    <div class="video-placeholder-icon">⚠️</div>
                    <p>Файл відео не знайдено</p>
                    <small style="font-size: 0.8rem; opacity: 0.7;">Очікуваний шлях: <?= htmlspecialchars($fullPath) ?></small>
                </div>
                <?php endif; ?>
            <?php elseif ($currentLesson['video_url']): ?>
                <?php
                $videoUrl = $currentLesson['video_url'];
                if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\?\/]+)/', $videoUrl, $matches);
                    $videoId = $matches[1] ?? '';
                    $embedUrl = "https://www.youtube.com/embed/{$videoId}";
                } elseif (strpos($videoUrl, 'vimeo.com') !== false) {
                    preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches);
                    $videoId = $matches[1] ?? '';
                    $embedUrl = "https://player.vimeo.com/video/{$videoId}";
                } else {
                    $embedUrl = $videoUrl;
                }
                ?>
                <iframe class="video-player" src="<?= htmlspecialchars($embedUrl) ?>" 
                        frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>
            <?php else: ?>
                <div class="video-placeholder">
                    <div class="video-placeholder-icon">🎹</div>
                    <p>Відео ще не завантажено</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="video-info">
            <div class="lesson-header-info">
                <div class="lesson-main-info">
                    <h1 class="lesson-title"><?= htmlspecialchars($currentLesson['title']) ?></h1>
                    <div class="lesson-meta">
                        <span>📚 Урок <?= $currentLesson['order_number'] ?> з <?= count($lessons) ?></span>
                        <?php if ($currentLesson['duration_minutes']): ?>
                        <span>⏱️ <?= $currentLesson['duration_minutes'] ?> хвилин</span>
                        <?php endif; ?>
                        <?php if ($currentLesson['is_completed']): ?>
                        <span style="color: #28a745;">✅ Завершено</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="lesson-actions-top">
                    <button class="btn-complete <?= $currentLesson['is_completed'] ? 'completed' : '' ?>" 
                            onclick="markAsCompleted(<?= $currentLesson['id'] ?>)"
                            id="complete-btn"
                            <?= $currentLesson['is_completed'] ? 'disabled' : '' ?>>
                        <?= $currentLesson['is_completed'] ? '✅ Завершено' : '✓ Відмітити як пройдений' ?>
                    </button>
                    
                    <?php
                    $nextLesson = $lessons[$currentLessonIndex + 1] ?? null;
                    ?>
                    
                    <?php if ($nextLesson): ?>
                    <a href="?id=<?= $courseId ?>&lesson=<?= $nextLesson['id'] ?>" class="btn-next">
                        Наступний урок →
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($currentLesson['description']): ?>
            <div class="lesson-description">
                <strong>Опис уроку:</strong><br>
                <?= nl2br(htmlspecialchars($currentLesson['description'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="course-title-sidebar"><?= htmlspecialchars($course['title']) ?></div>
            <div class="progress-info">
                Прогрес курсу: <strong><span id="progress-percent"><?= $enrollment['progress'] ?></span>%</strong>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?= $enrollment['progress'] ?>%" id="course-progress"></div>
            </div>
        </div>
        
        <ul class="lessons-list">
            <?php foreach ($lessons as $index => $lesson): ?>
            <li class="lesson-item <?= $lesson['id'] == $currentLessonId ? 'active' : '' ?> <?= $lesson['is_completed'] ? 'completed' : '' ?>" 
                onclick="window.location.href='?id=<?= $courseId ?>&lesson=<?= $lesson['id'] ?>'">
                <div class="lesson-number"><?= $index + 1 ?></div>
                <div class="lesson-info-sidebar">
                    <div class="lesson-name"><?= htmlspecialchars($lesson['title']) ?></div>
                    <?php if ($lesson['duration_minutes']): ?>
                    <div class="lesson-duration">⏱️ <?= $lesson['duration_minutes'] ?> хв</div>
                    <?php endif; ?>
                </div>
                <div class="lesson-status">
                    <?php if ($lesson['is_completed']): ?>
                        <span class="status-completed">✓</span>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
function markAsCompleted(lessonId) {
    const btn = document.getElementById('complete-btn');
    btn.disabled = true;
    btn.textContent = 'Обробка...';
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=mark_complete&lesson_id=' + lessonId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Оновлення прогресу
            document.getElementById('course-progress').style.width = data.progress + '%';
            document.getElementById('progress-percent').textContent = data.progress;
            
            // Оновлення кнопки
            btn.textContent = '✅ Завершено';
            btn.classList.add('completed');
            
            // Оновлення списку уроків
            const lessonItem = document.querySelector('.lesson-item.active');
            if (lessonItem) {
                lessonItem.classList.add('completed');
                const lessonNumber = lessonItem.querySelector('.lesson-number');
                if (lessonNumber) {
                    lessonNumber.style.background = '#28a745';
                }
                const statusDiv = lessonItem.querySelector('.lesson-status');
                if (statusDiv) {
                    statusDiv.innerHTML = '<span class="status-completed">✓</span>';
                }
            }
            
            if (data.progress >= 100) {
                setTimeout(() => {
                    alert('🎉 Вітаємо! Ви завершили курс!');
                }, 500);
            }
        } else {
            btn.disabled = false;
            btn.textContent = '✓ Відмітити як пройдений';
            alert('Помилка: ' + (data.message || 'Спробуйте ще раз'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.textContent = '✓ Відмітити як пройдений';
        alert('Помилка підключення. Перевірте інтернет та спробуйте ще раз.');
    });
}
</script>

<?php include '../includes/footer.php'; ?>