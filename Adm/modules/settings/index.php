<?php
/**
 * Модуль Settings - настройки сайта
 * Вкладки: Основные, Внешний вид, Контакты, Соцсети, SEO, Система
 */

if (!$auth->hasAdminAccess('settings', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

$db = Database::getInstance();

// Получаем все настройки из БД
$allSettings = [];
$rows = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings");
foreach ($rows as $row) {
    $allSettings[$row['setting_key']] = $row['setting_value'];
}

// Активная вкладка
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'main';

$tabs = [
    'main' => '📋 Основные',
    'appearance' => '🎨 Внешний вид',
    'contacts' => '📞 Контакты',
    'social' => '🔗 Соцсети',
    'seo' => '🔍 SEO',
    'system' => '⚙️ Система',
];

// Хелпер для получения значения
function sv($allSettings, $key, $default = '') {
    return isset($allSettings[$key]) ? $allSettings[$key] : $default;
}
?>

<style>
.module-settings .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.module-settings .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}

/* Вкладки */
.settings-tabs {
    display: flex;
    gap: 4px;
    border-bottom: 2px solid var(--admin-border);
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.settings-tab {
    padding: 10px 20px;
    color: var(--admin-text-secondary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    border-radius: 4px 4px 0 0;
}

.settings-tab:hover {
    color: var(--admin-text-primary);
    background: var(--admin-bg-hover);
}

.settings-tab.active {
    color: var(--admin-accent);
    border-bottom-color: var(--admin-accent);
    background: transparent;
}

/* Форма */
.settings-form {
    max-width: 800px;
}

.settings-form .admin-form-group {
    margin-bottom: 20px;
}

.settings-form .form-hint {
    color: var(--admin-text-muted);
    font-size: 12px;
    margin-top: 4px;
}

.settings-form .admin-form-control {
    width: 100%;
    padding: 10px 14px;
    font-family: var(--font-family);
    font-size: 14px;
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    background: var(--admin-bg-primary);
    color: var(--admin-text-primary);
    transition: border-color 0.2s ease;
}

.settings-form .admin-form-control:focus {
    outline: none;
    border-color: var(--admin-accent);
}

.settings-form textarea.admin-form-control {
    min-height: 100px;
    resize: vertical;
}

.settings-form select.admin-form-control {
    cursor: pointer;
}

.settings-form .form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--admin-border);
}

@media (max-width: 768px) {
    .settings-tabs {
        flex-direction: column;
        border-bottom: none;
    }
    .settings-tab {
        border-bottom: 1px solid var(--admin-border);
        border-left: 3px solid transparent;
        margin-bottom: 0;
        border-radius: 0;
    }
    .settings-tab.active {
        border-left-color: var(--admin-accent);
        border-bottom-color: var(--admin-border);
    }
}

/* Загрузка изображений */
.image-upload-wrapper {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.image-preview {
    display: inline-block;
    padding: 12px;
    background: var(--admin-bg-primary);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    max-width: 100%;
}

.image-preview img {
    display: block;
    max-width: 400px;
    max-height: 100px;
    width: auto;
    height: auto;
}

.image-preview-favicon img {
    max-width: 64px;
    max-height: 64px;
}

.image-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
</style>

<div class="module-settings">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            Настройки сайта
        </h2>
    </div>
    
    <!-- Вкладки -->
    <div class="settings-tabs">
        <?php foreach ($tabs as $key => $title): ?>
            <a href="?route=settings&tab=<?= $key ?>" 
               class="settings-tab <?= $activeTab === $key ? 'active' : '' ?>">
                <?= $title ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Форма -->
    <div class="admin-card">
        <form class="settings-form" id="settingsForm">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
            
            <?php if ($activeTab === 'main'): ?>
                <!-- ОСНОВНЫЕ -->
                <div class="admin-form-group">
                    <label for="site_name">Название сайта *</label>
                    <input type="text" id="site_name" name="site_name" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'site_name')) ?>" required>
                    <div class="form-hint">Отображается в шапке и в title страниц</div>
                </div>
                
                <div class="admin-form-group">
                    <label for="site_description">Описание сайта</label>
                    <textarea id="site_description" name="site_description" class="admin-form-control" rows="3"><?= htmlspecialchars(sv($allSettings, 'site_description')) ?></textarea>
                    <div class="form-hint">Используется как meta description по умолчанию</div>
                </div>
                
                <div class="admin-form-group">
                    <label for="site_keywords">Ключевые слова</label>
                    <input type="text" id="site_keywords" name="site_keywords" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'site_keywords')) ?>" 
                           placeholder="cms, сайт, разработка">
                    <div class="form-hint">Через запятую. Используется как meta keywords</div>
                </div>
                
                <div class="admin-form-group">
                    <label for="site_email">Email сайта</label>
                    <input type="email" id="site_email" name="site_email" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'site_email')) ?>" 
                           placeholder="admin@example.com">
                    <div class="form-hint">Основной email для уведомлений</div>
                </div>
                
                <div class="admin-form-group">
                    <label for="site_language">Язык по умолчанию</label>
                    <select id="site_language" name="site_language" class="admin-form-control">
                        <option value="ru" <?= sv($allSettings, 'site_language', 'ru') === 'ru' ? 'selected' : '' ?>>Русский</option>
                        <option value="en" <?= sv($allSettings, 'site_language') === 'en' ? 'selected' : '' ?>>English</option>
                    </select>
                </div>
                
                <div class="admin-form-group">
                    <label for="site_timezone">Часовой пояс</label>
                    <select id="site_timezone" name="site_timezone" class="admin-form-control">
                        <?php
                        $timezones = ['Europe/Moscow', 'Europe/Kiev', 'Europe/Minsk', 'Asia/Almaty', 'Asia/Yekaterinburg', 'Asia/Novosibirsk', 'UTC'];
                        $currentTz = sv($allSettings, 'site_timezone', 'Europe/Moscow');
                        foreach ($timezones as $tz):
                        ?>
                            <option value="<?= $tz ?>" <?= $currentTz === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            
                        <?php elseif ($activeTab === 'appearance'): ?>
                <!-- ВНЕШНИЙ ВИД -->
                
                <!-- Логотип -->
                <div class="admin-form-group">
                    <label>Логотип сайта</label>
                    <div class="form-hint" style="margin-bottom: 12px;">
                        📐 <strong>Рекомендуемый размер:</strong> до 400×100 px<br>
                        📁 <strong>Форматы:</strong> PNG, SVG, JPG<br>
                        💡 <strong>Совет:</strong> используйте PNG с прозрачным фоном
                    </div>
                    
                    <div class="image-upload-wrapper">
                        <!-- Текущий логотип (картинка) -->
                        <div class="image-preview" id="logo-image-preview" style="<?= sv($allSettings, 'logo_image') ? '' : 'display: none;' ?>">
                            <?php if (sv($allSettings, 'logo_image')): ?>
                                <img src="<?= htmlspecialchars(sv($allSettings, 'logo_image')) ?>" alt="Логотип">
                            <?php endif; ?>
                        </div>
                        
                        <!-- Поле для текстового логотипа (fallback) -->
                        <div id="logo-text-wrapper" style="<?= sv($allSettings, 'logo_image') ? 'display: none;' : '' ?>">
                            <label for="logo_text" style="font-size: 13px; color: var(--admin-text-muted);">Текст логотипа (если нет картинки)</label>
                            <input type="text" id="logo_text" name="logo_text" class="admin-form-control" 
                                   value="<?= htmlspecialchars(sv($allSettings, 'logo_text', 'ZoomCRM')) ?>">
                        </div>
                        
                        <input type="hidden" name="logo_image" id="logo_image" value="<?= htmlspecialchars(sv($allSettings, 'logo_image')) ?>">
                        
                        <div class="image-actions">
                            <button type="button" class="btn-admin btn-admin-primary" id="btn-upload-logo">
                                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                Загрузить логотип
                            </button>
                            <button type="button" class="btn-admin btn-admin-danger" id="btn-delete-logo" style="<?= sv($allSettings, 'logo_image') ? '' : 'display: none;' ?>">
                                Удалить
                            </button>
                        </div>
                        
                        <input type="file" id="logo-file-input" accept="image/*" style="display: none;">
                    </div>
                </div>
                
                <!-- Favicon -->
                <div class="admin-form-group">
                    <label>Favicon (иконка сайта)</label>
                    <div class="form-hint" style="margin-bottom: 12px;">
                        📐 <strong>Рекомендуемый размер:</strong> 32×32 или 64×64 px<br>
                        📁 <strong>Форматы:</strong> ICO, PNG<br>
                        💡 <strong>Совет:</strong> квадратное изображение
                    </div>
                    
                    <div class="image-upload-wrapper">
                        <div class="image-preview image-preview-favicon" id="favicon-preview" style="<?= sv($allSettings, 'favicon') ? '' : 'display: none;' ?>">
                            <?php if (sv($allSettings, 'favicon')): ?>
                                <img src="<?= htmlspecialchars(sv($allSettings, 'favicon')) ?>" alt="Favicon">
                            <?php endif; ?>
                        </div>
                        
                        <input type="hidden" name="favicon" id="favicon" value="<?= htmlspecialchars(sv($allSettings, 'favicon')) ?>">
                        
                        <div class="image-actions">
                            <button type="button" class="btn-admin btn-admin-primary" id="btn-upload-favicon">
                                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                Загрузить favicon
                            </button>
                            <button type="button" class="btn-admin btn-admin-danger" id="btn-delete-favicon" style="<?= sv($allSettings, 'favicon') ? '' : 'display: none;' ?>">
                                Удалить
                            </button>
                        </div>
                        
                        <input type="file" id="favicon-file-input" accept="image/*" style="display: none;">
                    </div>
                </div>
                
                <!-- Акцентный цвет -->
                <div class="admin-form-group">
                    <label for="accent_color">Акцентный цвет</label>
                    <input type="color" id="accent_color" name="accent_color" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'accent_color', '#64b5f6')) ?>" 
                           style="height: 50px; cursor: pointer;">
                    <div class="form-hint">Используется для кнопок и ссылок</div>
                </div>
                
                <!-- Шаблон по умолчанию -->
                <div class="admin-form-group">
                    <label for="default_template">Шаблон по умолчанию</label>
                    <select id="default_template" name="default_template" class="admin-form-control">
                        <?php
                        $manifestFile = ROOT_DIR . '/templates/modules/_manifest.php';
                        $templates = file_exists($manifestFile) ? require $manifestFile : ['default' => 'Стандартный'];
                        $currentTpl = sv($allSettings, 'default_template', 'default');
                        foreach ($templates as $key => $label):
                        ?>
                            <option value="<?= $key ?>" <?= $currentTpl === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- ВНЕШНИЙ ВИД -->
                <div class="admin-form-group">
                    <label for="logo_text">Текст логотипа</label>
                    <input type="text" id="logo_text" name="logo_text" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'logo_text', 'ZoomCRM')) ?>">
                    <div class="form-hint">Отображается в шапке сайта (пока без картинки)</div>
                </div>
                
                <div class="admin-form-group">
                    <label for="accent_color">Акцентный цвет</label>
                    <input type="color" id="accent_color" name="accent_color" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'accent_color', '#64b5f6')) ?>" 
                           style="height: 50px; cursor: pointer;">
                    <div class="form-hint">Используется для кнопок и ссылок</div>
                </div>
                
                <div class="admin-form-group">
                    <label for="default_template">Шаблон по умолчанию</label>
                    <select id="default_template" name="default_template" class="admin-form-control">
                        <?php
                        $manifestFile = ROOT_DIR . '/templates/modules/_manifest.php';
                        $templates = file_exists($manifestFile) ? require $manifestFile : ['default' => 'Стандартный'];
                        $currentTpl = sv($allSettings, 'default_template', 'default');
                        foreach ($templates as $key => $label):
                        ?>
                            <option value="<?= $key ?>" <?= $currentTpl === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            
            <?php elseif ($activeTab === 'contacts'): ?>
                <!-- КОНТАКТЫ -->
                <div class="admin-form-group">
                    <label for="contact_phone">Телефон</label>
                    <input type="text" id="contact_phone" name="contact_phone" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'contact_phone')) ?>" 
                           placeholder="+7 (999) 123-45-67">
                </div>
                
                <div class="admin-form-group">
                    <label for="contact_email">Email для связи</label>
                    <input type="email" id="contact_email" name="contact_email" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'contact_email')) ?>" 
                           placeholder="info@example.com">
                </div>
                
                <div class="admin-form-group">
                    <label for="contact_address">Адрес</label>
                    <textarea id="contact_address" name="contact_address" class="admin-form-control" rows="2"><?= htmlspecialchars(sv($allSettings, 'contact_address')) ?></textarea>
                </div>
                
                <div class="admin-form-group">
                    <label for="contact_working_hours">Режим работы</label>
                    <input type="text" id="contact_working_hours" name="contact_working_hours" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'contact_working_hours')) ?>" 
                           placeholder="Пн-Пт: 9:00 - 18:00">
                </div>
            
            <?php elseif ($activeTab === 'social'): ?>
                <!-- СОЦСЕТИ -->
                <div class="admin-form-group">
                    <label for="social_vk">ВКонтакте</label>
                    <input type="url" id="social_vk" name="social_vk" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'social_vk')) ?>" 
                           placeholder="https://vk.com/...">
                </div>
                
                <div class="admin-form-group">
                    <label for="social_telegram">Telegram</label>
                    <input type="url" id="social_telegram" name="social_telegram" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'social_telegram')) ?>" 
                           placeholder="https://t.me/...">
                </div>
                
                <div class="admin-form-group">
                    <label for="social_whatsapp">WhatsApp</label>
                    <input type="url" id="social_whatsapp" name="social_whatsapp" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'social_whatsapp')) ?>" 
                           placeholder="https://wa.me/...">
                </div>
                
                <div class="admin-form-group">
                    <label for="social_youtube">YouTube</label>
                    <input type="url" id="social_youtube" name="social_youtube" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'social_youtube')) ?>" 
                           placeholder="https://youtube.com/...">
                </div>
                
                <div class="admin-form-group">
                    <label for="social_instagram">Instagram</label>
                    <input type="url" id="social_instagram" name="social_instagram" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'social_instagram')) ?>" 
                           placeholder="https://instagram.com/...">
                </div>
            
            <?php elseif ($activeTab === 'seo'): ?>
                <!-- SEO -->
                <div class="admin-form-group">
                    <label for="seo_meta_title">Meta Title по умолчанию</label>
                    <input type="text" id="seo_meta_title" name="seo_meta_title" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'seo_meta_title')) ?>" 
                           placeholder="Оставьте пустым — будет использовано название сайта">
                    <div class="form-hint">Если у модуля или записи есть своё SEO — оно будет использовано</div>
                </div>
                
                <div class="admin-form-group">
                    <label for="seo_meta_description">Meta Description по умолчанию</label>
                    <textarea id="seo_meta_description" name="seo_meta_description" class="admin-form-control" rows="3"><?= htmlspecialchars(sv($allSettings, 'seo_meta_description')) ?></textarea>
                </div>
                
                <div class="admin-form-group">
                    <label for="seo_google_analytics">Google Analytics ID</label>
                    <input type="text" id="seo_google_analytics" name="seo_google_analytics" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'seo_google_analytics')) ?>" 
                           placeholder="G-XXXXXXXXXX или UA-XXXXXXXX-X">
                </div>
                
                <div class="admin-form-group">
                    <label for="seo_yandex_metrika">Яндекс.Метрика ID</label>
                    <input type="text" id="seo_yandex_metrika" name="seo_yandex_metrika" class="admin-form-control" 
                           value="<?= htmlspecialchars(sv($allSettings, 'seo_yandex_metrika')) ?>" 
                           placeholder="12345678">
                </div>
            
            <?php elseif ($activeTab === 'system'): ?>
                <!-- СИСТЕМА -->
                <div class="admin-form-group">
                    <label class="admin-checkbox">
                        <input type="checkbox" name="maintenance_mode" value="1" 
                               <?= sv($allSettings, 'maintenance_mode') == '1' ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Режим обслуживания
                    </label>
                    <div class="form-hint">Сайт будет показывать выбранный модуль всем посетителям</div>
                </div>
                
                <div class="admin-form-group">
                    <label for="maintenance_module">Модуль для режима обслуживания</label>
                    <select id="maintenance_module" name="maintenance_module" class="admin-form-control">
                        <option value="">— Выберите модуль —</option>
                        <?php
                        $moduleManager = new ModuleManager();
                        $frontendModules = $moduleManager->getFrontendModules(true);
                        $currentMm = sv($allSettings, 'maintenance_module');
                        foreach ($frontendModules as $name => $m):
                        ?>
                            <option value="<?= htmlspecialchars($m['slug']) ?>" <?= $currentMm === $m['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['title'] ?: $name) ?> (<?= htmlspecialchars($m['slug']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">Этот модуль будет показан при включённом режиме обслуживания</div>
                </div>
                
                <div class="admin-form-group">
                    <label>Генерация файлов</label>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px;">
                        <button type="button" class="btn-admin btn-admin-secondary" id="btn-generate-robots">
                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 4 23 10 17 10"/>
                                <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
                            </svg>
                            Перегенерировать robots.txt
                        </button>
                        <button type="button" class="btn-admin btn-admin-secondary" id="btn-generate-sitemap">
                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 4 23 10 17 10"/>
                                <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
                            </svg>
                            Перегенерировать sitemap.xml
                        </button>
                    </div>
                    <div class="form-hint" style="margin-top: 8px;">
                        Файлы создаются автоматически при первом заходе. Перегенерируйте вручную после добавления новых страниц/новостей.
                    </div>
                </div>
                
                <div class="admin-form-group">
                    <label>Текущий robots.txt</label>
                    <div style="background: var(--admin-bg-primary); padding: 12px; border-radius: 4px; font-family: monospace; font-size: 12px; color: var(--admin-text-secondary); max-height: 200px; overflow: auto;">
                        <?php
                        $robotsFile = ROOT_DIR . '/robots.txt';
                        if (file_exists($robotsFile)) {
                            echo '<pre style="margin:0; white-space: pre-wrap;">' . htmlspecialchars(file_get_contents($robotsFile)) . '</pre>';
                        } else {
                            echo '<em style="color: var(--admin-text-muted);">Файл ещё не создан</em>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="admin-form-group">
                    <label>Текущий sitemap.xml</label>
                    <div style="background: var(--admin-bg-primary); padding: 12px; border-radius: 4px; font-family: monospace; font-size: 12px; color: var(--admin-text-secondary); max-height: 200px; overflow: auto;">
                        <?php
                        $sitemapFile = ROOT_DIR . '/sitemap.xml';
                        if (file_exists($sitemapFile)) {
                            $size = filesize($sitemapFile);
                            $modified = date('d.m.Y H:i:s', filemtime($sitemapFile));
                            echo '<em>Файл существует. Размер: ' . round($size / 1024, 2) . ' КБ, изменён: ' . $modified . '</em>';
                        } else {
                            echo '<em style="color: var(--admin-text-muted);">Файл ещё не создан</em>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Кнопки -->
            <div class="form-actions">
                <button type="submit" class="btn-admin btn-admin-success">
                    <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Сохранить настройки
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // ============================================
    // СОХРАНЕНИЕ НАСТРОЕК
    // ============================================
    
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var formData = $form.serializeArray();
        var data = { action: 'save' };
        
        formData.forEach(function(item) {
            data[item.name] = item.value;
        });
        
        // Чекбоксы не передаются, если не отмечены
        $form.find('input[type="checkbox"]').each(function() {
            if (!$(this).is(':checked')) {
                data[$(this).attr('name')] = '0';
            }
        });
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '/Adm/api/settings.api.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification(response.message, 'success');
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка сохранения настроек', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // ============================================
    // ГЕНЕРАЦИЯ ROBOTS.TXT
    // ============================================
    
    $('#btn-generate-robots').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '/Adm/api/settings.api.php',
            type: 'POST',
            data: { action: 'generate_robots' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification(response.message, 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка генерации robots.txt', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // ============================================
    // ГЕНЕРАЦИЯ SITEMAP.XML
    // ============================================
    
    $('#btn-generate-sitemap').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: '/Adm/api/settings.api.php',
            type: 'POST',
            data: { action: 'generate_sitemap' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification(response.message, 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка генерации sitemap.xml', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

	    // ============================================
    // ЗАГРУЗКА ЛОГОТИПА
    // ============================================
    
    $('#btn-upload-logo').on('click', function() {
        $('#logo-file-input').click();
    });
    
    $('#logo-file-input').on('change', function() {
        if (!this.files.length) return;
        
        var formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('type', 'logo');
        formData.append('folder', 'site');
        formData.append('file', this.files[0]);
        
        showAdminNotification('Загрузка логотипа...', 'info');
        
        $.ajax({
            url: '/Adm/api/settings.api.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#logo_image').val(response.path);
                    $('#logo-image-preview').html('<img src="' + response.path + '" alt="Логотип">').show();
                    $('#logo-text-wrapper').hide();
                    $('#btn-delete-logo').show();
                    showAdminNotification('Логотип загружен', 'success');
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка загрузки', 'danger');
            }
        });
        
        $(this).val('');
    });
    
    $('#btn-delete-logo').on('click', function() {
        if (!confirm('Удалить логотип?')) return;
        
        $.ajax({
            url: '/Adm/api/settings.api.php',
            type: 'POST',
            data: { action: 'delete_image', type: 'logo' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#logo_image').val('');
                    $('#logo-image-preview').hide().html('');
                    $('#logo-text-wrapper').show();
                    $('#btn-delete-logo').hide();
                    showAdminNotification('Логотип удалён', 'success');
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            }
        });
    });
    
    // ============================================
    // ЗАГРУЗКА FAVICON
    // ============================================
    
    $('#btn-upload-favicon').on('click', function() {
        $('#favicon-file-input').click();
    });
    
    $('#favicon-file-input').on('change', function() {
        if (!this.files.length) return;
        
        var formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('type', 'favicon');
        formData.append('folder', 'site');
        formData.append('file', this.files[0]);
        
        showAdminNotification('Загрузка favicon...', 'info');
        
        $.ajax({
            url: '/Adm/api/settings.api.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#favicon').val(response.path);
                    $('#favicon-preview').html('<img src="' + response.path + '?t=' + Date.now() + '" alt="Favicon">').show();
                    $('#btn-delete-favicon').show();
                    showAdminNotification('Favicon загружен', 'success');
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка загрузки', 'danger');
            }
        });
        
        $(this).val('');
    });
    
    $('#btn-delete-favicon').on('click', function() {
        if (!confirm('Удалить favicon?')) return;
        
        $.ajax({
            url: '/Adm/api/settings.api.php',
            type: 'POST',
            data: { action: 'delete_image', type: 'favicon' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#favicon').val('');
                    $('#favicon-preview').hide().html('');
                    $('#btn-delete-favicon').hide();
                    showAdminNotification('Favicon удалён', 'success');
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            }
        });
    });
});
</script>