<h3>🧩 Блоки</h3>

<p><strong>Блок</strong> — это переиспользуемый элемент, который можно вставить в любой модуль или запись.</p>

<h4>Структура блока</h4>
<pre>/blocks/news/
├── block.php    ← логика + HTML (обязательно)
├── style.css    ← стили (опционально)
└── script.js    ← скрипты (опционально)</pre>

<h4>Создание блока</h4>
<ol>
    <li>Админка → <strong>«Блоки»</strong> → «Создать блок»</li>
    <li>Заполни: имя (латиница), название, описание</li>
    <li>Нажми «Создать» — папка и файлы создадутся автоматически</li>
    <li>Открой <code>block.php</code> через «Редактировать код» и напиши логику</li>
</ol>

<h4>Использование блока в модуле</h4>
<pre>&lt;div data-block="news" data-count="3" data-category="tech"&gt;&lt;/div&gt;</pre>

<h4>Переменные в block.php</h4>
<ul>
    <li><code>$params</code> — массив параметров из <code>data-*</code></li>
    <li><code>$blockName</code> — имя блока</li>
    <li><code>$uniqid</code> — уникальный ID экземпляра</li>
</ul>

<h4>Данные блока</h4>
<p>Если блоку нужны данные — создай таблицу с префиксом <code>block_</code>:</p>
<pre>CREATE TABLE block_news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    content TEXT,
    created_at DATETIME
);</pre>

<div class="warning">
    ⚠️ <strong>Важно:</strong> таблицы создаются вручную через phpMyAdmin. При удалении блока таблицы НЕ удаляются.
</div>

<hr style="margin: 40px 0; border: none; border-top: 2px solid var(--admin-border);">

<!-- ============================================ -->
<!-- БЛОК IMAGE -->
<!-- ============================================ -->

<h3>🖼️ Блок <code>image</code> — изображение</h3>

<p>Выводит одиночное изображение с подписью. Поддерживает lightbox при клике.</p>

<h4>Синтаксис</h4>
<pre>&lt;div data-block="image" 
     data-src="/uploads/news/2026-09/foto.jpg"
     data-alt="Описание изображения"
     data-caption="Подпись под фото"
     data-link="/news/125"&gt;
&lt;/div&gt;</pre>

<h4>Параметры</h4>

<table class="help-table">
    <thead>
        <tr>
            <th>Параметр</th>
            <th>Обязательно</th>
            <th>Описание</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>data-src</code></td>
            <td>✅ Да</td>
            <td>Путь к изображению (относительно корня сайта).<br>
                Пример: <code>/uploads/news/2026-09/foto.jpg</code>
            </td>
        </tr>
        <tr>
            <td><code>data-alt</code></td>
            <td>— Нет</td>
            <td>Альтернативный текст (для SEO и скринридеров).</td>
        </tr>
        <tr>
            <td><code>data-caption</code></td>
            <td>— Нет</td>
            <td>Подпись под изображением (курсив, серый цвет, по центру).</td>
        </tr>
        <tr>
            <td><code>data-link</code></td>
            <td>— Нет</td>
            <td>URL, куда ведёт клик по изображению.<br>
                Если не задан — работает <strong>lightbox</strong>.
            </td>
        </tr>
        <tr>
            <td><code>data-eager</code></td>
            <td>— Нет</td>
            <td>
                Загружать изображение сразу (для картинок выше сгиба):<br>
                <code>true</code> — загрузить сразу<br>
                <code>false</code> — lazy-loading (по умолчанию)
            </td>
        </tr>
    </tbody>
</table>

<h4>Примеры</h4>

<p><strong>1. Простое изображение с подписью:</strong></p>
<pre>&lt;div data-block="image" 
     data-src="/uploads/site/logo.svg"
     data-alt="Логотип"
     data-caption="Наш логотип"&gt;
&lt;/div&gt;</pre>

<p><strong>2. Изображение-ссылка:</strong></p>
<pre>&lt;div data-block="image" 
     data-src="/uploads/banners/promo.jpg"
     data-link="/catalog/125"
     data-alt="Акция"&gt;
