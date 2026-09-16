/**
 * Динамическая загрузка модулей
 * Подгружает CSS и JS для активных модулей
 */

$(document).ready(function() {
    
    // ============================================
    // ЗАГРУЗЧИК МОДУЛЕЙ
    // ============================================
    
    window.ModuleLoader = {
        loadedModules: {},
        loadingModules: {},
        
        /**
         * Загрузка CSS модуля
         */
        loadCSS: function(moduleName, cssPath) {
            if (this.loadedModules[moduleName + '-css']) {
                return true;
            }
            
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = cssPath;
            link.dataset.module = moduleName;
            
            document.head.appendChild(link);
            this.loadedModules[moduleName + '-css'] = true;
            
            console.log('CSS загружен:', moduleName);
            return true;
        },
        
        /**
         * Загрузка JS модуля
         */
        loadJS: function(moduleName, jsPath) {
            if (this.loadedModules[moduleName + '-js']) {
                return true;
            }
            
            // Проверяем, не загружается ли уже
            if (this.loadingModules[moduleName + '-js']) {
                return false;
            }
            
            this.loadingModules[moduleName + '-js'] = true;
            
            var script = document.createElement('script');
            script.src = jsPath;
            script.dataset.module = moduleName;
            script.async = true;
            
            script.onload = function() {
                window.ModuleLoader.loadedModules[moduleName + '-js'] = true;
                delete window.ModuleLoader.loadingModules[moduleName + '-js'];
                console.log('JS загружен:', moduleName);
                
                // Триггерим событие загрузки модуля
                $(document).trigger('module:loaded', [moduleName]);
            };
            
            script.onerror = function() {
                delete window.ModuleLoader.loadingModules[moduleName + '-js'];
                console.error('Ошибка загрузки JS:', moduleName);
                $(document).trigger('module:error', [moduleName]);
            };
            
            document.body.appendChild(script);
            return true;
        },
        
        /**
         * Загрузка модуля целиком
         */
        loadModule: function(moduleName, cssPath, jsPath) {
            if (cssPath) {
                this.loadCSS(moduleName, cssPath);
            }
            
            if (jsPath) {
                this.loadJS(moduleName, jsPath);
            }
        }
    };
    
    // ============================================
    // АВТОМАТИЧЕСКАЯ ЗАГРУЗКА МОДУЛЕЙ ИЗ РАЗМЕТКИ
    // ============================================
    
    // Ищем элементы с data-module
    $('[data-module]').each(function() {
        var $el = $(this);
        var moduleName = $el.data('module');
        var cssPath = $el.data('css');
        var jsPath = $el.data('js');
        
        if (moduleName && window.ModuleLoader) {
            window.ModuleLoader.loadModule(moduleName, cssPath, jsPath);
        }
    });
    
    // ============================================
    // ИНИЦИАЛИЗАЦИЯ МОДУЛЕЙ ПОСЛЕ ЗАГРУЗКИ
    // ============================================
    
    $(document).on('module:loaded', function(e, moduleName) {
        console.log('Модуль инициализирован:', moduleName);
        
        // Вызываем функцию инициализации, если она определена
        var initFn = window['initModule_' + moduleName];
        if (typeof initFn === 'function') {
            initFn();
        }
    });
    
    console.log('ModuleLoader готов');
});