/**
 * Глобальные скрипты ZoomCRM
 */

$(document).ready(function() {
    
    // ============================================
    // ИНИЦИАЛИЗАЦИЯ
    // ============================================
    
    console.log('ZoomCRM загружен. Версия 1.0.0');
    
// ============================================
// ГАМБУРГЕР-МЕНЮ
// ============================================
    
var $hamburger = $('#hamburger');
var $mainNav = $('#mainNav');

if ($hamburger.length && $mainNav.length) {
    
    // Клик по гамбургеру
    $hamburger.on('click', function(e) {
        e.stopPropagation();
        $(this).toggleClass('active');
        $mainNav.toggleClass('open');
        var isOpen = $mainNav.hasClass('open');
        $(this).attr('aria-expanded', isOpen);
    });
    
// На мобильных: клик по ссылке с подменю — открывает подменю
// На мобильных: клик по ссылке с подменю — открывает подменю
	$mainNav.on('click', 'li.has-children > a', function(e) {
		if (window.innerWidth > 768) return;
		e.preventDefault();
		var $li = $(this).closest('li');
		var $submenu = $li.children('ul');
		$submenu.toggleClass('open');
		$li.toggleClass('open'); // ← для поворота стрелки
	});
    
    // На мобильных: клик по ссылке без подменю — закрывает меню
    $mainNav.on('click', 'li:not(.has-children) > a', function(e) {
        if (window.innerWidth > 768) return;
        $hamburger.removeClass('active');
        $mainNav.removeClass('open');
        $hamburger.attr('aria-expanded', 'false');
    });
    
    // Закрытие меню при клике вне его
    $(document).on('click', function(e) {
        if (window.innerWidth <= 768) {
            var $target = $(e.target);
            if (!$target.closest('.hamburger').length && 
                !$target.closest('.main-nav').length) {
                $hamburger.removeClass('active');
                $mainNav.removeClass('open');
                $hamburger.attr('aria-expanded', 'false');
            }
        }
    });
    
    // Закрытие меню при изменении размера окна
    $(window).on('resize', function() {
        if (window.innerWidth > 768) {
            $hamburger.removeClass('active');
            $mainNav.removeClass('open');
            $hamburger.attr('aria-expanded', 'false');
        }
    });
}
    
    // ============================================
    // ОБРАБОТЧИКИ ФОРМ (валидация на клиенте)
    // ============================================
    
    $('form[data-validate]').on('submit', function(e) {
        var $form = $(this);
        var isValid = true;
        
        // Проверяем все поля с атрибутом required
        $form.find('[required]').each(function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (value === '') {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Пожалуйста, заполните все обязательные поля', 'error');
        }
    });
    
    // Убираем класс ошибки при вводе
    $('[required]').on('input change', function() {
        var $field = $(this);
        if ($field.val().trim() !== '') {
            $field.removeClass('error');
        }
    });
    
    // ============================================
    // AJAX-ЗАПРОСЫ
    // ============================================
    
    // Обертка для AJAX-запросов
    window.apiRequest = function(url, data, method, successCallback, errorCallback) {
        method = method || 'POST';
        
        $.ajax({
            url: url,
            type: method,
            data: data,
            dataType: 'json',
            beforeSend: function() {
                // Показываем индикатор загрузки
                $(document).trigger('api:beforeSend', [url]);
            },
            success: function(response) {
                if (response.success) {
                    if (typeof successCallback === 'function') {
                        successCallback(response);
                    }
                    // Успешное уведомление
                    if (response.message) {
                        showNotification(response.message, 'success');
                    }
                } else {
                    if (typeof errorCallback === 'function') {
                        errorCallback(response);
                    }
                    if (response.message) {
                        showNotification(response.message, 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                if (typeof errorCallback === 'function') {
                    errorCallback({ success: false, message: 'Ошибка сервера: ' + error });
                }
                showNotification('Ошибка соединения с сервером', 'error');
            },
            complete: function() {
                $(document).trigger('api:complete', [url]);
            }
        });
    };
    
    // ============================================
    // УВЕДОМЛЕНИЯ (Toast-сообщения)
    // ============================================
    
    function showNotification(message, type) {
        type = type || 'info';
        
        var colors = {
            'info': '#d1ecf1',
            'success': '#d4edda',
            'error': '#f8d7da',
            'warning': '#fff3cd'
        };
        
        var borderColors = {
            'info': '#bee5eb',
            'success': '#c3e6cb',
            'error': '#f5c6cb',
            'warning': '#ffeeba'
        };
        
        var textColors = {
            'info': '#0c5460',
            'success': '#155724',
            'error': '#721c24',
            'warning': '#856404'
        };
        
        var $notification = $('<div>')
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 25px',
                background: colors[type] || '#fff',
                border: '1px solid ' + (borderColors[type] || '#ddd'),
                borderRadius: '4px',
                color: textColors[type] || '#333',
                fontSize: '14px',
                fontWeight: '500',
                boxShadow: '0 4px 15px rgba(0,0,0,0.1)',
                zIndex: 9999,
                maxWidth: '400px',
                opacity: 0,
                transform: 'translateY(-20px)',
                transition: 'all 0.3s ease',
                fontFamily: 'FuturaNew, sans-serif'
            })
            .html(message)
            .appendTo('body');
        
        // Анимация появления
        setTimeout(function() {
            $notification.css({
                opacity: 1,
                transform: 'translateY(0)'
            });
        }, 10);
        
        // Автоматическое скрытие через 5 секунд
        setTimeout(function() {
            $notification.css({
                opacity: 0,
                transform: 'translateY(-20px)'
            });
            
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 5000);
    }
    
    // Делаем функцию глобальной
    window.showNotification = showNotification;
    
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
    // ЗАГРУЗКА МОДУЛЕЙ (динамическая)
    // ============================================
    
    $(document).on('module:load', function(e, moduleName) {
        console.log('Загрузка модуля:', moduleName);
    });
    
    // ============================================
    // ОБРАБОТКА ОШИБОК
    // ============================================
    
    // Перехватываем ошибки jQuery
    $(document).on('error', function(e) {
        console.error('jQuery error:', e);
    });
    
    // Глобальный обработчик ошибок
    window.onerror = function(message, source, lineno, colno, error) {
        // Игнорируем "Script error." - это CORS-ошибки от сторонних скриптов
        if (message === 'Script error.' || message === 'Script error') {
            console.warn('Игнорируем CORS-ошибку скрипта');
            return false;
        }
        
        console.error('Global error:', message, source, lineno);
        
        if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
            showNotification('Ошибка: ' + message, 'error');
        }
        return false;
	};
	
	// ============================================
	// МОБИЛЬНОЕ МЕНЮ: открытие подменю по клику
	// ============================================

	$(document).on('click', '.main-nav li.has-children > a', function(e) {
		if (window.innerWidth <= 768) {
			e.preventDefault();
			var $li = $(this).parent('li');
			$li.toggleClass('open');
		}
	});

});