<?php
/**
 * Страница "О компании"
 */
$phone = Setting::get('contact_phone');
?>
<div class="page-container">
    <h1 class="page-title">О компании</h1>
    
    <div class="page-content">
        <p>Проверочное сообщение</p>
        <p>Это еще одно сообщение!!! DeepSeek - РУЛИТ!!!!!!</p>
        <p>Если вы желаете связаться с автором - звоните по телефону: <?= htmlspecialchars($phone) ?></p>
        
		<h2>Тест блока gallery</h2>
        <div class="row">
            <div class="col-xs-12 col-md-12">
				<!-- Галерея 1: сетка 3 колонки -->
				<div data-block="gallery" 
					data-gallery-id="3"
					data-cols="3"
					data-mode="grid">
				</div>

				<h3>Галерея 2: 4 колонки</h3>
				<div data-block="gallery" 
					data-gallery-id="3"
					data-cols="4">
				</div>

				<h3>Галерея 3: слайдер</h3>
				<div data-block="gallery" 
					data-gallery-id="3"
					data-mode="slider">
				</div>

				<h2>Тест галереи preview</h2>
				<div data-block="gallery" 
					data-gallery-id="3"
					data-mode="preview">
				</div>
            </div>
        </div>

        
        <!-- Изображение слева, текст справа -->
		<h2>Тест блока image</h2> 
        <div class="row">
            <div class="col-xs-12 col-md-6">
                <div data-block="image" 
                     data-src="/uploads/site/2026-09/2026-09-15-991d77396f23_medium.jpg"
                     data-alt="Тестовое изображение"
                     data-caption="Это подпись под изображением">
                </div>
            </div>
            <div class="col-xs-12 col-md-6">
                <p>Текст рядом с изображением. На мобильных — картинка сверху, текст снизу. На десктопе — картинка слева, текст справа.</p>
            </div>
        </div>
        
        <!-- Изображение по центру -->
        <div class="row">
            <div class="col-xs-12 col-md-8 col-md-offset-2">
                <div data-block="image" 
                     data-src="/uploads/site/2026-09/2026-09-15-1c161dcbb71e.svg"
                     data-alt="По центру"
                     data-caption="Это изображение по центру">
                </div>
            </div>
        </div>
        
        <!-- Изображение со ссылкой -->
        <div class="row">
            <div class="col-xs-12 col-md-6">
                <div data-block="image" 
                     data-src="/uploads/site/2026-09/2026-09-15-1c161dcbb71e.svg"
                     data-alt="С ссылкой"
                     data-caption="Со ссылкой"
                     data-link="/">
                </div>
            </div>
        </div>
        
        <!-- Изображение на всю ширину -->
        <div class="row">
            <div class="col-xs-12">
                <div data-block="image" 
                     data-src="/uploads/site/2026-09/2026-09-15-991d77396f23_medium.jpg"
                     data-alt="Описание"
                     data-caption="Это растровое изображение на всю ширину"
                     data-eager="true">
                </div>
            </div>
        </div>
        
        <!-- Две колонки: изображение и текст -->
        <div class="row">
            <div class="col-xs-12 col-md-4">
                <div data-block="image" 
                     data-src="/uploads/site/2026-09/2026-09-15-991d77396f23_thumb.jpg"
                     data-alt="Основатель"
                     data-caption="Основатель">
                </div>
            </div>
            <div class="col-xs-12 col-md-8">
                <p>Компания была основана в 2010 году. За это время мы прошли путь от небольшой студии до крупной компании с офисами в трёх городах.</p>
                <p>Наша команда — это более 50 специалистов. Мы гордимся каждым проектом, который реализовали. Наши клиенты — крупные компании и стартапы, которые ценят качество и скорость работы.</p>
            </div>
        </div>
        
        <!-- Три колонки с изображениями -->
        <div class="row">
            <div class="col-xs-6 col-md-4">
                <div data-block="image" 
                     data-src="/uploads/site/2026-09/2026-09-15-991d77396f23_medium.jpg"
                     data-alt="Фото 1">
                </div>
            </div>
            <div class="col-xs-6 col-md-4">
                <div data-block="image" 
                     data-src="/uploads/site/2026-09/2026-09-15-991d77396f23_medium.jpg"
                     data-alt="Фото 2">
                </div>
            </div>
            <div class="col-xs-6 col-md-4">
                <div data-block="image" 
                     data-src="/uploads/site/2026-09/2026-09-15-991d77396f23_medium.jpg"
                     data-alt="Фото 3">
                </div>
            </div>
        </div>
    </div>
</div>