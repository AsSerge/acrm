/**
 * Модуль Example - скрипты
 * Демонстрация работы с AJAX
 */

$(document).ready(function() {
    console.log('Модуль Example загружен');
    
    // Обработчик AJAX-запроса
    $('#example-ajax-btn').on('click', function() {
        var $btn = $(this);
        var $result = $('#example-result');
        
        // Отключаем кнопку
        $btn.prop('disabled', true).text('Загрузка...');
        $result.html('');
        
        $.ajax({
            url: '/api/example.api.php',
            type: 'POST',
            data: {
                action: 'test',
                value: 'Hello from AJAX!',
                timestamp: new Date().getTime()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $result.html(
                        '<span style="color: #28a745; font-weight: 500;">✓ ' + 
                        response.message + 
                        ' <span style="font-weight: 400; color: #6c757d; font-size: 12px;">(' + 
                        response.timestamp + 
                        ')</span></span>'
                    );
                } else {
                    $result.html(
                        '<span style="color: #dc3545; font-weight: 500;">✗ ' + 
                        response.message + 
                        '</span>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $result.html(
                    '<span style="color: #dc3545; font-weight: 500;">✗ Ошибка: ' + 
                    error + 
                    '</span>'
                );
            },
            complete: function() {
                $btn.prop('disabled', false).text('Отправить AJAX-запрос');
            }
        });
    });
    
    // ==========================================
    // ДЕМОНСТРАЦИЯ ДИНАМИЧЕСКОЙ ЗАГРУЗКИ
    // ==========================================
    
    // При загрузке модуля показываем уведомление
    showNotification('Модуль Example загружен успешно!', 'success');
    
    // ==========================================
    // ОБРАБОТЧИК ДЛЯ ДЕМОНСТРАЦИИ
    // ==========================================
    
    // Клик по ссылкам с классом nav-link
    $('.nav-link').on('click', function(e) {
        // Показываем уведомление о переходе
        var href = $(this).attr('href');
        showNotification('Переход по ссылке: ' + href, 'info');
    });
});