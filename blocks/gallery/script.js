/**
 * Блок "Галерея"
 * Lightbox + режим preview (большое фото + превью)
 */

(function() {
    'use strict';
    
    $(document).ready(function() {
        
        var currentGallery = null;
        var currentIndex = 0;
        var galleryImages = [];
        
        // ============================================
        // LIGHTBOX (grid / slider)
        // ============================================
        
        $(document).on('click', '.gallery-block__link[data-lightbox]', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var galleryId = $link.data('lightbox');
            
            galleryImages = [];
            $('.gallery-block__link[data-lightbox="' + galleryId + '"]').each(function() {
                galleryImages.push({
                    src: $(this).attr('href'),
                    caption: $(this).data('caption') || ''
                });
            });
            
            currentIndex = $('.gallery-block__link[data-lightbox="' + galleryId + '"]').index($link);
            currentGallery = galleryId;
            
            openLightbox(currentIndex);
        });
        
        // ============================================
        // LIGHTBOX (preview)
        // ============================================
        
        $(document).on('click', '.gallery-preview-main-image', function(e) {
            e.preventDefault();
            
            var $img = $(this);
            var $gallery = $img.closest('.gallery-block');
            
            galleryImages = [];
            $gallery.find('.gallery-preview-thumb').each(function() {
                galleryImages.push({
                    src: $(this).data('image'),
                    caption: $(this).data('caption') || ''
                });
            });
            
            var $active = $gallery.find('.gallery-preview-thumb.active');
            currentIndex = $gallery.find('.gallery-preview-thumb').index($active);
            currentGallery = 'preview';
            
            openLightbox(currentIndex);
        });
        
        // ============================================
        // LIGHTBOX ОБЩИЙ
        // ============================================
        
        function openLightbox(index) {
            $('.gallery-lightbox').remove();
            
            var image = galleryImages[index];
            if (!image) return;
            
            var html = '<div class="gallery-lightbox">';
            html += '<button type="button" class="gallery-lightbox__close" aria-label="Закрыть">✕</button>';
            
            if (galleryImages.length > 1) {
                html += '<button type="button" class="gallery-lightbox__nav gallery-lightbox__prev" aria-label="Предыдущее">‹</button>';
                html += '<button type="button" class="gallery-lightbox__nav gallery-lightbox__next" aria-label="Следующее">›</button>';
                html += '<div class="gallery-lightbox__counter">' + (index + 1) + ' / ' + galleryImages.length + '</div>';
            }
            
            html += '<img src="' + image.src + '" alt="" class="gallery-lightbox__image">';
            
            if (image.caption) {
                html += '<div class="gallery-lightbox__caption">' + image.caption + '</div>';
            }
            
            html += '</div>';
            
            var $lightbox = $(html).appendTo('body');
            
            $('body').css('overflow', 'hidden');
            
            setTimeout(function() {
                $lightbox.addClass('active');
            }, 10);
            
            // Обработчики
            $lightbox.find('.gallery-lightbox__close').on('click', function(e) {
                e.stopPropagation();
                closeLightbox($lightbox);
            });
            
            $lightbox.on('click', function(e) {
                if (e.target === this) {
                    closeLightbox($lightbox);
                }
            });
            
            $lightbox.find('.gallery-lightbox__prev').on('click', function(e) {
                e.stopPropagation();
                navigateLightbox(-1);
            });
            
            $lightbox.find('.gallery-lightbox__next').on('click', function(e) {
                e.stopPropagation();
                navigateLightbox(1);
            });
            
            $(document).on('keydown.gallery-lightbox', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    closeLightbox($lightbox);
                } else if (e.key === 'ArrowLeft' || e.keyCode === 37) {
                    navigateLightbox(-1);
                } else if (e.key === 'ArrowRight' || e.keyCode === 39) {
                    navigateLightbox(1);
                }
            });
        }
        
        function navigateLightbox(direction) {
            currentIndex += direction;
            
            if (currentIndex < 0) {
                currentIndex = galleryImages.length - 1;
            } else if (currentIndex >= galleryImages.length) {
                currentIndex = 0;
            }
            
            var image = galleryImages[currentIndex];
            var $lightbox = $('.gallery-lightbox');
            
            $lightbox.find('.gallery-lightbox__image').css('opacity', 0);
            
            setTimeout(function() {
                $lightbox.find('.gallery-lightbox__image').attr('src', image.src).css('opacity', 1);
                
                $lightbox.find('.gallery-lightbox__caption').remove();
                if (image.caption) {
                    $lightbox.append('<div class="gallery-lightbox__caption">' + image.caption + '</div>');
                }
                
                $lightbox.find('.gallery-lightbox__counter').text((currentIndex + 1) + ' / ' + galleryImages.length);
            }, 150);
        }
        
        function closeLightbox($lightbox) {
            $lightbox.removeClass('active');
            $('body').css('overflow', '');
            
            setTimeout(function() {
                $lightbox.remove();
            }, 300);
            
            $(document).off('keydown.gallery-lightbox');
        }
        
        // ============================================
        // РЕЖИМ PREVIEW (переключение миниатюр)
        // ============================================
        
        $(document).on('click', '.gallery-preview-thumb', function() {
            var $thumb = $(this);
            var $gallery = $thumb.closest('.gallery-block');
            var $mainImage = $gallery.find('.gallery-preview-main-image');
            var $caption = $gallery.find('.gallery-preview-caption');
            var $counter = $gallery.find('[data-current]');
            
            // Если уже активна — ничего не делаем
            if ($thumb.hasClass('active')) return;
            
            // Обновляем активную миниатюру
            $gallery.find('.gallery-preview-thumb').removeClass('active');
            $thumb.addClass('active');
            
            // Анимация смены
            $mainImage.addClass('fading');
            
            setTimeout(function() {
                // Обновляем большое фото
                $mainImage.attr('src', $thumb.data('image'));
                $mainImage.attr('alt', $thumb.data('alt'));
                $mainImage.attr('data-caption', $thumb.data('caption'));
                
                // Обновляем подпись
                if ($caption.length) {
                    if ($thumb.data('caption')) {
                        $caption.text($thumb.data('caption')).show();
                    } else {
                        $caption.hide();
                    }
                }
                
                // Обновляем счётчик
                if ($counter.length) {
                    $counter.text(parseInt($thumb.data('index')) + 1);
                }
                
                $mainImage.removeClass('fading');
            }, 150);
        });
        
        // ============================================
        // НАВИГАЦИЯ В PREVIEW (стрелки)
        // ============================================
        
        $(document).on('click', '[data-gallery-preview-prev]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $gallery = $(this).closest('.gallery-block');
            var $thumbs = $gallery.find('.gallery-preview-thumb');
            var $active = $thumbs.filter('.active');
            var currentIndex = $thumbs.index($active);
            var newIndex = currentIndex - 1;
            
            if (newIndex < 0) {
                newIndex = $thumbs.length - 1;
            }
            
            $thumbs.eq(newIndex).trigger('click');
        });
        
        $(document).on('click', '[data-gallery-preview-next]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $gallery = $(this).closest('.gallery-block');
            var $thumbs = $gallery.find('.gallery-preview-thumb');
            var $active = $thumbs.filter('.active');
            var currentIndex = $thumbs.index($active);
            var newIndex = currentIndex + 1;
            
            if (newIndex >= $thumbs.length) {
                newIndex = 0;
            }
            
            $thumbs.eq(newIndex).trigger('click');
        });
        
    });
    
})();