&lt;/div&gt;</pre>

<p><strong>3. Изображение на всю ширину с немедленной загрузкой:</strong></p>
<pre>&lt;div data-block="image" 
     data-src="/uploads/hero.jpg"
     data-eager="true"&gt;
&lt;/div&gt;</pre>

<div class="note">
    💡 <strong>Совет:</strong> используйте <code>data-eager="true"</code> для изображений <strong>выше сгиба</strong>, чтобы они загрузились сразу. Для остальных — оставьте lazy (по умолчанию).
</div>

<h4>Lightbox</h4>

<p>Если у изображения <strong>нет</strong> <code>data-link</code> — при клике оно откроется в <strong>полноэкранном режиме</strong> (lightbox).</p>

<ul>
    <li><strong>Клик</strong> по изображению → открыть lightbox</li>
    <li><strong>Клик</strong> по фону / изображению / кнопке ✕ → закрыть</li>
    <li><strong>ESC</strong> → закрыть</li>
</ul>

<hr style="margin: 40px 0; border: none; border-top: 2px solid var(--admin-border);">

<!-- ============================================ -->
<!-- БЛОК GALLERY -->
<!-- ============================================ -->

<h3>🎨 Блок <code>gallery</code> — галерея изображений</h3>

<p>Выводит галерею изображений из БД. Поддерживает <strong>три режима отображения</strong>: сетка, слайдер и preview (большое фото + миниатюры).</p>

<h4>Как это работает</h4>

<ol>
    <li><strong>Админка → Галереи → Создать галерею</strong></li>
    <li><strong>Загрузите фото</strong> (drag-and-drop или кнопкой)</li>
    <li><strong>Настройте порядок</strong> — перетаскиванием фото в сетке</li>
    <li><strong>Вставьте блок</strong> в нужное место модуля с параметром <code>data-gallery-id</code></li>
</ol>

<h4>Синтаксис</h4>
<pre>&lt;div data-block="gallery" 
     data-gallery-id="1"
     data-mode="grid"
     data-cols="3"
     data-lightbox="true"&gt;
&lt;/div&gt;</pre>

<h4>Параметры</h4>

<table class="help-table">
    <thead>
        <tr>
            <th>Параметр</th>
            <th>Обязательно</th>
            <th>Описание</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>data-gallery-id</code></td>
            <td>✅ Да</td>
            <td>ID галереи в БД. Найти можно в списке галерей.</td>
        </tr>
        <tr>
            <td><code>data-mode</code></td>
            <td>— Нет</td>
            <td>
                Режим отображения:<br>
                <code>grid</code> — сетка (по умолчанию)<br>
                <code>slider</code> — горизонтальная прокрутка<br>
                <code>preview</code> — большое фото + миниатюры снизу
            </td>
        </tr>
        <tr>
            <td><code>data-cols</code></td>
            <td>— Нет</td>
            <td>
                Количество колонок в сетке (для режима <code>grid</code>):<br>
                <code>2</code>, <code>3</code> (по умолчанию) или <code>4</code>
            </td>
        </tr>
        <tr>
            <td><code>data-lightbox</code></td>
            <td>— Нет</td>
            <td>
                Открывать ли изображение в lightbox:<br>
                <code>true</code> — да (по умолчанию)<br>
                <code>false</code> — нет
            </td>
        </tr>
    </tbody>
</table>

<!-- ============================================ -->
<!-- РЕЖИМЫ ОТОБРАЖЕНИЯ -->
<!-- ============================================ -->

<h4>📐 Режимы отображения</h4>

<h4>1. Режим <code>grid</code> — сетка</h4>

<p>Классическая сетка миниатюр. Клик по фото → lightbox.</p>

<pre>&lt;div data-block="gallery" 
     data-gallery-id="1"
     data-mode="grid"
     data-cols="3"&gt;
&lt;/div&gt;</pre>

