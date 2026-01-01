<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Отримання всіх тренерів
$stmt = $db->query("
    SELECT u.*, 
           COUNT(DISTINCT c.id) as courses_count,
           COUNT(DISTINCT e.id) as students_count,
           AVG(r.rating) as avg_rating
    FROM users u
    LEFT JOIN courses c ON u.id = c.trainer_id AND c.is_active = 1
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN reviews r ON c.id = r.course_id
    WHERE u.role = 'trainer' AND u.is_active = 1
    GROUP BY u.id
    ORDER BY courses_count DESC
");
$trainers = $stmt->fetchAll();

$pageTitle = 'Наші тренери';
include 'includes/header.php';
?>

<style>
    .trainers-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 60px 0;
        text-align: center;
    }
    
    .trainers-hero h1 {
        font-size: 2.5rem;
        margin-bottom: 15px;
        font-weight: 700;
    }
    
    .trainers-hero p {
        font-size: 1.2rem;
        opacity: 0.95;
    }
    
    .trainers-section {
        padding: 60px 0;
    }
    
    .trainer-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        transition: all 0.3s;
        margin-bottom: 30px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .trainer-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }
    
    .trainer-header {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        align-items: start;
    }
    
    .trainer-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .trainer-info {
        flex: 1;
    }
    
    .trainer-name {
        font-size: 1.6rem;
        color: #333;
        margin-bottom: 8px;
        font-weight: 700;
    }
    
    .trainer-experience {
        color: #667eea;
        font-weight: 600;
        font-size: 1.05rem;
        margin-bottom: 10px;
    }
    
    .trainer-stats {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
        font-size: 0.95rem;
    }
    
    .stat-item strong {
        color: #333;
        font-size: 1.1rem;
    }
    
    .trainer-bio {
        color: #555;
        line-height: 1.7;
        margin: 15px 0;
        flex-grow: 1;
    }
    
    .trainer-rating {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 15px 0;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .rating-stars {
        color: #ffc107;
        font-size: 1.2rem;
    }
    
    .rating-text {
        color: #666;
        font-size: 0.95rem;
    }
    
    .btn-view-courses {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
        display: block;
        transition: all 0.3s;
        margin-top: auto;
    }
    
    .btn-view-courses:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .no-trainers {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
    }
    
    .no-trainers h3 {
        color: #333;
        margin-bottom: 15px;
        font-size: 1.8rem;
    }
</style>

<!-- Hero Section -->
<section class="trainers-hero">
    <div class="container">
        <h1>👨‍🏫 Наші тренери</h1>
        <p>Професіонали з багаторічним досвідом готові допомогти вам</p>
    </div>
</section>

<!-- Trainers Section -->
<section class="trainers-section">
    <div class="container">
        <?php if (empty($trainers)): ?>
            <div class="no-trainers">
                <h3>Тренерів поки немає</h3>
                <p>Скоро з'являться нові тренери</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($trainers as $trainer): ?>
                <div class="col-md-6">
                    <div class="trainer-card">
                        <div class="trainer-header">
                            <div class="trainer-avatar">
                                <?= strtoupper(mb_substr($trainer['first_name'], 0, 1)) ?>
                            </div>
                            <div class="trainer-info">
                                <h2 class="trainer-name">
                                    <?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?>
                                </h2>
                                <?php if ($trainer['experience_years']): ?>
                                <div class="trainer-experience">
                                    📊 Досвід роботи: <?= $trainer['experience_years'] ?> років
                                </div>
                                <?php endif; ?>
                                <div class="trainer-stats">
                                    <div class="stat-item">
                                        📚 <strong><?= $trainer['courses_count'] ?></strong> курсів
                                    </div>
                                    <div class="stat-item">
                                        👥 <strong><?= $trainer['students_count'] ?></strong> учнів
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($trainer['avg_rating']): ?>
                        <div class="trainer-rating">
                            <div class="rating-stars">
                                <?= str_repeat('⭐', round($trainer['avg_rating'])) ?>
                            </div>
                            <div class="rating-text">
                                <?= number_format($trainer['avg_rating'], 1) ?> / 5.0
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($trainer['bio']): ?>
                        <p class="trainer-bio"><?= htmlspecialchars($trainer['bio']) ?></p>
                        <?php endif; ?>
                        
                        <?php if ($trainer['courses_count'] > 0): ?>
                        <a href="courses.php?trainer=<?= $trainer['id'] ?>" class="btn-view-courses">
                            Переглянути курси тренера
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>