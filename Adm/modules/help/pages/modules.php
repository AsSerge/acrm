<h3>📦 Модули</h3>

<p><strong>Модуль</strong> — это страница сайта. Лежит в папке <code>/modules/</code>.</p>

<h4>Структура модуля</h4>
<pre>/modules/about/
├── index.php    ← основной файл (обязательно)
├── style.css    ← стили (опционально)
└── script.js    ← скрипты (опционально)</pre>

<h4>Создание модуля</h4>
<ol>
    <li>Создай папку в <code>/modules/</code> (например, <code>about</code>)</li>
    <li>Внутри создай <code>index.php</code> с HTML-контентом</li>
    <li>При необходимости добавь <code>style.css</code> и <code>script.js</code></li>
    <li>Админка → <strong>«Модули»</strong> → «Обнаружить модули»</li>
    <li>Открой модуль → заполни: название, slug, SEO, шаблон</li>
</ol>

<h4>Поля модуля</h4>
<ul>
    <li><strong>Title</strong> — название для админки</li>
    <li><strong>Slug</strong> — URL-адрес модуля (например, <code>about</code>)</li>
    <li><strong>Meta Title / Meta Description</strong> — SEO</li>
    <li><strong>Шаблон</strong> — обёртка из <code>/templates/modules/</code></li>
    <li><strong>Доступ для групп</strong> — какие группы видят модуль</li>
</ul>

<h4>Шаблоны</h4>
<p>Шаблоны лежат в <code>/templates/modules/</code>. Список — в <code>_manifest.php</code>.</p>
<ul>
    <li><code>default</code> — стандартный (ограниченная ширина)</li>
    <li><code>full-width</code> — на всю ширину</li>
    <li><code>with-sidebar</code> — с боковой панелью</li>
    <li><code>landing</code> — без шапки и подвала</li>
    <li><code>maintenance</code> — для заглушек</li>
</ul>

<div class="note">
    💡 <strong>Приоритет SEO:</strong> сначала SEO модуля, потом — глобальное SEO.
</div>