<p><strong>Особенности:</strong></p>
<ul>
    <li>Сетка 2, 3 или 4 колонки</li>
    <li>На мобильных — 2 колонки</li>
    <li>Клик → lightbox с навигацией</li>
</ul>

<h4>2. Режим <code>slider</code> — слайдер</h4>

<p>Горизонтальная прокрутка с прилипанием.</p>

<pre>&lt;div data-block="gallery" 
     data-gallery-id="1"
     data-mode="slider"&gt;
&lt;/div&gt;</pre>

<p><strong>Особенности:</strong></p>
<ul>
    <li>Горизонтальная прокрутка</li>
    <li>Скролл-снап для удобства</li>
    <li>Клик → lightbox с навигацией</li>
</ul>

<h4>3. Режим <code>preview</code> — большое фото + миниатюры</h4>

<p>Сверху — <strong>большое фото</strong>, снизу — <strong>лента миниатюр</strong>.</p>

<pre>&lt;div data-block="gallery" 
     data-gallery-id="1"
     data-mode="preview"&gt;
&lt;/div&gt;</pre>

<p><strong>Особенности:</strong></p>
<ul>
    <li>Большое фото сверху</li>
    <li>Миниатюры снизу (по центру)</li>
    <li>Клик по миниатюре → меняется большое фото</li>
    <li>Стрелки ← → для переключения</li>
    <li>Счётчик «текущее / всего» в углу</li>
    <li>Активная миниатюра подсвечена</li>
    <li>Клик по большому фото → lightbox</li>
</ul>

<!-- ============================================ -->
<!-- ПРИМЕРЫ -->
<!-- ============================================ -->

<h4>Примеры</h4>

<p><strong>1. Галерея сеткой 3 колонки:</strong></p>
<pre>&lt;div data-block="gallery" 
     data-gallery-id="1"
     data-mode="grid"
     data-cols="3"&gt;
&lt;/div&gt;</pre>

<p><strong>2. Галерея сеткой 4 колонки:</strong></p>
<pre>&lt;div data-block="gallery" 
     data-gallery-id="1"
     data-cols="4"&gt;
&lt;/div&gt;</pre>

<p><strong>3. Галерея слайдером:</strong></p>
<pre>&lt;div data-block="gallery" 
     data-gallery-id="1"
     data-mode="slider"&gt;
&lt;/div&gt;</pre>

<p><strong>4. Галерея с большим фото и превью:</strong></p>
<pre>&lt;div data-block="gallery" 
     data-gallery-id="1"
     data-mode="preview"&gt;
&lt;/div&gt;</pre>

<p><strong>5. Галерея без lightbox:</strong></p>
<pre>&lt;div data-block="gallery" 
     data-gallery-id="1"
     data-lightbox="false"&gt;
&lt;/div&gt;</pre>

<!-- ============================================ -->
<!-- LIGHTBOX -->
<!-- ============================================ -->

<h4>🔍 Lightbox с навигацией</h4>

<p>При клике на изображение (в любом режиме) — открывается <strong>полноэкранный просмотр</strong> с навигацией:</p>

<ul>
    <li><strong>Стрелки ← →</strong> — переключение между фото</li>
    <li><strong>ESC</strong> — закрыть</li>
    <li><strong>Клик по фону</strong> — закрыть</li>
    <li><strong>Счётчик</strong> вверху — «текущее / всего»</li>
    <li><strong>Подпись</strong> внизу — если задана</li>
</ul>

<!-- ============================================ -->
<!-- УПРАВЛЕНИЕ ГАЛЕРЕЯМИ -->
<!-- ============================================ -->

<h4>⚙️ Управление галереями</h4>

<p>Галереи хранятся в БД (таблицы <code>galleries</code> и <code>gallery_images</code>) и управляются через раздел <strong>«Галереи»</strong> в админке.</p>

