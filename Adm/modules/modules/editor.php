<?php
/**
 * Модуль Pages - файловый менеджер + редактор кода
 * Доступ: только Супер-админ
 */

if (!$auth->isSuperAdmin()) {
    echo '<div class="admin-alert admin-alert-danger">Доступ к редактору кода только у Супер-админа.</div>';
    return;
}

$db = Database::getInstance();

$filePath = isset($_GET['file']) ? trim($_GET['file']) : '';
$showEditor = !empty($filePath);
?>

<style>
.module-editor {
    height: calc(100vh - 130px);
    display: flex;
    flex-direction: column;
}

.editor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}

.editor-header h2 {
    font-size: 20px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}

.editor-file-path {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--admin-bg-hover);
    padding: 6px 12px;
    border-radius: 4px;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 13px;
    color: var(--admin-text-secondary);
    margin-top: 8px;
}

.editor-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.editor-wrapper {
    flex: 1;
    position: relative;
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

#ace-editor {
    flex: 1;
    width: 100%;
    min-height: 500px;
}

.editor-status {
    padding: 8px 16px;
    background: var(--admin-bg-secondary);
    border-top: 1px solid var(--admin-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: var(--admin-text-muted);
}

.editor-status.saved {
    background: rgba(76, 175, 80, 0.15);
    color: var(--admin-success);
}

.editor-status.error {
    background: rgba(239, 83, 80, 0.15);
    color: var(--admin-danger);
}

/* Файловый менеджер */
.files-list {
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
}

.files-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--admin-border);
    flex-wrap: wrap;
    gap: 12px;
}

.files-list-header h3 {
    margin: 0;
    color: var(--admin-text-primary);
    font-size: 16px;
}

.files-list-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-radius: 4px;
    text-decoration: none;
    color: var(--admin-text-primary);
    transition: background 0.2s;
    border-bottom: 1px solid var(--admin-border-light);
    gap: 12px;
}

.file-item:hover {
    background: var(--admin-bg-hover);
}

.file-item:last-child {
    border-bottom: none;
}

.file-item-left {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
    text-decoration: none;
    color: inherit;
}

