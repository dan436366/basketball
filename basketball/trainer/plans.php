<?php
require_once '../config.php';
requireRole('trainer');

$db = Database::getInstance()->getConnection();
$trainerId = $_SESSION['user_id'];

// Отримання планів тренувань
$stmt = $db->prepare("
    SELECT tp.*, u.first_name, u.last_name,
           (SELECT COUNT(*) FROM plan_tasks WHERE plan_id = tp.id) as total_tasks,
           (SELECT COUNT(*) FROM plan_tasks WHERE plan_id = tp.id AND is_completed = 1) as completed_tasks
    FROM training_plans tp
    JOIN users u ON tp.user_id = u.id
    WHERE tp.trainer_id = ?
    ORDER BY 
        CASE tp.status
            WHEN 'active' THEN 1
            WHEN 'pending' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'cancelled' THEN 4
        END,
        tp.created_at DESC
");
$stmt->execute([$trainerId]);
$plans = $stmt->fetchAll();

$pageTitle = 'Плани тренувань';
include '../includes/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
    
    .btn-create-plan {
        padding: 12px 30px;
        background: white;
        color: #11998e;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 700;
        transition: all 0.3s;
    }
    
    .btn-create-plan:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(255,255,255,0.3);
        color: #11998e;
    }
    
    .plans-stats {
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
        color: #11998e;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.95rem;
    }
    
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 25px;
        margin-bottom: 60px;
    }
    
    .plan-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        transition: all 0.3s;
        position: relative;
    }
    
    .plan-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.12);
    }
    
    .plan-status {
        position: absolute;
        top: 20px;
        right: 20px;
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
        background: #cce5ff;
        color: #004085;
    }
    
    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }
    
    .plan-header {
        margin-bottom: 15px;
        padding-right: 100px;
    }
    
    .plan-title {
        font-size: 1.3rem;
        color: #333;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .plan-student {
        color: #666;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .plan-description {
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .plan-dates {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 0.9rem;
    }
    
    .date-item {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
    }
    
    .plan-progress {
        margin-top: 15px;
    }
    
    .progress-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .progress-bar {
        height: 8px;
        background: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        border-radius: 10px;
        transition: width 0.3s;
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
    
    @media (max-width: 768px) {
        .plans-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="page-header">
    <div class="container">
        <div class="page-header-content">
            <div>
                <h1>📋 Плани тренувань</h1>
                <p>Створюйте персоналізовані плани для ваших учнів</p>
            </div>
            <a href="#" onclick="alert('Функція створення плану в розробці'); return false;" class="btn-create-plan">
                + Створити план
            </a>
        </div>
    </div>
</section>

<div class="container">
    <div class="plans-stats">
        <div class="stat-card">
            <div class="stat-value">
                <?php
                $activeCount = 0;
                foreach ($plans as $plan) {
                    if ($plan['status'] === 'active') $activeCount++;
                }
                echo $activeCount;
                ?>
            </div>
            <div class="stat-label">Активних планів</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">
                <?php
                $pendingCount = 0;
                foreach ($plans as $plan) {
                    if ($plan['status'] === 'pending') $pendingCount++;
                }
                echo $pendingCount;
                ?>
            </div>
            <div class="stat-label">В очікуванні</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">
                <?php
                $completedCount = 0;
                foreach ($plans as $plan) {
                    if ($plan['status'] === 'completed') $completedCount++;
                }
                echo $completedCount;
                ?>
            </div>
            <div class="stat-label">Завершених</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= count($plans) ?></div>
            <div class="stat-label">Всього планів</div>
        </div>
    </div>
    
    <?php if (empty($plans)): ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h2>Поки немає планів тренувань</h2>
            <p>Створіть персоналізований план для вашого учня</p>
            <a href="#" onclick="alert('Функція створення плану в розробці'); return false;" class="btn-create-plan">
                + Створити перший план
            </a>
        </div>
    <?php else: ?>
        <div class="plans-grid">
            <?php foreach ($plans as $plan): ?>
            <div class="plan-card">
                <div class="plan-status status-<?= $plan['status'] ?>">
                    <?php
                    $statuses = [
                        'active' => '✅ Активний',
                        'pending' => '⏳ В очікуванні',
                        'completed' => '🎉 Завершено',
                        'cancelled' => '❌ Скасовано'
                    ];
                    echo $statuses[$plan['status']];
                    ?>
                </div>
                
                <div class="plan-header">
                    <h3 class="plan-title"><?= htmlspecialchars($plan['title']) ?></h3>
                    <div class="plan-student">
                        <span>👤</span>
                        <span><?= htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']) ?></span>
                    </div>
                </div>
                
                <?php if ($plan['description']): ?>
                <p class="plan-description">
                    <?= htmlspecialchars($plan['description']) ?>
                </p>
                <?php endif; ?>
                
                <div class="plan-dates">
                    <div class="date-item">
                        <span>📅</span>
                        <span>Старт: <?= date('d.m.Y', strtotime($plan['start_date'])) ?></span>
                    </div>
                    <div class="date-item">
                        <span>🏁</span>
                        <span>Кінець: <?= date('d.m.Y', strtotime($plan['end_date'])) ?></span>
                    </div>
                </div>
                
                <?php if ($plan['total_tasks'] > 0): ?>
                <div class="plan-progress">
                    <div class="progress-header">
                        <span>Прогрес завдань</span>
                        <span><strong><?= $plan['completed_tasks'] ?></strong> з <?= $plan['total_tasks'] ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $plan['total_tasks'] > 0 ? round(($plan['completed_tasks'] / $plan['total_tasks']) * 100) : 0 ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>