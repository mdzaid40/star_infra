<?php
if (!defined('APP_ROOT')) { http_response_code(403); exit; }

$siteName = setting('site_name', SITE_NAME);
$email    = setting('contact_email', SITE_EMAIL);
$phone    = setting('contact_phone', SITE_PHONE);
$whatsapp = setting('contact_whatsapp');
$socials  = array_filter([
    'LinkedIn'  => setting('social_linkedin'),
    'Facebook'  => setting('social_facebook'),
    'Instagram' => setting('social_instagram'),
    'YouTube'   => setting('social_youtube'),
]);
?>
</main>
<footer class="site-footer">
  <div class="container footer-main">
    <div class="footer-about">
      <a class="brand brand-light" href="<?= e(url('')) ?>"><img class="brand-logo brand-logo-light" src="<?= e(asset('assets/images/star-infra-logo-white.svg')) ?>" alt="<?= e($siteName) ?>" width="265" height="56" loading="lazy"></a>
      <p><?= e(setting('footer_about')) ?></p>
<?php if ($socials): ?>
      <div class="footer-social">
<?php foreach ($socials as $label => $href): ?>
        <a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a>
<?php endforeach; ?>
      </div>
<?php endif; ?>
    </div>
    <div class="footer-col">
      <h4>Explore</h4>
      <a href="<?= e(url('about.php')) ?>">About us</a>
      <a href="<?= e(url('services.php')) ?>">Our services</a>
      <a href="<?= e(url('projects.php')) ?>">Capabilities</a>
      <a href="<?= e(blog_url()) ?>">Blog</a>
      <a href="<?= e(url('contact.php')) ?>">Contact</a>
    </div>
    <div class="footer-col">
      <h4>Core services</h4>
<?php foreach (array_slice(services(), 0, 6) as $svc): ?>
      <a href="<?= e(url('services.php#' . $svc['slug'])) ?>"><?= e($svc['title']) ?></a>
<?php endforeach; ?>
    </div>
    <div class="footer-col">
      <h4>Get in touch</h4>
      <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
      <a href="<?= e(phone_link($phone)) ?>"><?= e($phone) ?></a>
      <span><?= nl2br(e(setting('contact_address', SITE_ADDRESS)), false) ?></span>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</span>
    <span><?= e(setting('footer_slogan')) ?></span>
  </div>
</footer>
<?php if ($whatsapp !== ''): ?>
<a class="whatsapp-float" href="<?= e(whatsapp_link($whatsapp, 'Hello ' . $siteName . ', I would like to discuss a project.')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp">
  <svg viewBox="0 0 32 32" width="26" height="26" aria-hidden="true"><path fill="currentColor" d="M16 3C8.8 3 3 8.7 3 15.8c0 2.5.7 4.9 2 7L3 29l6.4-2c2 1.1 4.3 1.7 6.6 1.7 7.2 0 13-5.7 13-12.8S23.2 3 16 3zm0 23.4c-2.1 0-4.1-.6-5.8-1.7l-.4-.3-3.8 1.2 1.2-3.7-.3-.4c-1.2-1.8-1.9-3.9-1.9-6 0-5.9 4.9-10.7 11-10.7s11 4.8 11 10.7-4.9 10.9-11 10.9zm6-8c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.2-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.4.3-.6.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.7s1.2 3.2 1.4 3.4c.2.2 2.4 3.6 5.8 5 .8.3 1.4.5 1.9.7.8.3 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4z"/></svg>
</a>
<?php endif; ?>
<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
</body>
</html>
