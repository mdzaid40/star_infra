<?php
/**
 * Minimal, dependency-free mail sender.
 * Uses authenticated SMTP when SMTP_HOST is configured (recommended on Hostinger),
 * otherwise falls back to PHP mail().
 */
if (!defined('APP_ROOT')) { http_response_code(403); exit; }

function mail_header_safe(string $value): string
{
    return trim(str_replace(["\r", "\n", "\0"], ' ', $value));
}

function mail_encode_header(string $value): string
{
    $value = mail_header_safe($value);
    return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
}

function mail_site_domain(): string
{
    $host = (string)parse_url(site_origin(), PHP_URL_HOST);
    $host = preg_replace('/^www\./i', '', $host);
    return $host !== '' ? $host : 'localhost';
}

function mail_from_address(): string
{
    foreach ([MAIL_FROM, SMTP_USER] as $candidate) {
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) { return $candidate; }
    }
    return 'website@' . mail_site_domain();
}

/**
 * Send a plain-text email. Returns true on success; $error receives a reason on failure.
 */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null, ?string &$error = null): bool
{
    $error = null;
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid recipient address.'; return false; }
    if ($replyTo !== null && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) { $replyTo = null; }

    $from = mail_from_address();
    $fromName = setting('site_name', SITE_NAME);
    $body = str_replace(["\r\n", "\r"], "\n", $body);

    $headers = [
        'Date'                      => date('r'),
        'From'                      => mail_encode_header($fromName) . ' <' . $from . '>',
        'To'                        => '<' . $to . '>',
        'Subject'                   => mail_encode_header($subject),
        'Message-ID'                => '<' . bin2hex(random_bytes(12)) . '@' . mail_site_domain() . '>',
        'MIME-Version'              => '1.0',
        'Content-Type'              => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => 'base64',
        'X-Mailer'                  => 'StarInfra-Site',
    ];
    if ($replyTo) { $headers['Reply-To'] = '<' . $replyTo . '>'; }
    $encodedBody = rtrim(chunk_split(base64_encode($body), 76, "\r\n"));

    if (SMTP_HOST !== '') {
        $ok = smtp_send($to, $from, $headers, $encodedBody, $error);
        if (!$ok) { error_log('[mail] SMTP failed: ' . $error); }
        return $ok;
    }

    if (!function_exists('mail')) { $error = 'PHP mail() is disabled on this server.'; return false; }

    $mailHeaders = $headers;
    unset($mailHeaders['To'], $mailHeaders['Subject']); // mail() adds these itself
    $headerLines = '';
    foreach ($mailHeaders as $k => $v) { $headerLines .= $k . ': ' . $v . "\r\n"; }
    $headerLines = rtrim($headerLines);

    $ok = @mail($to, $headers['Subject'], $encodedBody, $headerLines, '-f' . $from);
    if (!$ok) { $ok = @mail($to, $headers['Subject'], $encodedBody, $headerLines); }
    if (!$ok) { $error = 'mail() returned false.'; error_log('[mail] mail() failed for ' . $to); }
    return $ok;
}

function smtp_send(string $to, string $from, array $headers, string $encodedBody, ?string &$error): bool
{
    $secure = strtolower((string)SMTP_SECURE);
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . (int)SMTP_PORT;
    $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => SMTP_HOST]]);

    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) { $error = "Could not connect to " . SMTP_HOST . ": $errstr ($errno)"; return false; }
    stream_set_timeout($fp, 20);

    $read = static function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') { break; }
        }
        return $data;
    };
    // $label is what gets logged instead of the raw command (hides credentials).
    $step = static function (?string $command, array $expect, string $label) use ($fp, $read, &$error): bool {
        if ($command !== null) { fwrite($fp, $command . "\r\n"); }
        $reply = $read();
        $code = (int)substr($reply, 0, 3);
        if (!in_array($code, $expect, true)) {
            $error = $label . ' failed: ' . trim($reply ?: 'no response');
            return false;
        }
        return true;
    };

    $ehloHost = mail_site_domain();
    $ok = $step(null, [220], 'Greeting') && $step('EHLO ' . $ehloHost, [250], 'EHLO');

    if ($ok && $secure === 'tls') {
        $ok = $step('STARTTLS', [220], 'STARTTLS');
        if ($ok) {
            $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0);
            if (!@stream_socket_enable_crypto($fp, true, $method)) { $error = 'TLS negotiation failed.'; $ok = false; }
        }
        $ok = $ok && $step('EHLO ' . $ehloHost, [250], 'EHLO (TLS)');
    }

    if ($ok && SMTP_USER !== '') {
        $ok = $step('AUTH LOGIN', [334], 'AUTH')
           && $step(base64_encode(SMTP_USER), [334], 'AUTH username')
           && $step(base64_encode(SMTP_PASS), [235], 'AUTH password');
    }

    $ok = $ok && $step('MAIL FROM:<' . $from . '>', [250], 'MAIL FROM')
              && $step('RCPT TO:<' . $to . '>', [250, 251], 'RCPT TO')
              && $step('DATA', [354], 'DATA');

    if ($ok) {
        $message = '';
        foreach ($headers as $k => $v) { $message .= $k . ': ' . $v . "\r\n"; }
        $message .= "\r\n" . $encodedBody . "\r\n";
        $message = preg_replace('/^\./m', '..', $message); // dot-stuffing
        fwrite($fp, $message . ".\r\n");
        $ok = $step(null, [250], 'Send');
    }

    @fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $ok;
}
