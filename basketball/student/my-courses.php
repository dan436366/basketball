<?php
require_once '../config.php';
requireRole('student');

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Фільтр
$filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';

// Базовий запит
$sql = "
    SELECT c.*, e.enrolled_at, e.progress, e.completed_at,
           u.first_name, u.last_name,
           (SELECT COUNT(*) FROM video_lessons WHERE course_id = c.id) as total_lessons,
           (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.trainer_id = u.id
    WHERE e.user_id = ?
";

$params = [$userId];

switch ($filter) {
    case 'in_progress':
        $sql .= " AND e.progress > 0 AND e.progress < 100";
        break;
    case 'completed':
        $sql .= " AND e.completed_at IS NOT NULL";
        break;
    case 'not_started':
        $sql .= " AND e.progress = 0";
        break;
}

$sql .= " ORDER BY e.enrolled_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Підрахунок статистики
$stmt = $db->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ?");
$stmt->execute([$userId]);
$totalCourses = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ? AND progress > 0 AND completed_at IS NULL");
$stmt->execute([$userId]);
$inProgress = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ? AND completed_at IS NOT NULL");
$stmt->execute([$userId]);
$completed = $stmt->fetch()['total'];

$pageTitle = 'Мої курси';
include '../includes/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        font-size: 2.2rem;
        margin-bottom: 10px;
        font-weight: 700;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-box {
        background: white;
        padding: 20px;
        border-radius: 12px;
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
        font-size: 0.95rem;
    }
    
    .filters-bar {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 10px 20px;
        background: #f8f9fa;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        color: #333;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .filter-btn:hover {
        border-color: #667eea;
        background: #f8f9ff;
        color: #667eea;
    }
    
    .filter-btn.active {
        background: #667eea;
        border-color: #667eea;
        color: white;
    }
    
    .course-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        display: flex;
        gap: 25px;
        transition: all 0.3s;
    }
    
    .course-card:hover {
        box-shadow: 0 5px 25px rgba(0,0,0,0.12);
        transform: translateY(-3px);
    }
    
    .course-thumbnail {
        width: 200px;
        height: 150px;
        border-radius: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
        flex-shrink: 0;
        position: relative;
    }
    
    .completion-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #28a745;
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .course-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .course-header {
        margin-bottom: 15px;
    }
    
    .course-title {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 8px;
        font-weight: 600;
    }
    
    .course-trainer {
        color: #667eea;
        font-size: 1rem;
    }
    
    .course-meta {
        display: flex;
        gap: 20px;
        color: #666;
        font-size: 0.95rem;
        margin-bottom: 15px;
    }
    
    .progress-section {
        margin-top: auto;
    }
    
    .progress-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    
    .progress-label {
        color: #666;
    }
    
    .progress-value {
        color: #333;
        font-weight: 700;
    }
    
    .progress-bar-container {
        height: 10px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 15px;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        transition: width 0.3s;
    }
    
    .course-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-continue {
        padding: 12px 25px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
    }
    
    .btn-continue:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-certificate {
        padding: 12px 25px;
        background: #28a745;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
    }
    
    .btn-certificate:hover {
        background: #218838;
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .empty-icon {
        font-size: 5rem;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #666;
        margin-bottom: 25px;
    }
    
    @media (max-width: 768px) {
        .course-card {
            flex-direction: column;
        }
        
        .course-thumbnail {
            width: 100%;
            height: 200px;
        }
    }
</style>

<section class="page-header">
    <div class="container">
        <h1>📚 Мої курси</h1>
        <p>Продовжуйте своє навчання</p>
    </div>
</section>

<div class="container">
    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-value"><?= $totalCourses ?></div>
            <div class="stat-label">Всього курсів</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $inProgress ?></div>
            <div class="stat-label">В процесі</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $completed ?></div>
            <div class="stat-label">Завершено</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
        <div class="filter-buttons">
            <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                Всі курси
            </a>
            <a href="?filter=in_progress" class="filter-btn <?= $filter === 'in_progress' ? 'active' : '' ?>">
                В процесі
            </a>
            <a href="?filter=not_started" class="filter-btn <?= $filter === 'not_started' ? 'active' : '' ?>">
                Не розпочаті
            </a>
            <a href="?filter=completed" class="filter-btn <?= $filter === 'completed' ? 'active' : '' ?>">
                Завершені
            </a>
        </div>
    </div>
    
    <!-- Courses List -->
    <?php if (empty($courses)): ?>
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <h3>У вас поки немає курсів</h3>
            <p>Почніть своє навчання - оберіть курс з каталогу</p>
            <a href="../courses.php" class="btn-continue">Переглянути курси</a>
        </div>
    <?php else: ?>
        <?php foreach ($courses as $course): ?>
        <div class="course-card">
            <div class="course-thumbnail">
                🏀
                <?php if ($course['completed_at']): ?>
                    <div class="completion-badge">✓ Завершено</div>
                <?php endif; ?>
            </div>
            
            <div class="course-content">
                <div class="course-header">
                    <h2 class="course-title"><?= htmlspecialchars($course['title']) ?></h2>
                    <div class="course-trainer">
                        👨‍🏫 <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?>
                    </div>
                </div>
                
                <div class="course-meta">
                    <span>📅 Записано: <?= formatDate($course['enrolled_at']) ?></span>
                    <span>🎥 <?= $course['total_lessons'] ?> уроків</span>
                    <?php if ($course['avg_rating']): ?>
                        <span>⭐ <?= number_format($course['avg_rating'], 1) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label">Прогрес навчання</span>
                        <span class="progress-value"><?= $course['progress'] ?>%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: <?= $course['progress'] ?>%"></div>
                    </div>
                    
                    <div class="course-actions">
                        <?php if ($course['completed_at']): ?>
                            <a href="course-view.php?id=<?= $course['id'] ?>" class="btn-continue">
                                Переглянути знову
                            </a>
                            <a href="certificate.php?course_id=<?= $course['id'] ?>" class="btn-certificate">
                                📜 Сертифікат
                            </a>
                        <?php else: ?>
                            <a href="course-view.php?id=<?= $course['id'] ?>" class="btn-continue">
                                <?= $course['progress'] > 0 ? 'Продовжити навчання' : 'Почати навчання' ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>