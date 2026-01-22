<?php
require_once '../config.php';
requireRole('trainer');

$db = Database::getInstance()->getConnection();
$trainerId = $_SESSION['user_id'];

// Отримання всіх курсів тренера
$stmt = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count,
           (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating,
           (SELECT COUNT(*) FROM video_lessons WHERE course_id = c.id) as lessons_count
    FROM courses c
    WHERE c.trainer_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$trainerId]);
$courses = $stmt->fetchAll();

$pageTitle = 'Мої курси';
include '../includes/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
    }
    
    .page-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .btn-create-header {
        padding: 12px 30px;
        background: white;
        color: #667eea;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 700;
        transition: all 0.3s;
        display: inline-block;
    }
    
    .btn-create-header:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(255,255,255,0.3);
        color: #667eea;
    }
    
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 60px;
    }
    
    .course-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        transition: all 0.3s;
    }
    
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .course-thumbnail {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        color: white;
    }
    
    .course-content {
        padding: 25px;
    }
    
    .course-title {
        font-size: 1.4rem;
        color: #333;
        font-weight: 700;
        margin-bottom: 10px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .course-description {
        color: #666;
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.6;
    }
    
    .course-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
        padding: 15px 0;
        border-top: 1px solid #f0f0f0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
        font-size: 0.9rem;
    }
    
    .course-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .course-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: #667eea;
    }
    
    .course-level {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .level-beginner {
        background: #d4edda;
        color: #155724;
    }
    
    .level-intermediate {
        background: #fff3cd;
        color: #856404;
    }
    
    .level-advanced {
        background: #f8d7da;
        color: #721c24;
    }
    
    .course-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .btn-action {
        padding: 10px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s;
    }
    
    .btn-edit {
        background: #667eea;
        color: white;
    }
    
    .btn-edit:hover {
        background: #5568d3;
        color: white;
    }
    
    .btn-lessons {
        background: #f8f9fa;
        color: #333;
        border: 2px solid #e0e0e0;
    }
    
    .btn-lessons:hover {
        background: #e9ecef;
        color: #333;
    }
    
    .status-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        background: white;
        color: #333;
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
    
    .empty-state h2 {
        font-size: 2rem;
        color: #333;
        margin-bottom: 15px;
    }
    
    .empty-state p {
        color: #666;
        font-size: 1.1rem;
        margin-bottom: 30px;
    }
</style>

<section class="page-header">
    <div class="container">
        <div class="page-header-content">
            <div>
                <h1>📚 Мої курси</h1>
                <p>Управляйте своїми курсами та додавайте нові уроки</p>
            </div>
            <a href="course-create.php" class="btn-create-header">+ Створити курс</a>
        </div>
    </div>
</section>

<div class="container">
    <?php if (empty($courses)): ?>
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <h2>У вас ще немає курсів</h2>
            <p>Створіть свій перший курс і почніть ділитися знаннями з учнями</p>
            <a href="course-create.php" class="btn-create-header">+ Створити перший курс</a>
        </div>
    <?php else: ?>
        <div class="courses-grid">
            <?php foreach ($courses as $course): ?>
            <div class="course-card">
                <div class="course-thumbnail" style="position: relative;">
                    🏀
                    <span class="status-badge">
                        <?= $course['is_active'] ? '✅ Активний' : '⏸️ Неактивний' ?>
                    </span>
                </div>
                
                <div class="course-content">
                    <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                    
                    <p class="course-description">
                        <?= htmlspecialchars($course['description']) ?>
                    </p>
                    
                    <div class="course-stats">
                        <div class="stat-item">
                            <span>👥</span>
                            <span><?= $course['students_count'] ?> учнів</span>
                        </div>
                        <div class="stat-item">
                            <span>🎥</span>
                            <span><?= $course['lessons_count'] ?> уроків</span>
                        </div>
                        <?php if ($course['avg_rating']): ?>
                        <div class="stat-item">
                            <span>⭐</span>
                            <span><?= number_format($course['avg_rating'], 1) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="course-meta">
                        <div class="course-price">
                            <?= $course['is_free'] ? 'Безкоштовно' : formatPrice($course['price']) ?>
                        </div>
                        <div class="course-level level-<?= $course['level'] ?>">
                            <?php
                            $levels = [
                                'beginner' => '🌱 Початковий',
                                'intermediate' => '🌿 Середній',
                                'advanced' => '🌳 Просунутий'
                            ];
                            echo $levels[$course['level']];
                            ?>
                        </div>
                    </div>
                    
                    <div class="course-actions">
                        <a href="course-lessons.php?id=<?= $course['id'] ?>" class="btn-action btn-edit">
                            🎥 Уроки
                        </a>
                        <a href="../course.php?id=<?= $course['id'] ?>" class="btn-action btn-lessons">
                            👁️ Переглянути
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>