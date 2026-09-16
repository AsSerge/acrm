/**
 * Скрипты модуля Menu
 */

$(document).ready(function() {
    
    // ============================================
    // ИНИЦИАЛИЗАЦИЯ SORTABLE
    // ============================================
    
    var tree = document.getElementById('menuTree');
    
    if (tree && typeof Sortable !== 'undefined') {
        var sortable = new Sortable(tree, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                // Определяем нового родителя по позиции вставки
                var newParentId = getParentIdFromPosition(evt);
                
                // Обновляем data-parent у перемещённого элемента
                var movedElement = evt.item;
                if (newParentId !== null) {
                    movedElement.dataset.parent = newParentId;
                } else {
                    movedElement.dataset.parent = '0';
                }
                
                // Сохраняем порядок
                saveMenuOrder();
            }
        });
        console.log('Sortable initialized');
    } else {
        if (!tree) {
            console.log('Menu tree not found (probably create/edit page)');
        }
        if (typeof Sortable === 'undefined') {
            console.warn('Sortable library not loaded');
        }
    }
    
    // ============================================
    // ОПРЕДЕЛЕНИЕ РОДИТЕЛЯ ПО ПОЗИЦИИ ВСТАВКИ
    // ============================================
    
    function getParentIdFromPosition(evt) {
        var movedElement = evt.item;
        var oldParentId = movedElement.dataset.parent;
        
        // 1. Проверяем, есть ли перед элементом другой пункт
        var prevElement = movedElement.previousElementSibling;
        if (prevElement && prevElement.classList.contains('sortable-row')) {
            var prevId = prevElement.dataset.id;
            var hasChildren = document.querySelector('tr[data-parent="' + prevId + '"]');
            if (hasChildren) {
                return prevId;
            }
        }
        
        // 2. Проверяем, есть ли после элемента другой пункт
        var nextElement = movedElement.nextElementSibling;
        if (nextElement && nextElement.classList.contains('sortable-row')) {
            var nextParent = nextElement.dataset.parent;
            if (nextParent && nextParent !== '0') {
                return nextParent;
            }
        }
        
        // 3. Проверяем родительский контейнер
        var parentContainer = movedElement.parentElement;
        if (parentContainer && parentContainer.id === 'menuTree') {
            return null;
        }
        
        // 4. Проверяем родительский <tr>
        var parentRow = movedElement.parentElement.closest('tr[data-id]');
        if (parentRow) {
            return parentRow.dataset.id;
        }
        
        // 5. Оставляем старого родителя
        return oldParentId || null;
    }
    
    // ============================================
    // ПЕРЕСЧЁТ ОТСТУПОВ (визуальная корректировка)
    // ============================================
    
    function updatePaddings() {
        var rows = document.querySelectorAll('#menuTree .sortable-row');
        
        // Строим карту: id → parent_id
        var parentMap = {};
        rows.forEach(function(row) {
            var id = row.dataset.id;
            var parentId = row.dataset.parent;
            if (parentId === '0' || parentId === '') parentId = null;
            parentMap[id] = parentId;
        });
        
        // Определяем уровень вложенности
        function getDepth(id) {
            var depth = 0;
            var currentId = id;
            
            while (currentId && parentMap[currentId]) {
                depth++;
                currentId = parentMap[currentId];
                
                if (depth > 10) break; // защита от бесконечного цикла
            }
            
            return depth;
        }
        
        // Обновляем padding-left для каждого пункта
        rows.forEach(function(row) {
            var id = row.dataset.id;
            var depth = getDepth(id);
            var padding = depth * 30;
            
            var div = row.querySelector('td:first-child > div');
            if (div) {
                div.style.paddingLeft = padding + 'px';
            }
        });
    }
    
    // ============================================
    // СОХРАНЕНИЕ ПОРЯДКА МЕНЮ
    // ============================================
    
    function saveMenuOrder() {
        var items = [];
        var rows = document.querySelectorAll('#menuTree .sortable-row');
        
        // 1. Группируем строки по parent_id
        var groups = {};
        rows.forEach(function(row) {
            var parentId = row.dataset.parent ? parseInt(row.dataset.parent) : null;
            if (parentId === 0) {
                parentId = null;
            }
            
            if (!groups[parentId]) {
                groups[parentId] = [];
            }
            groups[parentId].push(row);
        });
        
        // 2. Для каждой группы присваиваем sort_order от 0
        Object.keys(groups).forEach(function(parentKey) {
            var parentId = parentKey === 'null' ? null : parseInt(parentKey);
            var groupRows = groups[parentKey];
            
            groupRows.forEach(function(row, index) {
                var id = parseInt(row.dataset.id);
                items.push({
                    id: id,
                    parent_id: parentId,
                    sort_order: index
                });
            });
        });
        
        // Обновляем отступы сразу (визуально)
        updatePaddings();
        
        console.log('Saving order (grouped):', items);
        
        showAdminNotification('Сохранение порядка...', 'info');
        
        $.ajax({
            url: '/Adm/api/menu.api.php',
            type: 'POST',
            data: {
                action: 'sort',
                order: items
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification('Порядок меню сохранён', 'success');
                    
                    // Перезагружаем страницу через 500 мс
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response text:', xhr.responseText);
                showAdminNotification('Ошибка сохранения порядка', 'danger');
            }
        });
    }
    
    // ============================================
    // ОБНОВЛЕНИЕ DATA-PARENT ПРИ ИЗМЕНЕНИЯХ DOM
    // ============================================
    
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('sortable-row')) {
                        var parentTr = node.parentElement.closest('tr[data-id]');
                        if (parentTr) {
                            var parentId = parentTr.dataset.id;
                            if (node.dataset.parent !== parentId) {
                                node.dataset.parent = parentId;
                            }
                        }
                    }
                });
            }
        });
    });
    
    if (tree) {
        observer.observe(tree, {
            childList: true,
            subtree: true
        });
    }
    
    console.log('Menu module loaded');
});