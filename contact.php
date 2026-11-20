<?php
require __DIR__ . '/includes/bootstrap.php';
session_boot();

$pageTitle       = 'Contact';
$pageDescription = 'Contact ' . setting('site_name', SITE_NAME) . ' for tenders, subcontracting, pipeline, mechanical, civil and maintenance project enquiries.';
$activeNav       = 'contact';
$canonicalPath   = 'contact.php';

$serviceOptions = array_column(services(), 'title');
$serviceOptions[] = 'Tender / Subcontracting';
$serviceOptions[] = 'Other';

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'service' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'    => input('name', 100),
        'email'   => input('email', 150),
        'phone'   => input('phone', 30),
        'service' => input('service', 150),
        'message' => input('message', 4000),
    ];
    $startedAt = (int)($_SESSION['contact_form_ts'] ?? 0);

    if (!csrf_verify()) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    } elseif (input('website') !== '' || ($startedAt && time() - $startedAt < 3)) {
        // Bot detected (honeypot filled or submitted unrealistically fast): pretend success.
        flash('success', 'Thank you. Your enquiry has been submitted.');
        redirect('contact.php#enquiry-form');
    } else {
        if (mb_strlen($old['name']) < 2) { $errors['name'] = 'Please enter your name.'; }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Please enter a valid email address.'; }
        if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s().]{6,30}$/', $old['phone'])) { $errors['phone'] = 'Please enter a valid phone number.'; }
        if ($old['service'] !== '' && !in_array($old['service'], $serviceOptions, true)) { $old['service'] = 'Other'; }
        if (mb_strlen($old['message']) < 10) { $errors['message'] = 'Please tell us a little more about your requirement (at least 10 characters).'; }
    }

    $pdo = is_installed() ? db_try() : null;

    // Rate limit: max 5 enquiries per IP per hour (uses indexed ip+created_at).
    if (!$errors && $pdo) {
        try {
            $st = $pdo->prepare('SELECT COUNT(*) FROM enquiries WHERE ip = ? AND created_at > ?');
            $st->execute([client_ip(), date('Y-m-d H:i:s', time() - 3600)]);
            if ((int)$st->fetchColumn() >= 5) {
                $errors['form'] = 'You have sent several enquiries recently. Please try again later or contact us by phone.';
            }
        } catch (Throwable $e) { error_log('[contact] rate limit check: ' . $e->getMessage()); }
    }

    if (!$errors) {
        $saved = false;
        $enquiryId = null;
        if ($pdo) {
            try {
                $st = $pdo->prepare('INSERT INTO enquiries (name, email, phone, service, message, ip, user_agent, created_at) VALUES (?,?,?,?,?,?,?,?)');
                $st->execute([$old['name'], $old['email'], $old['phone'], $old['service'], $old['message'], client_ip(),
                              mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255), now()]);
                $enquiryId = (int)$pdo->lastInsertId();
                $saved = true;
            } catch (Throwable $e) { error_log('[contact] save failed: ' . $e->getMessage()); }
        }

        $to = setting('notify_email', setting('contact_email', SITE_EMAIL));
        $body = "New website enquiry\n\n"
              . "Name:    {$old['name']}\n"
              . "Email:   {$old['email']}\n"
              . "Phone:   " . ($old['phone'] ?: '-') . "\n"
              . "Service: " . ($old['service'] ?: '-') . "\n\n"
              . "Message:\n{$old['message']}\n\n"
              . "--\nSent from " . abs_url('contact.php') . ' on ' . date('j M Y, g:i a') . "\nIP: " . client_ip();
        $mailError = null;
        $sent = send_mail($to, 'Website enquiry from ' . $old['name'] . ' - ' . setting('site_name', SITE_NAME), $body, $old['email'], $mailError);

        if ($sent && $enquiryId) {
            try { $pdo->prepare('UPDATE enquiries SET mail_sent = 1 WHERE id = ?')->execute([$enquiryId]); } catch (Throwable $e) { /* not critical */ }
        }

        if ($saved || $sent) {
            unset($_SESSION['contact_form_ts']);
            flash('success', 'Thank you. Your enquiry has been submitted — our team will get back to you shortly.');
            redirect('contact.php#enquiry-form');
        }
        $errors['form'] = 'We could not send your enquiry right now. Please email us directly at ' . setting('contact_email', SITE_EMAIL) . '.';
    }
}