<table class="help-table">
    <thead>
        <tr>
            <th>Действие</th>
            <th>Где</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Создать галерею</td>
            <td>Галереи → Создать галерею</td>
        </tr>
        <tr>
            <td>Загрузить фото</td>
            <td>Галереи → Редактировать → Загрузить фото</td>
        </tr>
        <tr>
            <td>Изменить порядок</td>
            <td>Галереи → Редактировать → Перетащить фото</td>
        </tr>
        <tr>
            <td>Редактировать подпись / alt</td>
            <td>Галереи → Редактировать → ✏️ на фото</td>
        </tr>
        <tr>
            <td>Удалить фото</td>
            <td>Галереи → Редактировать → 🗑️ на фото</td>
        </tr>
        <tr>
            <td>Удалить галерею</td>
            <td>Галереи → Список → 🗑️ Удалить</td>
        </tr>
    </tbody>
</table>

<div class="note">
    💡 <strong>Совет:</strong> одна галерея может быть использована <strong>многократно</strong> — в разных модулях и в разных местах. Просто указывайте её <code>data-gallery-id</code>.
</div>

<h4>Рекомендации по изображениям для галерей</h4>

<table class="help-table">
    <thead>
        <tr>
            <th>Параметр</th>
            <th>Значение</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Размер</td>
            <td>800×600 px (4:3)</td>
        </tr>
        <tr>
            <td>Формат</td>
            <td>JPG / WEBP</td>
        </tr>
        <tr>
            <td>Максимальный вес</td>
            <td>15 МБ (при загрузке)</td>
        </tr>
    </tbody>
</table>

<div class="warning">
    ⚠️ <strong>Важно:</strong> пропорции <strong>4:3</strong> — обязательны для галерей. Система автоматически обрежет фото по центру до 4:3.
</div>

<!-- ============================================ -->
<!-- РАЗМЕЩЕНИЕ ЧЕРЕЗ СЕТКУ -->
<!-- ============================================ -->

<h4>📐 Размещение через сетку</h4>

<p>Блоки <code>image</code> и <code>gallery</code> <strong>не управляют</strong> своим размещением. Размещение задаётся <strong>в родителе</strong> через 12-колоночную сетку.</p>

<div class="note">
    💡 <strong>Принцип:</strong> блок отвечает <strong>только за своё содержимое</strong>. Где он находится — решает <strong>модуль</strong> (или другой родитель).
</div>

<h4>Структура сетки</h4>
<pre>&lt;div class="row"&gt;
    &lt;div class="col-xs-12 col-md-6"&gt;
        &lt;!-- Левая колонка (50% на десктопе) --&gt;
    &lt;/div&gt;
    &lt;div class="col-xs-12 col-md-6"&gt;
        &lt;!-- Правая колонка (50% на десктопе) --&gt;
    &lt;/div&gt;
&lt;/div&gt;</pre>

<h4>Классы колонок</h4>

<table class="help-table">
    <thead>
        <tr>
            <th>Префикс</th>
            <th>Устройство</th>
            <th>Ширина экрана</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>col-xs-*</code></td>
            <td>Мобильные</td>
            <td>&lt; 768px</td>
        </tr>
        <tr>
            <td><code>col-sm-*</code></td>
            <td>Планшеты</td>
            <td>≥ 768px</td>
        </tr>
        <tr>
            <td><code>col-md-*</code></td>
            <td>Десктопы</td>
            <td>≥ 992px</td>
        </tr>
        <tr>
            <td><code>col-lg-*</code></td>
            <td>Большие экраны</td>
            <td>≥ 1200px</td>
        </tr>
    </tbody>
</table>

<p><code>*</code> — число от <strong>1</strong> до <strong>12</strong> (сколько колонок занимает).</p>

<h4>Смещение (offset)</h4>
<pre>&lt;div class="col-md-8 col-md-offset-2"&gt;
    &lt;!-- Колонка 8 из 12, отступ слева 2 колонки --&gt;
&lt;/div&gt;</pre>

<h4>Примеры размещения</h4>

<p><strong>1. Галерея на всю ширину:</strong></p>
<pre>&lt;div class="row"&gt;
    &lt;div class="col-xs-12"&gt;
        &lt;div data-block="gallery" data-gallery-id="1"&gt;&lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;</pre>

