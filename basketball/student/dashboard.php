<?php
require_once '../config.php';
requireRole('student');

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Отримання курсів учня
$stmt = $db->prepare("
    SELECT c.*, e.enrolled_at, e.progress, e.completed_at,
           u.first_name, u.last_name,
           (SELECT COUNT(*) FROM video_lessons WHERE course_id = c.id) as total_lessons
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.trainer_id = u.id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$userId]);
$enrolledCourses = $stmt->fetchAll();

// Статистика
$stmt = $db->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ?");
$stmt->execute([$userId]);
$totalCourses = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ? AND completed_at IS NOT NULL");
$stmt->execute([$userId]);
$completedCourses = $stmt->fetch()['total'];

// Останні плани тренувань
$stmt = $db->prepare("
    SELECT tp.*, u.first_name, u.last_name,
           (SELECT COUNT(*) FROM plan_tasks WHERE plan_id = tp.id) as total_tasks,
           (SELECT COUNT(*) FROM plan_tasks WHERE plan_id = tp.id AND is_completed = 1) as completed_tasks
    FROM training_plans tp
    JOIN users u ON tp.trainer_id = u.id
    WHERE tp.user_id = ?
    ORDER BY tp.created_at DESC
    LIMIT 3
");
$stmt->execute([$userId]);
$trainingPlans = $stmt->fetchAll();

$pageTitle = 'Моя панель';
include '../includes/header.php';
?>

<style>
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .dashboard-header h1 {
        font-size: 2.2rem;
        margin-bottom: 10px;
        font-weight: 700;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    
    .stat-icon.blue {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-icon.green {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    
    .stat-icon.orange {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .stat-info h3 {
        font-size: 2rem;
        color: #333;
        margin-bottom: 5px;
        font-weight: 700;
    }
    
    .stat-info p {
        color: #666;
        margin: 0;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .section-title {
        font-size: 1.8rem;
        color: #333;
        font-weight: 700;
    }
    
    .btn-view-all {
        padding: 10px 20px;
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-view-all:hover {
        background: #667eea;
        color: white;
    }
    
    .course-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        display: flex;
        gap: 20px;
        transition: all 0.3s;
    }
    
    .course-card:hover {
        box-shadow: 0 5px 25px rgba(0,0,0,0.12);
        transform: translateY(-3px);
    }
    
    .course-thumbnail {
        width: 150px;
        height: 120px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
        flex-shrink: 0;
    }
    
    .course-info {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .course-title {
        font-size: 1.3rem;
        color: #333;
        margin-bottom: 8px;
        font-weight: 600;
    }
    
    .course-trainer {
        color: #667eea;
        font-size: 0.95rem;
        margin-bottom: 10px;
    }
    
    .course-meta {
        display: flex;
        gap: 20px;
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    
    .progress-section {
        margin-top: auto;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .progress-bar-container {
        height: 8px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 12px;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        transition: width 0.3s;
    }
    
    .btn-continue {
        padding: 10px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        display: inline-block;
        transition: all 0.3s;
    }
    
    .btn-continue:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .plan-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }
    
    .plan-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
    }
    
    .plan-title {
        font-size: 1.2rem;
        color: #333;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .plan-trainer {
        color: #667eea;
        font-size: 0.9rem;
    }
    
    .plan-status {
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-completed {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .plan-dates {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 12px;
    }
    
    .plan-progress {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .plan-progress-bar {
        flex: 1;
        height: 6px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .plan-progress-fill {
        height: 100%;
        background: #28a745;
        border-radius: 10px;
    }
    
    .plan-progress-text {
        color: #666;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
    }
    
    .empty-state h3 {
        color: #333;
        margin-bottom: 15px;
        font-size: 1.5rem;
    }
    
    .empty-state p {
        color: #666;
        margin-bottom: 20px;
    }
</style>

<!-- Dashboard Header -->
<section class="dashboard-header">
    <div class="container">
        <h1>👋 Вітаємо, <?= htmlspecialchars($_SESSION['user_email']) ?>!</h1>
        <p>Ось ваша статистика навчання</p>
    </div>
</section>

<div class="container">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📚</div>
            <div class="stat-info">
                <h3><?= $totalCourses ?></h3>
                <p>Активних курсів</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <h3><?= $completedCourses ?></h3>
                <p>Завершених курсів</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">🎯</div>
            <div class="stat-info">
                <h3><?= count($trainingPlans) ?></h3>
                <p>Планів тренувань</p>
            </div>
        </div>
    </div>
    
    <!-- My Courses -->
    <div class="section-header">
        <h2 class="section-title">📖 Мої курси</h2>
        <a href="my-courses.php" class="btn-view-all">Всі курси</a>
    </div>
    
    <?php if (empty($enrolledCourses)): ?>
        <div class="empty-state">
            <h3>📚 У вас поки немає курсів</h3>
            <p>Час почати навчання! Виберіть курс з нашого каталогу</p>
            <a href="../courses.php" class="btn-continue">Переглянути курси</a>
        </div>
    <?php else: ?>
        <?php foreach (array_slice($enrolledCourses, 0, 3) as $course): ?>
        <div class="course-card">
            <div class="course-thumbnail">🏀</div>
            <div class="course-info">
                <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                <div class="course-trainer">
                    👨‍🏫 <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?>
                </div>
                <div class="course-meta">
                    <span>📅 Записано: <?= formatDate($course['enrolled_at']) ?></span>
                    <span>🎥 <?= $course['total_lessons'] ?> уроків</span>
                </div>
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Прогрес</span>
                        <span><strong><?= $course['progress'] ?>%</strong></span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: <?= $course['progress'] ?>%"></div>
                    </div>
                    <a href="course-view.php?id=<?= $course['id'] ?>" class="btn-continue">
                        <?= $course['progress'] > 0 ? 'Продовжити навчання' : 'Почати навчання' ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Training Plans -->
    <?php if (!empty($trainingPlans)): ?>
    <div class="section-header" style="margin-top: 50px;">
        <h2 class="section-title">📋 Плани тренувань</h2>
        <a href="plans.php" class="btn-view-all">Всі плани</a>
    </div>
    
    <?php foreach ($trainingPlans as $plan): ?>
    <div class="plan-card">
        <div class="plan-header">
            <div>
                <h3 class="plan-title"><?= htmlspecialchars($plan['title']) ?></h3>
                <div class="plan-trainer">
                    👨‍🏫 Тренер: <?= htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']) ?>
                </div>
            </div>
            <span class="plan-status status-<?= $plan['status'] ?>">
                <?php
                $statuses = [
                    'pending' => 'Очікується',
                    'active' => 'Активний',
                    'completed' => 'Завершений',
                    'cancelled' => 'Скасований'
                ];
                echo $statuses[$plan['status']];
                ?>
            </span>
        </div>
        <div class="plan-dates">
            📅 <?= formatDate($plan['start_date']) ?> - <?= formatDate($plan['end_date']) ?>
        </div>
        <div class="plan-progress">
            <div class="plan-progress-bar">
                <?php 
                $planProgress = $plan['total_tasks'] > 0 
                    ? round(($plan['completed_tasks'] / $plan['total_tasks']) * 100) 
                    : 0;
                ?>
                <div class="plan-progress-fill" style="width: <?= $planProgress ?>%"></div>
            </div>
            <span class="plan-progress-text">
                <?= $plan['completed_tasks'] ?> / <?= $plan['total_tasks'] ?> завдань
            </span>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>