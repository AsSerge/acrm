<?php
/**
 * Подвал сайта (фронтенд)
 * С поддержкой настроек
 */

// Настройки (уже загружены в header.php, но подстрахуемся)
Setting::load();

$siteName = Setting::get('site_name', 'ZoomCRM');
$contactPhone = Setting::get('contact_phone');
$contactEmail = Setting::get('contact_email');
$contactAddress = Setting::get('contact_address');
$contactHours = Setting::get('contact_working_hours');

$socialVk = Setting::get('social_vk');
$socialTelegram = Setting::get('social_telegram');
$socialWhatsapp = Setting::get('social_whatsapp');
$socialYoutube = Setting::get('social_youtube');
$socialInstagram = Setting::get('social_instagram');

$hasSocial = $socialVk || $socialTelegram || $socialWhatsapp || $socialYoutube || $socialInstagram;
$hasContacts = $contactPhone || $contactEmail || $contactAddress || $contactHours;
?>
        </div>
    </main>
    
    <footer class="site-footer">
        <div class="container">
            <?php if ($hasContacts || $hasSocial): ?>
                <div class="footer-content">
                    <?php if ($hasContacts): ?>
                        <div class="footer-section">
                            <h4>Контакты</h4>
                            <ul class="footer-contacts">
                                <?php if ($contactPhone): ?>
                                    <li>📞 <a href="tel:<?= htmlspecialchars(str_replace([' ', '(', ')', '-'], '', $contactPhone)) ?>"><?= htmlspecialchars($contactPhone) ?></a></li>
                                <?php endif; ?>
                                <?php if ($contactEmail): ?>
                                    <li>✉️ <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a></li>
                                <?php endif; ?>
                                <?php if ($contactAddress): ?>
                                    <li>📍 <?= htmlspecialchars($contactAddress) ?></li>
                                <?php endif; ?>
                                <?php if ($contactHours): ?>
                                    <li>🕐 <?= htmlspecialchars($contactHours) ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($hasSocial): ?>
                        <div class="footer-section">
                            <h4>Мы в соцсетях</h4>
                            <ul class="footer-social">
                                <?php if ($socialVk): ?>
                                    <li><a href="<?= htmlspecialchars($socialVk) ?>" target="_blank" rel="noopener">ВКонтакте</a></li>
                                <?php endif; ?>
                                <?php if ($socialTelegram): ?>
                                    <li><a href="<?= htmlspecialchars($socialTelegram) ?>" target="_blank" rel="noopener">Telegram</a></li>
                                <?php endif; ?>
                                <?php if ($socialWhatsapp): ?>
                                    <li><a href="<?= htmlspecialchars($socialWhatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a></li>
                                <?php endif; ?>
                                <?php if ($socialYoutube): ?>
                                    <li><a href="<?= htmlspecialchars($socialYoutube) ?>" target="_blank" rel="noopener">YouTube</a></li>
                                <?php endif; ?>
                                <?php if ($socialInstagram): ?>
                                    <li><a href="<?= htmlspecialchars($socialInstagram) ?>" target="_blank" rel="noopener">Instagram</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="footer-inner">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. Все права защищены.</p>
                <p class="footer-version">CMS версия 1.0.0</p>
            </div>
        </div>
    </footer>
</body>
</html>

<style>
.footer-content {
    display: flex;
    gap: 40px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}
.footer-section {
    flex: 1;
    min-width: 200px;
}
.footer-section h4 {
    color: var(--footer-text, #a0a0a0);
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.footer-contacts,
.footer-social {
    list-style: none;
    padding: 0;
    margin: 0;
}
.footer-contacts li,
.footer-social li {
    padding: 4px 0;
    font-size: 14px;
}
.footer-contacts a,
.footer-social a {
    color: var(--footer-text, #a0a0a0);
    text-decoration: none;
    transition: color 0.2s ease;
}
.footer-contacts a:hover,
.footer-social a:hover {
    color: var(--header-accent, #64b5f6);
}
</style>