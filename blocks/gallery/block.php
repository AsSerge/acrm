<?php
/**
 * Блок "Галерея"
 * Выводит галерею изображений из БД
 * 
 * Доступные переменные:
 * $params — массив параметров из data-* атрибутов
 * $blockName — имя блока (gallery)
 * $uniqid — уникальный ID экземпляра блока
 * 
 * Параметры:
 *   data-gallery-id  — ID галереи (обязательно)
 *   data-mode        — режим: grid / slider / preview (по умолчанию grid)
 *   data-cols        — колонки: 2 / 3 / 4 (по умолчанию 3)
 *   data-lightbox    — открывать в lightbox (true / false, по умолчанию true)
 */

// Получаем параметры
$galleryId = (int)($params['gallery-id'] ?? 0);
$mode = $params['mode'] ?? 'grid';
$cols = (int)($params['cols'] ?? 3);
$lightbox = ($params['lightbox'] ?? 'true') !== 'false';

// Если нет ID — ничего не выводим
if ($galleryId <= 0) {
    return;
}

// Валидация режима и колонок
if (!in_array($mode, ['grid', 'slider', 'preview'])) {
    $mode = 'grid';
}

if (!in_array($cols, [2, 3, 4])) {
    $cols = 3;
}

// Загружаем галерею
$galleryManager = new GalleryManager();
$gallery = $galleryManager->getById($galleryId);

// Если галерея не найдена или неактивна — ничего не выводим
if (!$gallery || !$gallery['is_active']) {
    return;
}

// Если нет изображений — ничего не выводим
if (empty($gallery['images'])) {
    return;
}

// Собираем классы
$classes = ['gallery-block'];
$classes[] = 'gallery-block--mode-' . $mode;
$classes[] = 'gallery-block--cols-' . $cols;

if ($lightbox) {
    $classes[] = 'gallery-block--lightbox';
}

// Хелпер: получить путь к изображению нужного размера
// function getGalleryImagePath($imagePath, $size = 'medium')
// {
//     $info = pathinfo($imagePath);
//     $dir = dirname($imagePath);
//     $baseName = $info['filename'];
//     $ext = $info['extension'] ?? '';
    
//     if ($size === 'original') {
//         return $imagePath;
//     }
    
//     $suffix = '_' . $size;
//     $sizeName = $baseName . $suffix . '.' . $ext;
//     $sizeFullPath = ROOT_DIR . $dir . '/' . $sizeName;
    
//     if (file_exists($sizeFullPath)) {
//         return $dir . '/' . $sizeName;
//     }
    
//     // Fallback на оригинал
//     return $imagePath;
// }

// Хелпер: получить путь к изображению нужного размера
if (!function_exists('getGalleryImagePath')) {
    function getGalleryImagePath($imagePath, $size = 'medium')
    {
        $info = pathinfo($imagePath);
        $dir = dirname($imagePath);
        $baseName = $info['filename'];
        $ext = $info['extension'] ?? '';
        
        if ($size === 'original') {
            return $imagePath;
        }
        
        $suffix = '_' . $size;
        $sizeName = $baseName . $suffix . '.' . $ext;
        $sizeFullPath = ROOT_DIR . $dir . '/' . $sizeName;
        
        if (file_exists($sizeFullPath)) {
            return $dir . '/' . $sizeName;
        }
        
        // Fallback на оригинал
        return $imagePath;
    }
}
?>
<div class="<?= implode(' ', $classes) ?>" id="<?= htmlspecialchars($uniqid) ?>">
    
    <?php if (!empty($gallery['title'])): ?>
        <h3 class="gallery-block__title"><?= htmlspecialchars($gallery['title']) ?></h3>
    <?php endif; ?>
    
    <?php if (!empty($gallery['description'])): ?>
        <p class="gallery-block__description"><?= htmlspecialchars($gallery['description']) ?></p>
    <?php endif; ?>
    
    <?php if ($mode === 'preview'): ?>
        <!-- ============================================ -->
        <!-- РЕЖИМ PREVIEW (большое фото + превью) -->
        <!-- ============================================ -->
        
        <?php
        $firstImage = $gallery['images'][0];
        $firstFullPath = getGalleryImagePath($firstImage['image_path'], 'medium');
        ?>
        
        <div class="gallery-preview-main">
            <img src="<?= htmlspecialchars($firstFullPath) ?>" 
                 alt="<?= htmlspecialchars($firstImage['image_alt']) ?>" 
                 class="gallery-preview-main-image"
                 data-lightbox="true"
                 data-caption="<?= htmlspecialchars($firstImage['image_caption']) ?>">
            
            <?php if (!empty($firstImage['image_caption'])): ?>
                <div class="gallery-preview-caption"><?= htmlspecialchars($firstImage['image_caption']) ?></div>
            <?php endif; ?>
            
            <?php if (count($gallery['images']) > 1): ?>
                <button type="button" class="gallery-preview-nav gallery-preview-prev" 
                        data-gallery-preview-prev aria-label="Предыдущее">‹</button>
                <button type="button" class="gallery-preview-nav gallery-preview-next" 
                        data-gallery-preview-next aria-label="Следующее">›</button>
                <div class="gallery-preview-counter">
                    <span data-current>1</span> / <?= count($gallery['images']) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($gallery['images']) > 1): ?>
            <div class="gallery-preview-thumbs">
                <?php foreach ($gallery['images'] as $index => $image): ?>
                    <?php
                    $thumbPath = getGalleryImagePath($image['image_path'], 'thumb');
                    $fullPath = getGalleryImagePath($image['image_path'], 'medium');
                    ?>
                    <button type="button" 
                            class="gallery-preview-thumb <?= $index === 0 ? 'active' : '' ?>"
                            data-index="<?= $index ?>"
                            data-image="<?= htmlspecialchars($fullPath) ?>"
                            data-alt="<?= htmlspecialchars($image['image_alt']) ?>"
                            data-caption="<?= htmlspecialchars($image['image_caption']) ?>">
                        <img src="<?= htmlspecialchars($thumbPath) ?>" 
                             alt="<?= htmlspecialchars($image['image_alt']) ?>" 
                             loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- ============================================ -->
        <!-- РЕЖИМЫ GRID и SLIDER -->
        <!-- ============================================ -->
        
        <div class="gallery-block__items">
            <?php foreach ($gallery['images'] as $index => $image): ?>
                <?php
                $thumbPath = getGalleryImagePath($image['image_path'], 'thumb');
                $mediumPath = getGalleryImagePath($image['image_path'], 'medium');
                ?>
                <div class="gallery-block__item">
                    <?php if ($lightbox): ?>
                        <a href="<?= htmlspecialchars($mediumPath) ?>" 
                           class="gallery-block__link"
                           data-lightbox="gallery-<?= (int)$galleryId ?>"
                           data-caption="<?= htmlspecialchars($image['image_caption']) ?>">
                            <img src="<?= htmlspecialchars($thumbPath) ?>" 
                                 alt="<?= htmlspecialchars($image['image_alt']) ?>" 
                                 class="gallery-block__image"
                                 loading="lazy">
                        </a>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($thumbPath) ?>" 
                             alt="<?= htmlspecialchars($image['image_alt']) ?>" 
                             class="gallery-block__image"
                             loading="lazy">
                    <?php endif; ?>
                    
                    <?php if (!empty($image['image_caption'])): ?>
                        <div class="gallery-block__caption"><?= htmlspecialchars($image['image_caption']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php endif; ?>
</div>