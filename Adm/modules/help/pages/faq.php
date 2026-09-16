<h3>❓ Частые вопросы</h3>

<h4>Как добавить новую страницу?</h4>
<ol>
    <li>Создай папку в <code>/modules/</code> (например, <code>services</code>)</li>
    <li>Внутри создай <code>index.php</code> с HTML</li>
    <li>Админка → Модули → «Обнаружить модули»</li>
    <li>Заполни slug, название, шаблон</li>
    <li>Админка → Меню → создай пункт, выбери этот модуль</li>
</ol>

<h4>Как поменять название сайта?</h4>
<p>Админка → <strong>Настройки</strong> → вкладка «Основные» → поле «Название сайта».</p>

<h4>Как вставить блок в страницу?</h4>
<pre>&lt;div data-block="news" data-count="3"&gt;&lt;/div&gt;</pre>
<p>Где <code>news</code> — имя блока, <code>count</code> — параметр.</p>

<h4>Как скрыть модуль от гостей?</h4>
<p>В редактировании модуля → «Доступ для групп» → выбери нужные группы. Если ничего не выбрано — видят только авторизованные.</p>

<h4>Что делать, если страница не открывается?</h4>
<ol>
    <li>Проверь, что модуль <strong>активен</strong> в админке</li>
    <li>Проверь, что он <strong>is_frontend = 1</strong></li>
    <li>Проверь slug модуля — он должен совпадать с URL</li>
    <li>Проверь, что файл <code>/modules/имя/index.php</code> существует</li>
</ol>

<h4>Как очистить кэш настроек?</h4>
<p>Настройки кэшируются автоматически. Если нужно — перезагрузи страницу (Ctrl+F5).</p>

<h4>Где хранятся настройки?</h4>
<p>В таблице <code>system_settings</code> в базе данных.</p>

<h4>Как создать таблицу для блока?</h4>
<pre>CREATE TABLE block_news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);</pre>

<div class="note">
    💡 <strong>Правило именования:</strong> таблицы блоков всегда начинаются с <code>block_</code>.
</div>