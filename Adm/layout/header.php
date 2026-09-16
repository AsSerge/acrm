<?php
/**
 * Шапка админ-панели
 */
?>
<!DOCTYPE html>
<html lang="<?= DEFAULT_LANGUAGE ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - <?= SITE_NAME ?></title>
    
    <link rel="stylesheet" href="/assets/css/grid.css">
    <link rel="stylesheet" href="/Adm/assets/css/style.css">

	<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
	<script src="/vendor/sortable/Sortable.min.js"></script>
	<script src="/Adm/assets/js/admin-core.js"></script>
	<script src="/Adm/assets/js/script.js"></script>
    
    <!-- Стили для иконок -->
    <style>
        .icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            vertical-align: middle;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        
        .icon-sm {
            width: 16px;
            height: 16px;
        }
        
        .icon-lg {
            width: 28px;
            height: 28px;
        }
        
        .sidebar-item-icon .icon {
            stroke: var(--admin-text-secondary);
            transition: stroke 0.2s ease;
        }
        
        .sidebar-item:hover .sidebar-item-icon .icon {
            stroke: var(--admin-text-primary);
        }
        
        .sidebar-item.active .sidebar-item-icon .icon {
            stroke: var(--admin-accent);
        }
        
        .btn-admin .icon {
            stroke: currentColor;
        }
    </style>   

</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar-overlay"></div>
        
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        
        <div class="admin-main">
            <header class="admin-header">
                <div class="admin-header-left">
                    <button class="header-hamburger" id="sidebarToggle" aria-label="Переключить меню">
                        <span class="hamburger-icon">
                            <span class="desktop-arrow">◀</span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                        </span>
                    </button>
                    <span class="header-title">
                        Админ-панель
                        <small><?= SITE_NAME ?></small>
                    </span>
                </div>
                
                <div class="admin-header-right">
                    <?php if (isset($adminUser) && $adminUser): ?>
                        <div class="admin-user">
                            <div class="admin-user-avatar">
                                <?= strtoupper(substr($adminUser['username'], 0, 1)) ?>
                            </div>
                            <span class="admin-user-name"><?= htmlspecialchars($adminUser['username']) ?></span>
                        </div>
                        <a href="/Adm/logout.php" class="admin-user-logout">Выйти</a>
                    <?php endif; ?>
                </div>
            </header>
            
            <main class="admin-content">