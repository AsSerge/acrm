<?php
/**
 * Блок "Изображение"
 * Одиночное изображение с подписью
 * 
 * Доступные переменные:
 * $params — массив параметров из data-* атрибутов
 * $blockName — имя блока (image)
 * $uniqid — уникальный ID экземпляра блока
 * 
 * Параметры:
 *   data-src        — путь к изображению (обязательно)
 *   data-alt        — альтернативный текст
 *   data-caption    — подпись под фото
 *   data-link       — ссылка при клике
 *   data-eager      — загружать сразу (true / false), по умолчанию false
 */

// Получаем параметры
$src = $params['src'] ?? '';
$alt = $params['alt'] ?? '';
$caption = $params['caption'] ?? '';
$link = $params['link'] ?? '';
$eager = ($params['eager'] ?? '') === 'true';

// Если нет src — ничего не выводим
if (empty($src)) {
    return;
}

// Атрибут loading
$loading = $eager ? 'eager' : 'lazy';
?>
<figure class="image-block" id="<?= htmlspecialchars($uniqid) ?>">
    <?php if (!empty($link)): ?>
        <a href="<?= htmlspecialchars($link) ?>" class="image-block__link">
            <img src="<?= htmlspecialchars($src) ?>" 
                 alt="<?= htmlspecialchars($alt) ?>" 
                 class="image-block__image"
                 loading="<?= $loading ?>">
        </a>
    <?php else: ?>
        <img src="<?= htmlspecialchars($src) ?>" 
             alt="<?= htmlspecialchars($alt) ?>" 
             class="image-block__image"
             loading="<?= $loading ?>"
             data-lightbox="true">
    <?php endif; ?>
    
    <?php if (!empty($caption)): ?>
        <figcaption class="image-block__caption"><?= htmlspecialchars($caption) ?></figcaption>
    <?php endif; ?>
</figure>