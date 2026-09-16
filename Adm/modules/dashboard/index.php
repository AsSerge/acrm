<?php
/**
 * Модуль Dashboard - дашборд админ-панели
 */

if (!$auth->hasAdminAccess('dashboard', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

$db = Database::getInstance();

$userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'] ?? 0;
$adminCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM users u 
     JOIN user_groups ug ON u.group_id = ug.id 
     WHERE ug.group_type = 'admin' AND u.is_active = 1"
)['count'] ?? 0;
$groupCount = $db->fetchOne("SELECT COUNT(*) as count FROM user_groups")['count'] ?? 0;
$moduleCount = $db->fetchOne("SELECT COUNT(*) as count FROM modules_registry WHERE is_active = 1")['count'] ?? 0;

$recentUsers = $db->fetchAll(
    "SELECT u.id, u.username, u.last_login, ug.group_name 
     FROM users u 
     JOIN user_groups ug ON u.group_id = ug.id 
     ORDER BY u.last_login DESC 
     LIMIT 5"
);
?>

<div class="dashboard">
    <!-- Заголовок с иконкой вместо 👋 -->
    <h2 style="font-size: 22px; font-weight: 600; margin-bottom: 24px; color: var(--admin-text-primary); display: flex; align-items: center; gap: 10px;">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-accent); width: 28px; height: 28px;">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
            <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
            <line x1="9" y1="9" x2="9.01" y2="9"/>
            <line x1="15" y1="9" x2="15.01" y2="9"/>
        </svg>
        Добро пожаловать, <?= htmlspecialchars($adminUser['username']) ?>!
    </h2>
    
    <!-- Статистика -->
    <div class="row" style="margin-bottom: 24px;">
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="admin-card" style="text-align: center;">
                <div style="margin-bottom: 4px;">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-accent);">
                        <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div style="font-size: 28px; font-weight: 600; color: var(--admin-text-primary);"><?= $userCount ?></div>
                <div style="color: var(--admin-text-muted); font-size: 13px;">Пользователей</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="admin-card" style="text-align: center;">
                <div style="margin-bottom: 4px;">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-accent);">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>
                </div>
                <div style="font-size: 28px; font-weight: 600; color: var(--admin-text-primary);"><?= $adminCount ?></div>
                <div style="color: var(--admin-text-muted); font-size: 13px;">Администраторов</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="admin-card" style="text-align: center;">
                <div style="margin-bottom: 4px;">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-accent);">
                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div style="font-size: 28px; font-weight: 600; color: var(--admin-text-primary);"><?= $groupCount ?></div>
                <div style="color: var(--admin-text-muted); font-size: 13px;">Групп</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="admin-card" style="text-align: center;">
                <div style="margin-bottom: 4px;">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-accent);">
                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                </div>
                <div style="font-size: 28px; font-weight: 600; color: var(--admin-text-primary);"><?= $moduleCount ?></div>
                <div style="color: var(--admin-text-muted); font-size: 13px;">Активных модулей</div>
            </div>
        </div>
    </div>
    
    <!-- Последние действия -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                Последние входы
            </h3>
        </div>
        <?php if (!empty($recentUsers)): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Пользователь</th>
                        <th>Группа</th>
                        <th>Последний вход</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['group_name']) ?></td>
                            <td><?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--admin-text-muted);">Нет данных о входах</p>
        <?php endif; ?>
    </div>
    
    <!-- Быстрые действия с иконкой ⚡ -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px; stroke: var(--admin-warning);">
                    <polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
                Быстрые действия
            </h3>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <?php if ($auth->canManageUsers()): ?>
                <a href="?route=users" class="btn-admin btn-admin-primary">
                    <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Управление пользователями
                </a>
            <?php endif; ?>
            <?php if ($auth->canManageGroups()): ?>
                <a href="?route=groups" class="btn-admin btn-admin-secondary">
                    <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Управление группами
                </a>
            <?php endif; ?>
            <a href="/" class="btn-admin btn-admin-secondary" target="_blank">
                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                </svg>
                Перейти на сайт
            </a>
        </div>
    </div>
</div>