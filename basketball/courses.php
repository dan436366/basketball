<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Фільтри
$level = isset($_GET['level']) ? sanitizeInput($_GET['level']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'popular';

// Пагінація
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Базовий запит
$sql = "
    SELECT c.*, u.first_name, u.last_name,
           (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count
    FROM courses c
    LEFT JOIN users u ON c.trainer_id = u.id
    WHERE c.is_active = 1
";

$params = [];

// Додавання фільтрів
if (!empty($level)) {
    $sql .= " AND c.level = ?";
    $params[] = $level;
}

if (!empty($search)) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Сортування
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY c.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY c.price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY c.created_at DESC";
        break;
    case 'popular':
    default:
        $sql .= " ORDER BY students_count DESC";
        break;
}

// Підрахунок загальної кількості
$countSql = "SELECT COUNT(*) as total FROM courses c WHERE c.is_active = 1";
if (!empty($level)) {
    $countSql .= " AND c.level = ?";
}
if (!empty($search)) {
    $countSql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
}

$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalCourses = $countStmt->fetch()['total'];
$totalPages = ceil($totalCourses / ITEMS_PER_PAGE);

// Отримання курсів
$sql .= " LIMIT " . ITEMS_PER_PAGE . " OFFSET " . $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$pageTitle = 'Каталог курсів';
include 'includes/header.php';
?>

<style>
    .courses-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
    }
    
    .courses-hero h1 {
        font-size: 2.5rem;
        margin-bottom: 15px;
        font-weight: 700;
    }
    
    .filters-section {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .filter-row {
        display: flex;
        gap: 15px;
        align-items: end;
        flex-wrap: wrap;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    .filter-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .filter-input, .filter-select {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    .filter-input:focus, .filter-select:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .btn-filter {
        padding: 10px 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        height: 46px;
    }
    
    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-reset {
        padding: 10px 25px;
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        height: 46px;
    }
    
    .btn-reset:hover {
        background: #f8f9fa;
    }
    
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .results-count {
        font-size: 1.1rem;
        color: #666;
    }
    
    .sort-dropdown {
        padding: 8px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
        cursor: pointer;
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
        position: relative;
    }
    
    .course-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255,255,255,0.95);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
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
    
    .course-description {
        color: #666;
        margin: 10px 0;
        line-height: 1.6;
        flex-grow: 1;
    }
    
    .course-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 15px 0;
        color: #666;
        font-size: 0.9rem;
        flex-wrap: wrap;
    }
    
    .course-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 2px solid #f5f5f5;
    }
    
    .course-price {
        font-size: 1.8rem;
        color: #667eea;
        font-weight: 700;
    }
    
    .btn-view-course {
        padding: 10px 25px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
    }
    
    .btn-view-course:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .rating {
        color: #ffc107;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 40px;
    }
    
    .pagination a, .pagination span {
        padding: 10px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
    }
    
    .pagination a:hover {
        border-color: #667eea;
        background: #667eea;
        color: white;
    }
    
    .pagination .active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .no-results {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
    }
    
    .no-results h3 {
        color: #333;
        margin-bottom: 15px;
        font-size: 1.8rem;
    }
    
    .no-results p {
        color: #666;
        font-size: 1.1rem;
    }
</style>

<!-- Hero секція -->
<section class="courses-hero">
    <div class="container">
        <h1>🏀 Каталог курсів</h1>
        <p>Обирайте курс та починайте своє навчання вже сьогодні</p>
    </div>
</section>

<div class="container">
    <!-- Фільтри -->
    <div class="filters-section">
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">Пошук</label>
                    <input type="text" name="search" class="filter-input" 
                           placeholder="Назва курсу..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Рівень</label>
                    <select name="level" class="filter-select">
                        <option value="">Всі рівні</option>
                        <option value="beginner" <?= $level === 'beginner' ? 'selected' : '' ?>>Початковий</option>
                        <option value="intermediate" <?= $level === 'intermediate' ? 'selected' : '' ?>>Середній</option>
                        <option value="advanced" <?= $level === 'advanced' ? 'selected' : '' ?>>Просунутий</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Сортування</label>
                    <select name="sort" class="filter-select">
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Популярні</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Нові</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Дешевші спочатку</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Дорожчі спочатку</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-filter">Застосувати</button>
                <a href="courses.php" class="btn-reset">Скинути</a>
            </div>
        </form>
    </div>
    
    <!-- Результати -->
    <div class="results-header">
        <div class="results-count">
            Знайдено курсів: <strong><?= $totalCourses ?></strong>
        </div>
    </div>
    
    <?php if (empty($courses)): ?>
        <div class="no-results">
            <h3>😔 Курсів не знайдено</h3>
            <p>Спробуйте змінити параметри пошуку</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($courses as $course): ?>
            <div class="col-md-4">
                <div class="course-card">
                    <div class="course-thumbnail">
                        🏀
                        <?php if ($course['students_count'] > 10): ?>
                        <div class="course-badge">🔥 Популярний</div>
                        <?php endif; ?>
                    </div>
                    <div class="course-content">
                        <span class="course-level level-<?= $course['level'] ?>">
                            <?php
                            $levels = ['beginner' => 'Початковий', 'intermediate' => 'Середній', 'advanced' => 'Просунутий'];
                            echo $levels[$course['level']];
                            ?>
                        </span>
                        <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="course-description">
                            <?= htmlspecialchars(mb_substr($course['description'], 0, 100)) ?>...
                        </p>
                        <div class="course-meta">
                            <span>👨‍🏫 <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></span>
                            <span>👥 <?= $course['students_count'] ?></span>
                            <?php if ($course['avg_rating']): ?>
                            <span class="rating">⭐ <?= number_format($course['avg_rating'], 1) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="course-footer">
                            <div class="course-price">
                                <?= $course['is_free'] ? '<span style="color: #28a745;">Безкоштовно</span>' : formatPrice($course['price']) ?>
                            </div>
                            <a href="course.php?id=<?= $course['id'] ?>" class="btn-view-course">Детальніше</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Пагінація -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&level=<?= $level ?>&search=<?= $search ?>&sort=<?= $sort ?>">← Попередня</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&level=<?= $level ?>&search=<?= $search ?>&sort=<?= $sort ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&level=<?= $level ?>&search=<?= $search ?>&sort=<?= $sort ?>">Наступна →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>