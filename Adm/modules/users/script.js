/**
 * Скрипты модуля Users
 */

$(document).ready(function() {
    
    // ============================================
    // ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ
    // ============================================
    
    $(document).on('click', '[data-delete-user]', function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        var username = $(this).data('username');
        
        if (!confirm('Вы уверены, что хотите удалить пользователя "' + username + '"? Это действие нельзя отменить!')) {
            return;
        }
        
        deleteUser(userId);
    });
    
    // ============================================
    // ПОДТВЕРЖДЕНИЕ ИЗМЕНЕНИЯ СТАТУСА
    // ============================================
    
    $(document).on('click', '[data-toggle-user]', function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus ? 0 : 1;
        var statusText = newStatus ? 'активировать' : 'заблокировать';
        
        if (!confirm('Вы уверены, что хотите ' + statusText + ' этого пользователя?')) {
            return;
        }
        
        toggleUser(userId, newStatus);
    });
    
    // ============================================
    // AJAX-ФУНКЦИИ
    // ============================================
    
    window.toggleUser = function(userId, newStatus) {
        $.ajax({
            url: '/Adm/api/users.api.php',
            type: 'POST',
            data: {
                action: 'toggle',
                id: userId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification(response.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка выполнения запроса', 'danger');
            }
        });
    };
    
    window.deleteUser = function(userId) {
        $.ajax({
            url: '/Adm/api/users.api.php',
            type: 'POST',
            data: {
                action: 'delete',
                id: userId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification(response.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка выполнения запроса', 'danger');
            }
        });
    };
    
    console.log('Users module loaded');
});