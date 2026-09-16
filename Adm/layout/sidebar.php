<?php
/**
 * Боковое меню админ-панели
 */

$currentRoute = $_GET['route'] ?? 'dashboard';
$active = function($route) use ($currentRoute) {
    return $currentRoute === $route ? 'active' : '';
};
?>

<aside class="admin-sidebar" role="navigation">
    <div class="sidebar-logo">
        <span class="sidebar-logo-icon">
            <svg class="icon icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
        </span>
        <span class="sidebar-logo-text"><?= SITE_NAME ?></span>
    </div>
    
    <nav class="sidebar-nav">
        <div class="sidebar-subtitle">Основное</div>
        
        <a href="?route=dashboard" class="sidebar-item <?= $active('dashboard') ?>">
            <span class="sidebar-item-icon">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </span>
            <span class="sidebar-item-text">Дашборд</span>
        </a>
        
        <?php if ($auth->canManageUsers()): ?>
            <a href="?route=users" class="sidebar-item <?= $active('users') ?>">
                <span class="sidebar-item-icon">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </span>
                <span class="sidebar-item-text">Пользователи</span>
            </a>
        <?php endif; ?>
        
        <?php if ($auth->canManageGroups()): ?>
            <a href="?route=groups" class="sidebar-item <?= $active('groups') ?>">
                <span class="sidebar-item-icon">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
                <span class="sidebar-item-text">Группы</span>
            </a>
        <?php endif; ?>
        
        <div class="sidebar-subtitle">Контент</div>
        

		<a href="?route=media" class="sidebar-item <?= $active('media') ?>">
			<span class="sidebar-item-icon">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
					<circle cx="8.5" cy="8.5" r="1.5"/>
					<polyline points="21 15 16 10 5 21"/>
				</svg>
			</span>
			<span class="sidebar-item-text">Медиа</span>
		</a>		
        
		<a href="?route=modules" class="sidebar-item <?= $active('modules') ?>">
			<span class="sidebar-item-icon">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
					<polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
					<line x1="12" y1="22.08" x2="12" y2="12"/>
				</svg>
			</span>
			<span class="sidebar-item-text">Модули</span>
		</a>

		<a href="?route=blocks" class="sidebar-item <?= $active('blocks') ?>">
			<span class="sidebar-item-icon">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="3" width="7" height="7"/>
					<rect x="14" y="3" width="7" height="7"/>
					<rect x="14" y="14" width="7" height="7"/>
					<rect x="3" y="14" width="7" height="7"/>
				</svg>
			</span>
			<span class="sidebar-item-text">Блоки</span>
		</a>

        <a href="?route=menu" class="sidebar-item <?= $active('menu') ?>">
            <span class="sidebar-item-icon">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </span>
            <span class="sidebar-item-text">Меню</span>
        </a>		

		<a href="?route=galleries" class="sidebar-item <?= $active('galleries') ?>">
			<span class="sidebar-item-icon">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
					<circle cx="8.5" cy="8.5" r="1.5"/>
					<polyline points="21 15 16 10 5 21"/>
				</svg>
			</span>
			<span class="sidebar-item-text">Галереи</span>
		</a>

		<a href="?route=articles" class="sidebar-item <?= $active('articles') ?>">
			<span class="sidebar-item-icon">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
					<polyline points="14 2 14 8 20 8"/>
					<line x1="16" y1="13" x2="8" y2="13"/>
					<line x1="16" y1="17" x2="8" y2="17"/>
					<polyline points="10 9 9 9 8 9"/>
				</svg>
			</span>
			<span class="sidebar-item-text">Статьи</span>
		</a>

		<?php if ($auth->isSuperAdmin()): ?>		
		<a href="?route=modules&action=editor" class="sidebar-item <?= isset($_GET['action']) && $_GET['action'] === 'editor' ? 'active' : '' ?>">
			<span class="sidebar-item-icon">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="16 18 22 12 16 6"/>
					<polyline points="8 6 2 12 8 18"/>
				</svg>
			</span>
			<span class="sidebar-item-text">Файловый менеджер</span>
		</a>

		<?php endif; ?>

        
        <div class="sidebar-subtitle">Система</div>

		<a href="?route=help" class="sidebar-item <?= $active('help') ?>">
			<span class="sidebar-item-icon">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="10"/>
					<path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
					<line x1="12" y1="17" x2="12.01" y2="17"/>
				</svg>
			</span>
			<span class="sidebar-item-text">Помощь</span>
		</a>

        
		<a href="?route=settings" class="sidebar-item <?= $active('settings') ?>">
			<span class="sidebar-item-icon">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/>
					<path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
				</svg>
			</span>
			<span class="sidebar-item-text">Настройки</span>
		</a>
    </nav>
    
    <div class="sidebar-bottom">
        <a href="/" class="sidebar-item" target="_blank">
            <span class="sidebar-item-icon">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                </svg>
            </span>
            <span class="sidebar-item-text">Перейти на сайт</span>
        </a>
    </div>
</aside>