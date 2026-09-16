/**
 * Ядро админ-панели
 * Управление сайдбаром, гамбургером, сворачиванием
 */

$(document).ready(function() {
    
	console.log('admin-core.js loaded');
    // ============================================
    // ПЕРЕМЕННЫЕ
    // ============================================
    
    var $sidebar = $('.admin-sidebar');
    var $main = $('.admin-main');
    var $hamburger = $('#sidebarToggle');
    var $arrow = $('.desktop-arrow');
    var $overlay = $('.sidebar-overlay');
    var isCollapsed = false;
    var isMobile = window.innerWidth <= 768;
    


    // ============================================
    // ФУНКЦИЯ ОБНОВЛЕНИЯ СТРЕЛКИ
    // ============================================
    
    function updateArrow() {
        if (!$arrow.length) return;
        
        // Проверяем наличие класса collapsed у сайдбара
        if ($sidebar.hasClass('collapsed')) {
            $arrow.text('▶');
        } else {
            $arrow.text('◀');
        }
    }
    
    // ============================================
    // СВОРАЧИВАНИЕ САЙДБАРА (десктоп)
    // ============================================
    
    $hamburger.on('click', function(e) {
        e.stopPropagation();
        
        if (isMobile) {
            // На мобильных — открываем/закрываем оверлей
            $sidebar.toggleClass('open');
            $overlay.toggleClass('active');
            $(this).toggleClass('active');
        } else {
            // На десктопах — сворачиваем
            $sidebar.toggleClass('collapsed');
            
            // Обновляем стрелку
            updateArrow();
            
            // Сохраняем состояние в localStorage
            try {
                localStorage.setItem('adminSidebarCollapsed', $sidebar.hasClass('collapsed'));
            } catch(e) {}
        }
    });
    
    // ============================================
    // ВОССТАНОВЛЕНИЕ СОСТОЯНИЯ САЙДБАРА
    // ============================================
    
    try {
        var savedState = localStorage.getItem('adminSidebarCollapsed');
        if (savedState === 'true' && !isMobile) {
            $sidebar.addClass('collapsed');
        }
    } catch(e) {}
    
    // ОБЯЗАТЕЛЬНО вызываем updateArrow после загрузки и восстановления состояния
    updateArrow();
    
    // ============================================
    // ЗАКРЫТИЕ МОБИЛЬНОГО МЕНЮ
    // ============================================
    
    $overlay.on('click', function() {
        $sidebar.removeClass('open');
        $overlay.removeClass('active');
        $hamburger.removeClass('active');
    });
    
    $('.sidebar-item').on('click', function() {
        if (isMobile) {
            $sidebar.removeClass('open');
            $overlay.removeClass('active');
            $hamburger.removeClass('active');
        }
    });
    
    // ============================================
    // АДАПТАЦИЯ ПРИ ИЗМЕНЕНИИ РАЗМЕРА ОКНА
    // ============================================
    
    $(window).on('resize', function() {
        var newIsMobile = window.innerWidth <= 768;
        
        if (newIsMobile !== isMobile) {
            isMobile = newIsMobile;
            
            if (isMobile) {
                $sidebar.removeClass('collapsed').removeClass('open');
                $overlay.removeClass('active');
                $hamburger.removeClass('active');
            } else {
                $sidebar.removeClass('open');
                $overlay.removeClass('active');
                $hamburger.removeClass('active');
                
                try {
                    var saved = localStorage.getItem('adminSidebarCollapsed');
                    if (saved === 'true') {
                        $sidebar.addClass('collapsed');
                    } else {
                        $sidebar.removeClass('collapsed');
                    }
                } catch(e) {}
                
                // Обновляем стрелку после смены размера
                updateArrow();
            }
        }
    });
    
// ============================================
// АКТИВНЫЙ ПУНКТ МЕНЮ
// ============================================

// Получаем текущий route из URL
var urlParams = new URLSearchParams(window.location.search);
var currentRoute = urlParams.get('route') || 'dashboard';
var currentAction = urlParams.get('action') || '';

$('.sidebar-item').each(function() {
    var $item = $(this);
    var href = $item.attr('href');
    
    if (!href) return;
    
    // Разбираем href: извлекаем route и action
    var hrefParams = new URLSearchParams(href.split('?')[1] || '');
    var hrefRoute = hrefParams.get('route') || '';
    var hrefAction = hrefParams.get('action') || '';
    
    // Активен, если route совпадает и action совпадает (или action не задан)
    if (hrefRoute === currentRoute && (hrefAction === '' || hrefAction === currentAction)) {
        $item.addClass('active');
    } else {
        $item.removeClass('active');
    }
});
    
    console.log('Admin core loaded');
});