.file-item-right {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

.file-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.file-icon svg {
    width: 100%;
    height: 100%;
    stroke: var(--admin-text-secondary);
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.file-meta {
    font-size: 12px;
    color: var(--admin-text-muted);
    white-space: nowrap;
}

.file-action-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    transition: background 0.2s;
}

.file-action-btn:hover {
    background: var(--admin-bg-hover);
}

.file-action-btn svg {
    width: 14px;
    height: 14px;
    stroke: var(--admin-text-secondary);
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.file-action-btn.rename:hover svg {
    stroke: var(--admin-accent);
}

.file-action-btn.delete:hover svg {
    stroke: var(--admin-danger);
}

/* Модальное окно */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-box {
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 24px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

.modal-box h3 {
    margin: 0 0 16px 0;
    color: var(--admin-text-primary);
    font-size: 18px;
}

.modal-box .admin-form-group {
    margin-bottom: 16px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .editor-header {
        flex-direction: column;
        align-items: stretch;
    }
    .editor-actions {
        justify-content: stretch;
    }
    .editor-actions .btn-admin {
        flex: 1;
        text-align: center;
    }
    .file-item {
        flex-direction: column;
        align-items: flex-start;
    }
    .file-item-right {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<!-- Модальное окно для создания / переименования -->
<div class="modal-overlay" id="modal-overlay">
    <div class="modal-box">
        <h3 id="modal-title">Создать</h3>
        <div class="admin-form-group">
            <label for="modal-input" id="modal-label">Имя:</label>
            <input type="text" id="modal-input" class="admin-form-control" placeholder="Введите имя">
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-admin btn-admin-secondary" id="modal-cancel">Отмена</button>
            <button type="button" class="btn-admin btn-admin-success" id="modal-confirm">Создать</button>
        </div>
    </div>
</div>

<?php if (!$showEditor): ?>
    <!-- Список файлов -->
    <div class="module-editor">
        <div class="editor-header">
            <h2>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                    <polyline points="16 18 22 12 16 6"/>
                    <polyline points="8 6 2 12 8 18"/>
                </svg>
                Файловый менеджер
            </h2>
            <a href="?route=pages" class="btn-admin btn-admin-secondary">← Назад к страницам</a>
        </div>
        
        <div class="files-list">
            <div class="files-list-header">
                <h3 id="current-path">Корень проекта</h3>
                <div class="files-list-actions">
                    <button type="button" class="btn-admin btn-admin-primary" id="btn-create-file">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <line x1="9" y1="15" x2="15" y2="15"/>
                        </svg>
                        Создать файл
                    </button>
                    <button type="button" class="btn-admin btn-admin-primary" id="btn-create-folder">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                            <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
                            <line x1="12" y1="11" x2="12" y2="17"/>
                            <line x1="9" y1="14" x2="15" y2="14"/>
                        </svg>
                        Создать папку
                    </button>
                </div>
            </div>
            
            <div id="files-container">
                <p style="color: var(--admin-text-muted); text-align: center; padding: 20px 0;">Загрузка файлов...</p>
            </div>
        </div>
    </div>
    
    <script>
    $(document).ready(function() {
        var currentPath = '';
        var modalAction = '';
        var modalTarget = '';
        
        // Иконки SVG
        var iconFile = '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
        var iconFolder = '<svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>';
        var iconEdit = '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
        var iconRename = '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>';
        var iconDelete = '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>';
        
        // Загрузка списка файлов
        function loadFiles(path) {
            $.ajax({
                url: '/Adm/api/file.api.php',
                type: 'GET',
                data: { action: 'scan', path: path },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        currentPath = path;
                        renderFiles(response.files, path);
                    } else {
                        $('#files-container').html('<p style="color: var(--admin-danger);">' + response.message + '</p>');
                    }
                },
                error: function() {
                    $('#files-container').html('<p style="color: var(--admin-danger);">Ошибка загрузки файлов</p>');
                }
            });
        }
        
        // Отрисовка списка
        function renderFiles(files, path) {
            var html = '';
            
            // Хлебные крошки (кнопка "Назад")
            if (path) {
                html += '<a href="#" class="file-item" data-scan-parent>';
                html += '<div class="file-item-left"><span class="file-icon"><svg viewBox="0 0 24 24"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg></span><strong>.. (Назад)</strong></div>';
                html += '</a>';
            }
            
            var dirs = files.filter(function(f) { return f.type === 'dir'; });
            var fileItems = files.filter(function(f) { return f.type === 'file'; });
            
            // Папки
            dirs.forEach(function(item) {
                html += '<div class="file-item">';
                html += '<a href="#" class="file-item-left" data-scan="' + item.path + '">';
                html += '<span class="file-icon">' + iconFolder + '</span>';
                html += '<strong>' + item.name + '</strong>';
                html += '</a>';
                html += '<div class="file-item-right">';
                html += '<button type="button" class="file-action-btn rename" data-rename="' + item.path + '" data-name="' + item.name + '" title="Переименовать">' + iconRename + '</button>';
                html += '<button type="button" class="file-action-btn delete" data-delete="' + item.path + '" data-name="' + item.name + '" title="Удалить">' + iconDelete + '</button>';
                html += '</div>';
                html += '</div>';
            });
            
            // Файлы
            fileItems.forEach(function(item) {
                html += '<div class="file-item">';
                html += '<a href="?route=modules&action=editor&file=' + encodeURIComponent(item.path) + '" class="file-item-left">';
                html += '<span class="file-icon">' + iconFile + '</span>';
                html += '<span>' + item.name + '</span>';
                html += '<span class="file-meta">' + (item.size || 0) + ' B · ' + (item.modified || '') + '</span>';
                html += '</a>';
                html += '<div class="file-item-right">';
                html += '<a href="?route=modules&action=editor&file=' + encodeURIComponent(item.path) + '" class="file-action-btn" title="Редактировать">' + iconEdit + '</a>';
                html += '<button type="button" class="file-action-btn rename" data-rename="' + item.path + '" data-name="' + item.name + '" title="Переименовать">' + iconRename + '</button>';
                html += '<button type="button" class="file-action-btn delete" data-delete="' + item.path + '" data-name="' + item.name + '" title="Удалить">' + iconDelete + '</button>';
                html += '</div>';
                html += '</div>';
            });
            
            if (html === '') {
                html = '<p style="color: var(--admin-text-muted); text-align: center; padding: 20px 0;">Файлы не найдены</p>';
            }
            
            $('#files-container').html(html);
            $('#current-path').text(path ? '/' + path : 'Корень проекта');
        }
        
        // Навигация по папкам
        $(document).on('click', '[data-scan]', function(e) {
            e.preventDefault();
            loadFiles($(this).data('scan'));
        });
        
        // Навигация назад
        $(document).on('click', '[data-scan-parent]', function(e) {
            e.preventDefault();
            var parts = currentPath.split('/');
            parts.pop();
            loadFiles(parts.join('/'));
        });
        
        // Создание файла
        $('#btn-create-file').on('click', function() {
            modalAction = 'create_file';
            modalTarget = currentPath;
            $('#modal-title').text('Создать файл');
            $('#modal-label').text('Имя файла (с расширением):');
            $('#modal-input').attr('placeholder', 'например: about.php').val('');
            $('#modal-confirm').text('Создать');
            $('#modal-overlay').addClass('active');
            $('#modal-input').focus();
        });
        
        // Создание папки
        $('#btn-create-folder').on('click', function() {
            modalAction = 'create_folder';
            modalTarget = currentPath;
            $('#modal-title').text('Создать папку');
            $('#modal-label').text('Имя папки:');
            $('#modal-input').attr('placeholder', 'например: about').val('');
            $('#modal-confirm').text('Создать');
            $('#modal-overlay').addClass('active');
            $('#modal-input').focus();
        });
        
        // Переименование
        $(document).on('click', '[data-rename]', function() {
            modalAction = 'rename';
            modalTarget = $(this).data('rename');
            $('#modal-title').text('Переименовать');
            $('#modal-label').text('Новое имя:');
            $('#modal-input').val($(this).data('name'));
            $('#modal-confirm').text('Переименовать');
            $('#modal-overlay').addClass('active');
            $('#modal-input').focus().select();
        });
        
        // Удаление
        $(document).on('click', '[data-delete]', function() {
            var filePath = $(this).data('delete');
            var fileName = $(this).data('name');
            
            if (!confirm('Вы уверены, что хотите удалить "' + fileName + '"?\nЭто действие нельзя отменить!')) {
                return;
            }
            
            $.ajax({
                url: '/Adm/api/file.api.php',
                type: 'POST',
                data: { action: 'delete', file: filePath },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAdminNotification(response.message, 'success');
                        loadFiles(currentPath);
                    } else {
                        showAdminNotification(response.message, 'danger');
                    }
                },
                error: function() {
                    showAdminNotification('Ошибка удаления', 'danger');
                }
            });
        });
        
        // Кнопка "Отмена" в модалке
        $('#modal-cancel').on('click', function() {
            $('#modal-overlay').removeClass('active');
        });
        
        // Клик по оверлею — закрыть
        $('#modal-overlay').on('click', function(e) {
            if (e.target === this) {
                $(this).removeClass('active');
            }
        });
        
        // Подтверждение в модалке
        $('#modal-confirm').on('click', function() {
            var value = $('#modal-input').val().trim();
            
            if (value === '') {
                showAdminNotification('Введите имя', 'danger');
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true);
            
            var requestData = { action: modalAction };
            
            if (modalAction === 'create_file') {
                requestData.file = (modalTarget ? modalTarget + '/' : '') + value;
            } else if (modalAction === 'create_folder') {
                requestData.file = (modalTarget ? modalTarget + '/' : '') + value;
            } else if (modalAction === 'rename') {
                requestData.file = modalTarget;
                requestData.new_name = value;
            }
            
            $.ajax({
                url: '/Adm/api/file.api.php',
                type: 'POST',
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAdminNotification(response.message, 'success');
                        $('#modal-overlay').removeClass('active');
                        loadFiles(currentPath);
                    } else {
                        showAdminNotification(response.message, 'danger');
                    }
                },
                error: function() {
                    showAdminNotification('Ошибка выполнения запроса', 'danger');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Enter в модалке
        $('#modal-input').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#modal-confirm').click();
            }
        });
        
        // Загружаем файлы при старте
        loadFiles('');
    });
    </script>

<?php else: ?>
    <!-- Редактор кода -->
    <div class="module-editor">
        <div class="editor-header">
            <div>
                <h2>
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>
                    Редактор кода
                </h2>
                <div class="editor-file-path">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <?= htmlspecialchars($filePath) ?>
                </div>
            </div>
            <div class="editor-actions">
                <button id="save-file" class="btn-admin btn-admin-success">
                    <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Сохранить
                </button>
                <a href="?route=modules&action=editor" class="btn-admin btn-admin-secondary">← К списку файлов</a>
            </div>
        </div>
        
        <div class="editor-wrapper">
            <div id="ace-editor"></div>
            <div class="editor-status" id="editor-status">
                <span>Готово к редактированию</span>
                <span id="file-size"></span>
            </div>
        </div>
    </div>
    
    <script src="/vendor/ace/ace.js"></script>
    <script src="/vendor/ace/mode-php.js"></script>
    <script src="/vendor/ace/mode-html.js"></script>
    <script src="/vendor/ace/mode-css.js"></script>
    <script src="/vendor/ace/mode-javascript.js"></script>
    <script src="/vendor/ace/theme-tomorrow_night.js"></script>
    
    <script>
    $(document).ready(function() {
        var filePath = <?= json_encode($filePath) ?>;
        var editor = null;
        var originalContent = '';
        
        function getMode(filename) {
            var ext = filename.split('.').pop().toLowerCase();
            var modes = {
                'php': 'ace/mode/php',
                'html': 'ace/mode/html',
                'css': 'ace/mode/css',
                'js': 'ace/mode/javascript',
                'json': 'ace/mode/json'
            };
            return modes[ext] || 'ace/mode/text';
        }
        
        editor = ace.edit('ace-editor');
        editor.setTheme('ace/theme/tomorrow_night');
        editor.session.setMode(getMode(filePath));
        editor.setOptions({
            fontSize: '14px',
            fontFamily: 'Consolas, "Courier New", monospace',
            showPrintMargin: false,
            wrap: true,
            tabSize: 4,
            useSoftTabs: true
        });
        
        $.ajax({
            url: '/Adm/api/file.api.php',
            type: 'GET',
            data: { action: 'read', file: filePath },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    editor.setValue(response.content, -1);
                    originalContent = response.content;
                    $('#file-size').text('Размер: ' + response.size + ' B · Изменён: ' + response.modified);
                    $('#editor-status span:first').text('Файл загружен');
                } else {
                    $('#editor-status').addClass('error');
                    $('#editor-status span:first').text('Ошибка: ' + response.message);
                }
            },
            error: function() {
                $('#editor-status').addClass('error');
                $('#editor-status span:first').text('Ошибка загрузки файла');
            }
        });
        
        $('#save-file').on('click', function() {
            var content = editor.getValue();
            var $btn = $(this);
            var $status = $('#editor-status');
            
            $btn.prop('disabled', true);
            $status.removeClass('saved error');
            
            $.ajax({
                url: '/Adm/api/file.api.php',
                type: 'POST',
                data: { action: 'save', file: filePath, content: content },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        originalContent = content;
                        $status.addClass('saved');
                        $status.find('span:first').text('✅ ' + response.message);
                        $('#file-size').text('Размер: ' + response.size + ' B · Изменён: ' + response.modified);
                        showAdminNotification('Файл сохранён', 'success');
                    } else {
                        $status.addClass('error');
                        $status.find('span:first').text('❌ ' + response.message);
                        showAdminNotification(response.message, 'danger');
                    }
                },
                error: function() {
                    $status.addClass('error');
                    $status.find('span:first').text('❌ Ошибка соединения');
                    showAdminNotification('Ошибка соединения', 'danger');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Ctrl+S
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('#save-file').click();
            }
        });
        
        // Индикатор изменений
        editor.on('change', function() {
            var hasChanges = editor.getValue() !== originalContent;
            $('#editor-status span:first').text(hasChanges ? '⚠️ Есть несохранённые изменения' : 'Готово к редактированию');
        });
        
        window.onbeforeunload = function() {
            if (editor && editor.getValue() !== originalContent) {
                return 'У вас есть несохранённые изменения.';
            }
        };
    });
    </script>

<?php endif; ?>