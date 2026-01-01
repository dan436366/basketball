<?php
require_once 'config.php';

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$courseId) {
    header('Location: courses.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Отримання інформації про курс
$stmt = $db->prepare("
    SELECT c.*, u.first_name, u.last_name, u.bio, u.experience_years,
           (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE course_id = c.id) as reviews_count,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count
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

// Перевірка, чи користувач вже записаний на курс
$isEnrolled = false;
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $courseId]);
    $isEnrolled = $stmt->fetch() !== false;
}

// Отримання відеоуроків
$stmt = $db->prepare("
    SELECT * FROM video_lessons 
    WHERE course_id = ? 
    ORDER BY order_number ASC
");
$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll();

// Отримання відгуків
$stmt = $db->prepare("
    SELECT r.*, u.first_name, u.last_name, u.avatar
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.course_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->execute([$courseId]);
$reviews = $stmt->fetchAll();

$pageTitle = $course['title'];
include 'includes/header.php';
?>

<style>
    .course-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 60px 0;
    }
    
    .course-header h1 {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 20px;
    }
    
    .course-meta-header {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
        font-size: 1.1rem;
    }
    
    .course-meta-header > div {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .course-level-badge {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .badge-beginner { background: rgba(255,255,255,0.2); }
    .badge-intermediate { background: rgba(255,193,7,0.3); }
    .badge-advanced { background: rgba(255,82,82,0.3); }
    
    .main-content {
        padding: 40px 0;
    }
    
    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    
    .course-section {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .section-title {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 20px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .course-description {
        color: #555;
        line-height: 1.8;
        font-size: 1.05rem;
    }
    
    .lessons-list {
        list-style: none;
        padding: 0;
    }
    
    .lesson-item {
        padding: 15px;
        border: 2px solid #f0f0f0;
        border-radius: 10px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
    }
    
    .lesson-item:hover {
        border-color: #667eea;
        background: #f8f9ff;
    }
    
    .lesson-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .lesson-number {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }
    
    .lesson-title {
        font-weight: 600;
        color: #333;
    }
    
    .lesson-duration {
        color: #666;
        font-size: 0.9rem;
    }
    
    .lesson-locked {
        color: #999;
        font-size: 0.9rem;
    }
    
    .sidebar-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        position: sticky;
        top: 20px;
    }
    
    .price-section {
        text-align: center;
        padding: 20px 0;
        border-bottom: 2px solid #f0f0f0;
        margin-bottom: 20px;
    }
    
    .price-label {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 5px;
    }
    
    .price-amount {
        font-size: 3rem;
        color: #667eea;
        font-weight: 700;
    }
    
    .btn-enroll {
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
        text-decoration: none;
        display: block;
        text-align: center;
    }
    
    .btn-enroll:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-enrolled {
        background: #28a745;
        cursor: default;
    }
    
    .btn-enrolled:hover {
        transform: none;
    }
    
    .course-includes {
        list-style: none;
        padding: 0;
    }
    
    .course-includes li {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #555;
    }
    
    .course-includes li:last-child {
        border-bottom: none;
    }
    
    .trainer-card {
        display: flex;
        gap: 20px;
        align-items: start;
    }
    
    .trainer-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .trainer-info h3 {
        color: #333;
        margin-bottom: 5px;
    }
    
    .trainer-experience {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .trainer-bio {
        color: #666;
        line-height: 1.6;
    }
    
    .review-item {
        padding: 20px;
        border: 2px solid #f0f0f0;
        border-radius: 10px;
        margin-bottom: 15px;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 10px;
    }
    
    .review-user {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    
    .review-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
    }
    
    .review-user-name {
        font-weight: 600;
        color: #333;
    }
    
    .review-date {
        color: #999;
        font-size: 0.85rem;
    }
    
    .review-rating {
        color: #ffc107;
        font-size: 1.1rem;
    }
    
    .review-text {
        color: #555;
        line-height: 1.6;
    }
    
    @media (max-width: 992px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
        
        .sidebar-card {
            position: static;
        }
    }
</style>

<!-- Course Header -->
<section class="course-header">
    <div class="container">
        <span class="course-level-badge badge-<?= $course['level'] ?>">
            <?php
            $levels = ['beginner' => 'Початковий рівень', 'intermediate' => 'Середній рівень', 'advanced' => 'Просунутий рівень'];
            echo $levels[$course['level']];
            ?>
        </span>
        <h1><?= htmlspecialchars($course['title']) ?></h1>
        <div class="course-meta-header">
            <div>
                <span>👨‍🏫</span>
                <span><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></span>
            </div>
            <div>
                <span>👥</span>
                <span><?= $course['students_count'] ?> учнів</span>
            </div>
            <?php if ($course['avg_rating']): ?>
            <div>
                <span>⭐</span>
                <span><?= number_format($course['avg_rating'], 1) ?> (<?= $course['reviews_count'] ?> відгуків)</span>
            </div>
            <?php endif; ?>
            <div>
                <span>📅</span>
                <span><?= $course['duration_weeks'] ?> тижнів</span>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container main-content">
    <div class="content-grid">
        <!-- Left Column -->
        <div>
            <!-- Description -->
            <div class="course-section">
                <h2 class="section-title">📖 Про курс</h2>
                <p class="course-description"><?= nl2br(htmlspecialchars($course['description'])) ?></p>
            </div>
            
            <!-- Lessons -->
            <div class="course-section">
                <h2 class="section-title">🎥 Програма курсу (<?= count($lessons) ?> уроків)</h2>
                <ul class="lessons-list">
                    <?php foreach ($lessons as $index => $lesson): ?>
                    <li class="lesson-item">
                        <div class="lesson-info">
                            <div class="lesson-number"><?= $index + 1 ?></div>
                            <div>
                                <div class="lesson-title"><?= htmlspecialchars($lesson['title']) ?></div>
                                <?php if ($lesson['duration_minutes']): ?>
                                <div class="lesson-duration">⏱️ <?= $lesson['duration_minutes'] ?> хв</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($lesson['is_free']): ?>
                            <span style="color: #28a745; font-weight: 600;">🎁 Безкоштовно</span>
                        <?php elseif (!$isEnrolled): ?>
                            <span class="lesson-locked">🔒 Заблоковано</span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Trainer -->
            <div class="course-section">
                <h2 class="section-title">👨‍🏫 Про тренера</h2>
                <div class="trainer-card">
                    <div class="trainer-avatar">
                        <?= strtoupper(mb_substr($course['first_name'], 0, 1)) ?>
                    </div>
                    <div class="trainer-info">
                        <h3><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></h3>
                        <?php if ($course['experience_years']): ?>
                        <div class="trainer-experience">Досвід: <?= $course['experience_years'] ?> років</div>
                        <?php endif; ?>
                        <?php if ($course['bio']): ?>
                        <p class="trainer-bio"><?= htmlspecialchars($course['bio']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Reviews -->
            <?php if (!empty($reviews)): ?>
            <div class="course-section">
                <h2 class="section-title">⭐ Відгуки учнів</h2>
                <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="review-user">
                            <div class="review-avatar">
                                <?= strtoupper(mb_substr($review['first_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="review-user-name"><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?></div>
                                <div class="review-date"><?= formatDate($review['created_at']) ?></div>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?= str_repeat('⭐', $review['rating']) ?>
                        </div>
                    </div>
                    <p class="review-text"><?= htmlspecialchars($review['comment']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Sidebar -->
        <div>
            <div class="sidebar-card">
                <div class="price-section">
                    <div class="price-label">Вартість курсу</div>
                    <div class="price-amount"><?= formatPrice($course['price']) ?></div>
                </div>
                
                <?php if ($isEnrolled): ?>
                    <a href="student/course-view.php?id=<?= $course['id'] ?>" class="btn-enroll btn-enrolled">
                        ✅ Ви записані на курс
                    </a>
                <?php elseif (isLoggedIn()): ?>
                    <a href="payment.php?course_id=<?= $course['id'] ?>" class="btn-enroll">
                        🛒 Купити курс
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-enroll">
                        Увійти для покупки
                    </a>
                <?php endif; ?>
                
                <ul class="course-includes" style="margin-top: 25px;">
                    <li>
                        <span style="color: #667eea;">✓</span>
                        <span><?= count($lessons) ?> відеоуроків</span>
                    </li>
                    <li>
                        <span style="color: #667eea;">✓</span>
                        <span>Доступ назавжди</span>
                    </li>
                    <li>
                        <span style="color: #667eea;">✓</span>
                        <span>Сертифікат після завершення</span>
                    </li>
                    <li>
                        <span style="color: #667eea;">✓</span>
                        <span>Підтримка тренера</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>