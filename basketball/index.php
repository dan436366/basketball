<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Отримання популярних курсів
$stmt = $db->query("
    SELECT c.*, u.first_name, u.last_name,
           (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count
    FROM courses c
    LEFT JOIN users u ON c.trainer_id = u.id
    WHERE c.is_active = 1
    ORDER BY students_count DESC
    LIMIT 6
");
$popularCourses = $stmt->fetchAll();

// Отримання тренерів
$stmt = $db->query("
    SELECT u.*, COUNT(c.id) as courses_count
    FROM users u
    LEFT JOIN courses c ON u.id = c.trainer_id
    WHERE u.role = 'trainer' AND u.is_active = 1
    GROUP BY u.id
    ORDER BY courses_count DESC
    LIMIT 3
");
$trainers = $stmt->fetchAll();

$pageTitle = 'Головна';
include 'includes/header.php';
?>

<style>
    .hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
    }
    
    .hero h1 {
        font-size: 3rem;
        margin-bottom: 20px;
        font-weight: 700;
    }
    
    .hero p {
        font-size: 1.3rem;
        margin-bottom: 30px;
        opacity: 0.95;
    }
    
    .btn-hero {
        background: white;
        color: #667eea;
        padding: 15px 40px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .btn-hero:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        color: #667eea;
    }
    
    .section {
        padding: 60px 0;
    }
    
    .section-title {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .section-title h2 {
        font-size: 2.5rem;
        color: #333;
        margin-bottom: 10px;
    }
    
    .section-title p {
        color: #666;
        font-size: 1.1rem;
    }
    
    .course-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        transition: all 0.3s;
        margin-bottom: 30px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .course-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }
    
    .course-thumbnail {
        height: 200px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
    }
    
    .course-content {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .course-level {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin-bottom: 10px;
        font-weight: 600;
    }
    
    .level-beginner { background: #e3f2fd; color: #1976d2; }
    .level-intermediate { background: #fff3e0; color: #f57c00; }
    .level-advanced { background: #fce4ec; color: #c2185b; }
    
    .course-title {
        font-size: 1.3rem;
        margin: 10px 0;
        color: #333;
        font-weight: 600;
    }
    
    .course-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 10px 0;
        color: #666;
        font-size: 0.9rem;
    }
    
    .course-price {
        font-size: 1.5rem;
        color: #667eea;
        font-weight: 700;
        margin-top: auto;
    }
    
    .trainer-card {
        text-align: center;
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    
    .trainer-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }
    
    .trainer-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
        font-weight: 700;
    }
    
    .trainer-name {
        font-size: 1.4rem;
        color: #333;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .trainer-courses {
        color: #667eea;
        font-weight: 600;
    }
    
    .features {
        background: #f8f9fa;
    }
    
    .feature-box {
        text-align: center;
        padding: 30px;
    }
    
    .feature-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 2rem;
    }
    
    .feature-title {
        font-size: 1.3rem;
        color: #333;
        margin-bottom: 10px;
        font-weight: 600;
    }
    
    .feature-text {
        color: #666;
    }
    
    .cta-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 80px 0;
        text-align: center;
    }
    
    .cta-section h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
    }
    
    .rating {
        color: #ffc107;
    }
</style>

<!-- Hero секція -->
<section class="hero">
    <div class="container">
        <h1>🏀 Навчайся баскетболу онлайн</h1>
        <p>Професійні тренери, відеоуроки та персональні плани тренувань</p>
        <a href="courses.php" class="btn-hero">Переглянути курси</a>
    </div>
</section>

<!-- Переваги -->
<section class="section features">
    <div class="container">
        <div class="section-title">
            <h2>Чому обирають нас</h2>
            <p>Ваш шлях до професійного баскетболу</p>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon">🎥</div>
                    <h3 class="feature-title">Відеоуроки HD</h3>
                    <p class="feature-text">Якісні відео з детальним поясненням техніки</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon">👨‍🏫</div>
                    <h3 class="feature-title">Професійні тренери</h3>
                    <p class="feature-text">Досвідчені наставники з багаторічною практикою</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Персональні плани</h3>
                    <p class="feature-text">Індивідуальний підхід до кожного учня</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Популярні курси -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Популярні курси</h2>
            <p>Найкращі програми навчання від наших тренерів</p>
        </div>
        <div class="row">
            <?php foreach ($popularCourses as $course): ?>
            <div class="col-md-4">
                <div class="course-card">
                    <div class="course-thumbnail">🏀</div>
                    <div class="course-content">
                        <span class="course-level level-<?= $course['level'] ?>">
                            <?php
                            $levels = ['beginner' => 'Початковий', 'intermediate' => 'Середній', 'advanced' => 'Просунутий'];
                            echo $levels[$course['level']];
                            ?>
                        </span>
                        <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <div class="course-meta">
                            <span>👤 <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></span>
                            <span>👥 <?= $course['students_count'] ?></span>
                            <?php if ($course['avg_rating']): ?>
                            <span class="rating">⭐ <?= number_format($course['avg_rating'], 1) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="course-price">
                            <?= $course['is_free'] ? '<span style="color: #28a745;">Безкоштовно</span>' : formatPrice($course['price']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 30px;">
            <a href="courses.php" class="btn btn-primary btn-lg">Всі курси</a>
        </div>
    </div>
</section>

<!-- Тренери -->
<section class="section" style="background: #f8f9fa;">
    <div class="container">
        <div class="section-title">
            <h2>Наші тренери</h2>
            <p>Професіонали своєї справи</p>
        </div>
        <div class="row">
            <?php foreach ($trainers as $trainer): ?>
            <div class="col-md-4">
                <div class="trainer-card">
                    <div class="trainer-avatar">
                        <?= strtoupper(mb_substr($trainer['first_name'], 0, 1)) ?>
                    </div>
                    <h3 class="trainer-name"><?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?></h3>
                    <?php if ($trainer['experience_years']): ?>
                    <p style="color: #666;">Досвід: <?= $trainer['experience_years'] ?> років</p>
                    <?php endif; ?>
                    <p class="trainer-courses"><?= $trainer['courses_count'] ?> курсів</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA секція -->
<section class="cta-section">
    <div class="container">
        <h2>Готовий почати навчання?</h2>
        <p style="font-size: 1.2rem; margin-bottom: 30px;">Приєднуйся до тисяч учнів, які вже покращують свою гру</p>
        <?php if (isLoggedIn()): ?>
            <a href="courses.php" class="btn-hero">Вибрати курс</a>
        <?php else: ?>
            <a href="register.php" class="btn-hero">Зареєструватись зараз</a>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>