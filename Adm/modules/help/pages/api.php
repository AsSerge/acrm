<h3>🔧 API классов</h3>

<p>Все системные классы доступны в любом модуле и блоке. Ниже — их описание и примеры использования.</p>

<!-- ============================================ -->
<!-- КЛАСС Setting -->
<!-- ============================================ -->

<h4>⚙️ Класс <code>Setting</code></h4>
<p>Управление настройками сайта из таблицы <code>system_settings</code>.</p>

<p><strong>Методы:</strong></p>
<ul>
    <li><code>Setting::get($key, $default = null)</code> — получить значение</li>
    <li><code>Setting::has($key)</code> — проверить наличие</li>
    <li><code>Setting::set($key, $value)</code> — сохранить</li>
    <li><code>Setting::getBool($key, $default = false)</code> — булево значение</li>
    <li><code>Setting::getByPrefix($prefix)</code> — все настройки по префиксу</li>
    <li><code>Setting::all()</code> — все настройки</li>
</ul>

<p><strong>Пример в модуле:</strong></p>
<pre>&lt;?php
// /modules/about/index.php
$siteName = Setting::get('site_name');
$phone = Setting::get('contact_phone');
$email = Setting::get('contact_email');
?&gt;

&lt;h1&gt;Добро пожаловать в &lt;?= htmlspecialchars($siteName) ?&gt;&lt;/h1&gt;
&lt;p&gt;Телефон: &lt;?= htmlspecialchars($phone) ?&gt;&lt;/p&gt;</pre>

<p><strong>Пример в блоке:</strong></p>
<pre>&lt;?php
// /blocks/news/block.php
$siteName = Setting::get('site_name');
?&gt;

&lt;div class="news-block"&gt;
    &lt;h2&gt;Новости &lt;?= htmlspecialchars($siteName) ?&gt;&lt;/h2&gt;
    ...
&lt;/div&gt;</pre>

<!-- ============================================ -->
<!-- ТАБЛИЦА КЛЮЧЕЙ -->
<!-- ============================================ -->

<h4>📋 Все ключи настроек</h4>

<table class="help-table">
    <thead>
        <tr>
            <th>Ключ</th>
            <th>Тип</th>
            <th>Описание</th>
            <th>По умолчанию</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="4" class="group-title">📋 Основные</td>
        </tr>
        <tr>
            <td><code>site_name</code></td>
            <td>string</td>
            <td>Название сайта</td>
            <td>ZoomCRM</td>
        </tr>
        <tr>
            <td><code>site_description</code></td>
            <td>string</td>
            <td>Описание сайта</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>site_keywords</code></td>
            <td>string</td>
            <td>Ключевые слова (через запятую)</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>site_email</code></td>
            <td>string</td>
            <td>Email сайта</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>site_language</code></td>
            <td>string</td>
            <td>Язык сайта (ru, en)</td>
            <td>ru</td>
        </tr>
        <tr>
            <td><code>site_timezone</code></td>
            <td>string</td>
            <td>Часовой пояс</td>
            <td>Europe/Moscow</td>
        </tr>
        
        <tr>
            <td colspan="4" class="group-title">🎨 Внешний вид</td>
        </tr>
        <tr>
            <td><code>logo_text</code></td>
            <td>string</td>
            <td>Текст логотипа</td>
            <td>ZoomCRM</td>
        </tr>
        <tr>
            <td><code>accent_color</code></td>
            <td>string</td>
            <td>Акцентный цвет (HEX)</td>
            <td>#64b5f6</td>
        </tr>
        <tr>
            <td><code>default_template</code></td>
            <td>string</td>
            <td>Шаблон модулей по умолчанию</td>
            <td>default</td>
        </tr>
        
        <tr>
            <td colspan="4" class="group-title">📞 Контакты</td>
        </tr>
        <tr>
            <td><code>contact_phone</code></td>
            <td>string</td>
            <td>Телефон</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>contact_email</code></td>
            <td>string</td>
            <td>Email для связи</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>contact_address</code></td>
            <td>string</td>
            <td>Адрес</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>contact_working_hours</code></td>
            <td>string</td>
            <td>Режим работы</td>
            <td>—</td>
        </tr>
        
        <tr>
            <td colspan="4" class="group-title">🔗 Соцсети</td>
        </tr>
        <tr>
            <td><code>social_vk</code></td>
            <td>string</td>
            <td>ВКонтакте</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>social_telegram</code></td>
            <td>string</td>
            <td>Telegram</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>social_whatsapp</code></td>
            <td>string</td>
            <td>WhatsApp</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>social_youtube</code></td>
            <td>string</td>
            <td>YouTube</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>social_instagram</code></td>
            <td>string</td>
            <td>Instagram</td>
            <td>—</td>
        </tr>
        
        <tr>
            <td colspan="4" class="group-title">🔍 SEO</td>
        </tr>
        <tr>
            <td><code>seo_meta_title</code></td>
            <td>string</td>
            <td>Meta Title по умолчанию</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>seo_meta_description</code></td>
            <td>string</td>
            <td>Meta Description по умолчанию</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>seo_google_analytics</code></td>
            <td>string</td>
            <td>Google Analytics ID</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>seo_yandex_metrika</code></td>
            <td>string</td>
            <td>Яндекс.Метрика ID</td>
            <td>—</td>
        </tr>
        
        <tr>
            <td colspan="4" class="group-title">⚙️ Система</td>
        </tr>
        <tr>
            <td><code>maintenance_mode</code></td>
            <td>bool</td>
            <td>Режим обслуживания (0/1)</td>
            <td>0</td>
        </tr>
        <tr>
            <td><code>maintenance_module</code></td>
            <td>string</td>
            <td>Slug модуля для заглушки</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>robots_txt</code></td>
            <td>text</td>
            <td>Содержимое robots.txt</td>
            <td>—</td>
        </tr>
        <tr>
            <td><code>sitemap_last_generated</code></td>
            <td>datetime</td>
            <td>Дата последней генерации</td>
            <td>—</td>
        </tr>
    </tbody>