$_SESSION['contact_form_ts'] = $_SESSION['contact_form_ts'] ?? time();
$messages = flashes();
$email    = setting('contact_email', SITE_EMAIL);
$phone    = setting('contact_phone', SITE_PHONE);
$mapUrl   = setting('map_embed_url');

$fieldError = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? '<span class="field-error" id="err-' . $key . '">' . e($errors[$key]) . '</span>' : '';
};
$invalid = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . $key . '"' : '';
};

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <div class="eyebrow"><span></span> CONTACT</div>
    <h1>Let's talk about<br> <em>your next project.</em></h1>
    <p>Tell us a little about your requirement. Our team will get back to you.</p>
  </div>
</section>

<section class="section-pad">
  <div class="container contact-layout">
    <div class="contact-info">
      <div class="eyebrow"><span></span> GET IN TOUCH</div>
      <h2>Start with a<br> <em>conversation.</em></h2>
      <p>For tenders, subcontracting opportunities, project enquiries or general information, reach out to us.</p>
      <div class="contact-line"><small>EMAIL</small><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div>
      <div class="contact-line"><small>PHONE</small><a href="<?= e(phone_link($phone)) ?>"><?= e($phone) ?></a></div>
<?php if (setting('contact_whatsapp') !== ''): ?>
      <div class="contact-line"><small>WHATSAPP</small><a href="<?= e(whatsapp_link(setting('contact_whatsapp'))) ?>" target="_blank" rel="noopener noreferrer">Chat with us</a></div>
<?php endif; ?>
      <div class="contact-line"><small>LOCATION</small><span><?= nl2br(e(setting('contact_address', SITE_ADDRESS)), false) ?></span></div>
    </div>

    <form class="contact-form" id="enquiry-form" action="<?= e(url('contact.php')) ?>#enquiry-form" method="post" novalidate>
<?php foreach ($messages as $m): ?>
      <div class="form-<?= $m['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= e($m['message']) ?></div>
<?php endforeach; ?>
<?php if (isset($errors['form'])): ?>
      <div class="form-error" role="alert"><?= e($errors['form']) ?></div>
<?php elseif ($errors): ?>
      <div class="form-error" role="alert">Please correct the highlighted fields.</div>
<?php endif; ?>
      <?= csrf_field() ?>
      <label>Your name
        <input name="name" type="text" required maxlength="100" autocomplete="name" placeholder="Full name" value="<?= e($old['name']) ?>"<?= $invalid('name') ?>>
        <?= $fieldError('name') ?>
      </label>
      <div class="form-row">
        <label>Email address
          <input name="email" type="email" required maxlength="150" autocomplete="email" inputmode="email" placeholder="name@company.com" value="<?= e($old['email']) ?>"<?= $invalid('email') ?>>
          <?= $fieldError('email') ?>
        </label>
        <label>Phone number
          <input name="phone" type="tel" maxlength="30" autocomplete="tel" inputmode="tel" placeholder="+91" value="<?= e($old['phone']) ?>"<?= $invalid('phone') ?>>
          <?= $fieldError('phone') ?>
        </label>
      </div>
      <label>Area of interest
        <select name="service">
          <option value="">Select a service</option>
<?php foreach ($serviceOptions as $opt): ?>
          <option<?= $old['service'] === $opt ? ' selected' : '' ?>><?= e($opt) ?></option>
<?php endforeach; ?>
        </select>
      </label>
      <label>Tell us about your requirement
        <textarea name="message" rows="5" required minlength="10" maxlength="4000" placeholder="Project location, scope, timeline or any other details..."<?= $invalid('message') ?>><?= e($old['message']) ?></textarea>
        <?= $fieldError('message') ?>
      </label>
      <div class="hp-field" aria-hidden="true"><label>Leave this field empty<input name="website" tabindex="-1" autocomplete="off"></label></div>
      <button class="btn btn-dark" type="submit">Send enquiry <span aria-hidden="true">↗</span></button>
      <small class="form-note">Your details will only be used to respond to this enquiry.</small>
    </form>
  </div>
</section>

<?php if ($mapUrl !== '' && preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed#i', $mapUrl)): ?>
<section class="map-section">
  <iframe src="<?= e($mapUrl) ?>" title="Office location map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