<p><strong>2. Изображение слева (50%), текст справа (50%):</strong></p>
<pre>&lt;div class="row"&gt;
    &lt;div class="col-xs-12 col-md-6"&gt;
        &lt;div data-block="image" 
             data-src="/uploads/foto.jpg"&gt;
        &lt;/div&gt;
    &lt;/div&gt;
    &lt;div class="col-xs-12 col-md-6"&gt;
        &lt;p&gt;Текст рядом с изображением&lt;/p&gt;
    &lt;/div&gt;
&lt;/div&gt;</pre>

<p><strong>3. Две галереи рядом:</strong></p>
<pre>&lt;div class="row"&gt;
    &lt;div class="col-xs-12 col-md-6"&gt;
        &lt;div data-block="gallery" data-gallery-id="1" data-cols="2"&gt;&lt;/div&gt;
    &lt;/div&gt;
    &lt;div class="col-xs-12 col-md-6"&gt;
        &lt;div data-block="gallery" data-gallery-id="2" data-cols="2"&gt;&lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;</pre>

<p><strong>4. Галерея preview по центру:</strong></p>
<pre>&lt;div class="row"&gt;
    &lt;div class="col-xs-12 col-md-8 col-md-offset-2"&gt;
        &lt;div data-block="gallery" 
             data-gallery-id="1"
             data-mode="preview"&gt;
        &lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;</pre>

<!-- ============================================ -->
<!-- ОТСТУПЫ -->
<!-- ============================================ -->

<h4>📏 Отступы между рядами</h4>

<p>Между рядами <code>.row</code> автоматически добавляется отступ <strong>30px</strong>.</p>

<p>Если нужен <strong>дополнительный</strong> отступ — используй утилитарные классы:</p>

<table class="help-table">
    <thead>
        <tr>
            <th>Класс</th>
            <th>Отступ</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>mt-1</code></td>
            <td>Отступ <strong>сверху</strong> 10px</td>
        </tr>
        <tr>
            <td><code>mt-2</code></td>
            <td>Отступ <strong>сверху</strong> 20px</td>
        </tr>
        <tr>
            <td><code>mt-3</code></td>
            <td>Отступ <strong>сверху</strong> 30px</td>
        </tr>
        <tr>
            <td><code>mb-1</code></td>
            <td>Отступ <strong>снизу</strong> 10px</td>
        </tr>
        <tr>
            <td><code>mb-2</code></td>
            <td>Отступ <strong>снизу</strong> 20px</td>
        </tr>
        <tr>
            <td><code>mb-3</code></td>
            <td>Отступ <strong>снизу</strong> 30px</td>
        </tr>
    </tbody>
</table>

<!-- ============================================ -->
<!-- РЕКОМЕНДАЦИИ ПО ИЗОБРАЖЕНИЯМ -->
<!-- ============================================ -->

<h4>🖼️ Рекомендации по изображениям</h4>

<table class="help-table">
    <thead>
        <tr>
            <th>Назначение</th>
            <th>Размер</th>
            <th>Формат</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Логотип</td>
            <td>до 400×100 px</td>
            <td>PNG / SVG</td>
        </tr>
        <tr>
            <td>Иконки</td>
            <td>32×32, 64×64 px</td>
            <td>SVG / PNG</td>
        </tr>
        <tr>
            <td>Обложка новости</td>
            <td>800×600 px (4:3)</td>
            <td>JPG / WEBP</td>
        </tr>
        <tr>
            <td>Фото в галерее</td>
            <td>800×600 px (4:3)</td>
            <td>JPG / WEBP</td>
        </tr>
        <tr>
            <td>Баннер</td>
            <td>1600×600 px</td>
            <td>JPG / WEBP</td>
        </tr>
    </tbody>
</table>

<div class="note">
    💡 <strong>Важно:</strong> загружайте изображения в нужных пропорциях — система обрежет их по центру до 4:3 для галерей.
</div>