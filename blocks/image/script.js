/**
 * Блок "Изображение"
 * Lightbox для открытия изображений в полном размере
 */

(function() {
    'use strict';
    
    $(document).ready(function() {
        
        // ============================================
        // LIGHTBOX
        // ============================================
        
        $(document).on('click', '.image-block__image[data-lightbox="true"]', function(e) {
            e.preventDefault();
            
            var $img = $(this);
            var src = $img.attr('src');
            var alt = $img.attr('alt') || '';
            var caption = $img.closest('.image-block').find('.image-block__caption').text();
            
            openLightbox(src, alt, caption);
        });
        
        function openLightbox(src, alt, caption) {
            $('.image-lightbox').remove();
            
            var html = '<div class="image-lightbox">';
            html += '<button type="button" class="image-lightbox__close" aria-label="Закрыть">✕</button>';
            html += '<img src="' + src + '" alt="' + alt + '" class="image-lightbox__image">';
            if (caption) {
                html += '<div class="image-lightbox__caption">' + caption + '</div>';
            }
            html += '</div>';
            
            var $lightbox = $(html).appendTo('body');
            
            $('body').css('overflow', 'hidden');
            
            setTimeout(function() {
                $lightbox.addClass('active');
            }, 10);
            
            $lightbox.on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('image-lightbox')) {
                    closeLightbox($lightbox);
                }
            });
            
            $lightbox.find('.image-lightbox__close').on('click', function(e) {
                e.stopPropagation();
                closeLightbox($lightbox);
            });
            
            $lightbox.find('.image-lightbox__image').on('click', function(e) {
                e.stopPropagation();
                closeLightbox($lightbox);
            });
            
            $(document).on('keydown.lightbox', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    closeLightbox($lightbox);
                }
            });
        }
        
        function closeLightbox($lightbox) {
            $lightbox.removeClass('active');
            $('body').css('overflow', '');
            
            setTimeout(function() {
                $lightbox.remove();
            }, 300);
            
            $(document).off('keydown.lightbox');
        }
        
    });
    
})();