</table>

<div class="note">
    💡 <strong>Совет:</strong> используй <code>Setting::getBool('maintenance_mode')</code> для булевых значений — вернёт <code>true</code> или <code>false</code>.
</div>

<!-- ============================================ -->
<!-- ПРИМЕРЫ -->
<!-- ============================================ -->

<h4>💡 Примеры использования</h4>

<p><strong>1. Логотип и телефон в подвале:</strong></p>
<pre>&lt;?php
// В любом модуле или блоке
$logoText = Setting::get('logo_text', 'Моя CMS');
$phone = Setting::get('contact_phone');
?&gt;

&lt;footer&gt;
    &lt;p&gt;&amp;copy; &lt;?= date('Y') ?&gt; &lt;?= htmlspecialchars($logoText) ?&gt;&lt;/p&gt;
    &lt;?php if ($phone): ?&gt;
        &lt;p&gt;Тел: &lt;?= htmlspecialchars($phone) ?&gt;&lt;/p&gt;
    &lt;?php endif; ?&gt;
&lt;/footer&gt;</pre>

<p><strong>2. Проверка режима обслуживания:</strong></p>
<pre>&lt;?php
if (Setting::getBool('maintenance_mode')) {
    echo '&lt;div class="alert"&gt;Сайт на обслуживании&lt;/div&gt;';
}
?&gt;</pre>

<p><strong>3. Получение всех контактов:</strong></p>
<pre>&lt;?php
$contacts = Setting::getByPrefix('contact_');
// Результат: ['contact_phone' =&gt; '...', 'contact_email' =&gt; '...']
?&gt;</pre>

<!-- ============================================ -->
<!-- БУДУЩИЕ КЛАССЫ -->
<!-- ============================================ -->

<h4>📌 Другие классы (в разработке)</h4>

<table class="help-table">
    <thead>
        <tr>
            <th>Класс</th>
            <th>Назначение</th>
            <th>Статус</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>Setting</code></td>
            <td>Настройки сайта</td>
            <td>✅ Готов</td>
        </tr>
        <tr>
            <td><code>ModuleManager</code></td>
            <td>Управление модулями</td>
            <td>✅ Готов</td>
        </tr>
        <tr>
            <td><code>BlockManager</code></td>
            <td>Управление блоками</td>
            <td>✅ Готов</td>
        </tr>
        <tr>
            <td><code>BlockRenderer</code></td>
            <td>Рендер блоков</td>
            <td>✅ Готов</td>
        </tr>
        <tr>
            <td><code>MenuBuilder</code></td>
            <td>Построение меню</td>
            <td>✅ Готов</td>
        </tr>
        <tr>
            <td><code>ImageUploader</code></td>
            <td>Загрузка изображений</td>
            <td>⏳ В разработке</td>
        </tr>
    </tbody>
</table>

<div class="note">
    💡 Этот раздел будет <strong>дополняться</strong> по мере разработки новых классов.
</div>