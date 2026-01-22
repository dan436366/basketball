<?php
require_once '../config.php';
requireRole('trainer');

$db = Database::getInstance()->getConnection();
$trainerId = $_SESSION['user_id'];

// Отримання всіх учнів тренера
$stmt = $db->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
           COUNT(DISTINCT e.course_id) as enrolled_courses,
           COUNT(DISTINCT CASE WHEN e.completed_at IS NOT NULL THEN e.course_id END) as completed_courses,
           AVG(e.progress) as avg_progress
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    JOIN courses c ON e.course_id = c.id
    WHERE c.trainer_id = ? AND u.role = 'student'
    GROUP BY u.id
    ORDER BY u.first_name ASC
");
$stmt->execute([$trainerId]);
$students = $stmt->fetchAll();

$pageTitle = 'Мої учні';
include '../includes/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .students-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        text-align: center;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #4facfe;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.95rem;
    }
    
    .students-list {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 60px;
    }
    
    .list-header {
        padding: 25px;
        background: #f8f9fa;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .list-title {
        font-size: 1.5rem;
        color: #333;
        font-weight: 700;
    }
    
    .student-item {
        padding: 25px;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.3s;
    }
    
    .student-item:hover {
        background: #f8f9fa;
    }
    
    .student-item:last-child {
        border-bottom: none;
    }
    
    .student-main {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 15px;
    }
    
    .student-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .student-info {
        flex: 1;
    }
    
    .student-name {
        font-size: 1.3rem;
        color: #333;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .student-contact {
        color: #666;
        font-size: 0.95rem;
    }
    
    .student-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .student-stat {
        text-align: center;
    }
    
    .student-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #4facfe;
        margin-bottom: 3px;
    }
    
    .student-stat-label {
        font-size: 0.85rem;
        color: #666;
    }
    
    .progress-bar-wrapper {
        margin-top: 10px;
    }
    
    .progress-label {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 5px;
    }
    
    .progress-bar {
        height: 8px;
        background: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        border-radius: 10px;
        transition: width 0.3s;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 20px;
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
    }
</style>

<section class="page-header">
    <div class="container">
        <h1>👥 Мої учні</h1>
        <p>Переглядайте прогрес та успіхи ваших учнів</p>
    </div>
</section>

<div class="container">
    <div class="students-stats">
        <div class="stat-card">
            <div class="stat-value"><?= count($students) ?></div>
            <div class="stat-label">Всього учнів</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">
                <?php
                $activeStudents = 0;
                foreach ($students as $student) {
                    if ($student['avg_progress'] < 100) $activeStudents++;
                }
                echo $activeStudents;
                ?>
            </div>
            <div class="stat-label">Активних учнів</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">
                <?php
                $completedStudents = 0;
                foreach ($students as $student) {
                    if ($student['completed_courses'] > 0) $completedStudents++;
                }
                echo $completedStudents;
                ?>
            </div>
            <div class="stat-label">Завершили курси</div>
        </div>
    </div>
    
    <div class="students-list">
        <div class="list-header">
            <h2 class="list-title">Список учнів</h2>
        </div>
        
        <?php if (empty($students)): ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h2>Поки немає учнів</h2>
                <p>Коли хтось запишеться на ваші курси, вони з'являться тут</p>
            </div>
        <?php else: ?>
            <?php foreach ($students as $student): ?>
            <div class="student-item">
                <div class="student-main">
                    <div class="student-avatar">
                        <?= strtoupper(mb_substr($student['first_name'], 0, 1)) ?>
                    </div>
                    
                    <div class="student-info">
                        <h3 class="student-name">
                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                        </h3>
                        <div class="student-contact">
                            📧 <?= htmlspecialchars($student['email']) ?>
                            <?php if ($student['phone']): ?>
                                | 📱 <?= htmlspecialchars($student['phone']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="student-stats">
                    <div class="student-stat">
                        <div class="student-stat-value"><?= $student['enrolled_courses'] ?></div>
                        <div class="student-stat-label">Записано на курси</div>
                    </div>
                    
                    <div class="student-stat">
                        <div class="student-stat-value"><?= $student['completed_courses'] ?></div>
                        <div class="student-stat-label">Завершено курсів</div>
                    </div>
                    
                    <div class="student-stat">
                        <div class="student-stat-value"><?= round($student['avg_progress']) ?>%</div>
                        <div class="student-stat-label">Середній прогрес</div>
                    </div>
                    
                    <div class="student-stat">
                        <div class="student-stat-value">
                            <?= date('d.m.Y', strtotime($student['created_at'])) ?>
                        </div>
                        <div class="student-stat-label">Дата реєстрації</div>
                    </div>
                </div>
                
                <div class="progress-bar-wrapper">
                    <div class="progress-label">Загальний прогрес</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= round($student['avg_progress']) ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>