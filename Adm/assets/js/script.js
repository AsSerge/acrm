/**
 * Дополнительные скрипты админ-панели
 */

$(document).ready(function() {
    
    // ============================================
    // УВЕДОМЛЕНИЯ
    // ============================================
    
    window.showAdminNotification = function(message, type) {
        type = type || 'info';
        
        var colors = {
            'success': 'var(--admin-success)',
            'danger': 'var(--admin-danger)',
            'warning': 'var(--admin-warning)',
            'info': 'var(--admin-accent)'
        };
        
        var $notification = $('<div>')
            .css({
                position: 'fixed',
                top: '80px',
                right: '20px',
                padding: '12px 20px',
                background: 'var(--admin-bg-card)',
                border: '1px solid ' + (colors[type] || colors.info),
                borderRadius: '4px',
                color: 'var(--admin-text-primary)',
                fontSize: '14px',
                boxShadow: '0 4px 20px rgba(0,0,0,0.4)',
                zIndex: 9999,
                maxWidth: '400px',
                opacity: 0,
                transform: 'translateY(-20px)',
                transition: 'all 0.3s ease',
                fontFamily: 'FuturaNew, sans-serif'
            })
            .html(message)
            .appendTo('body');
        
        // Добавляем цветную полоску слева
        $notification.css('border-left', '4px solid ' + (colors[type] || colors.info));
        
        setTimeout(function() {
            $notification.css({
                opacity: 1,
                transform: 'translateY(0)'
            });
        }, 10);
        
        setTimeout(function() {
            $notification.css({
                opacity: 0,
                transform: 'translateY(-20px)'
            });
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 4000);
    };
    
    // ============================================
    // ПОДТВЕРЖДЕНИЕ ДЕЙСТВИЙ
    // ============================================
    
    $('[data-confirm]').on('click', function(e) {
        var message = $(this).data('confirm') || 'Вы уверены?';
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // ============================================
    // СООБЩЕНИЯ ИЗ URL (flash-сообщения)
    // ============================================
    
    var urlParams = new URLSearchParams(window.location.search);
    var msg = urlParams.get('msg');
    var msgType = urlParams.get('msg_type') || 'info';
    
    if (msg) {
        showAdminNotification(decodeURIComponent(msg), msgType);
        // Удаляем параметры из URL (не перезагружая страницу)
        if (window.history && window.history.replaceState) {
            var newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    }
    
    console.log('Admin scripts loaded');
});