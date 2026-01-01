<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?>Basketball Learning</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-brand:hover {
            color: #764ba2 !important;
        }
        
        .nav-link {
            color: #333 !important;
            font-weight: 500;
            margin: 0 10px;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: #667eea !important;
        }
        
        .nav-link.active {
            color: #667eea !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 600;
            background: transparent;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            border-radius: 10px;
            padding: 10px 0;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .dropdown-item:hover {
            background: #f8f9fa;
            color: #667eea;
            padding-left: 25px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 8px;
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin: 20px 0;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .container {
            max-width: 1200px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
                🏀 Basketball Learning
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php">Головна</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/courses.php">Курси</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/trainers.php">Тренери</a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php $user = getCurrentUser(); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar">
                                    <?= strtoupper(mb_substr($user['first_name'], 0, 1)) ?>
                                </div>
                                <?= htmlspecialchars($user['first_name']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Панель адміністратора
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php elseif ($user['role'] === 'trainer'): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/trainer/dashboard.php">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>Мої курси
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/dashboard.php">
                                        <i class="fas fa-book-reader me-2"></i>Мої курси
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/plans.php">
                                        <i class="fas fa-clipboard-list me-2"></i>Плани тренувань
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile.php">
                                    <i class="fas fa-user me-2"></i>Профіль
                                </a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Вийти
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/login.php">Вхід</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-primary" href="<?= BASE_URL ?>/register.php">Реєстрація</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <?php
    $flashMessage = getFlashMessage();
    if ($flashMessage):
    ?>
    <div class="container">
        <div class="alert alert-<?= $flashMessage['type'] ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flashMessage['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <main style="flex: 1;"></main>