<?php
// includes/smtp_mailer.php
//
// A minimal SMTP client using plain PHP sockets — no Composer, no
// PHPMailer, no external libraries. Use this when PHP's built-in mail()
// isn't working on your host (very common on shared hosting where
// sendmail isn't configured or outbound mail is blocked).
//
// This talks directly to an SMTP server you have real credentials for —
// typically the one from your hosting control panel's email account, or
// a transactional provider like Gmail SMTP, SendGrid, Mailgun, etc.

// === SMTP CONFIGURATION — FILL THESE IN ===
//const SMTP_HOST       = 'free.mboxhosting.com';   // your SMTP server, e.g. smtp.gmail.com
//const SMTP_HOST       = 'localhost';   // your SMTP server, e.g. smtp.gmail.com
//const SMTP_PORT       = 465;                      // 587 = STARTTLS (most common), 465 = implicit SSL, 25 = unencrypted (avoid if possible)
//const SMTP_PORT       = 587;
//const SMTP_PORT       = 25;

//const SMTP_ENCRYPTION = 'ssl';                    // 'tls' (STARTTLS on 587), 'ssl' (implicit SSL on 465), or '' for none
//const SMTP_ENCRYPTION = 'tls';
//const SMTP_USERNAME   = 'no-reply@bookappointment.me';     // usually the full mailbox address
//const SMTP_PASSWORD   = 'T12tbEsa*tb';
const SMTP_TIMEOUT    = 15;                       // seconds
const SMTP_VERIFY_PEER = true;                    // set to false only if your host's mail server has a cert PHP won't validate — try true first
// ============================================

/**
 * Send an email via SMTP using the config above.
 * Returns [success(bool), debugInfo(string)] — debugInfo is only useful
 * for troubleshooting; never show it to end users.
 */
function smtp_send_email(string $to, string $subject, string $body, string $fromEmail, string $fromName = ''): array {
    $log = [];
    $socket = null;

    try {
        // --- Connect ---
        $transport = SMTP_ENCRYPTION === 'ssl' ? 'ssl://' : 'tcp://';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => SMTP_VERIFY_PEER,
                'verify_peer_name'  => SMTP_VERIFY_PEER,
                'allow_self_signed' => !SMTP_VERIFY_PEER,
            ],
        ]);
        $socket = @stream_socket_client(
            $transport . SMTP_HOST . ':' . SMTP_PORT,
            $errno, $errstr, SMTP_TIMEOUT,
            STREAM_CLIENT_CONNECT, $context
        );
        if (!$socket) {
            return [false, "Connection failed: $errstr ($errno)"];
        }
        stream_set_timeout($socket, SMTP_TIMEOUT);

        $read = function () use ($socket, &$log) {
            $data = '';
            while (($line = fgets($socket, 515)) !== false) {
                $data .= $line;
                // Multiline SMTP responses use "250-" for continuation lines
                // and "250 " (space) on the final line of the block.
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            $log[] = 'S: ' . trim($data);
            return $data;
        };

        $write = function (string $cmd, bool $sensitive = false) use ($socket, &$log) {
            $log[] = 'C: ' . ($sensitive ? '[credential omitted]' : trim($cmd));
            fwrite($socket, $cmd . "\r\n");
        };

        $expect = function (string $data, array $codes) {
            $code = (int)substr($data, 0, 3);
            return in_array($code, $codes, true);
        };

        // --- Greeting ---
        $resp = $read();
        if (!$expect($resp, [220])) return [false, "No greeting from server. Log:\n" . implode("\n", $log)];

        // --- EHLO ---
        $write('EHLO ' . (parse_url(site_base_url_safe(), PHP_URL_HOST) ?: 'localhost'));
        $resp = $read();
        if (!$expect($resp, [250])) return [false, "EHLO failed. Log:\n" . implode("\n", $log)];

        // --- STARTTLS (if configured) ---
        if (SMTP_ENCRYPTION === 'tls') {
            $write('STARTTLS');
            $resp = $read();
            if (!$expect($resp, [220])) return [false, "STARTTLS refused. Log:\n" . implode("\n", $log)];

            $cryptoOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$cryptoOk) return [false, "TLS handshake failed. Log:\n" . implode("\n", $log)];

            // Must re-send EHLO after STARTTLS — the server resets its
            // capability list on the now-encrypted connection.
            $write('EHLO ' . (parse_url(site_base_url_safe(), PHP_URL_HOST) ?: 'localhost'));
            $resp = $read();
            if (!$expect($resp, [250])) return [false, "EHLO after STARTTLS failed. Log:\n" . implode("\n", $log)];
        }

        // --- AUTH LOGIN (if credentials configured) ---
        if (SMTP_USERNAME !== '') {
            $write('AUTH LOGIN');
            $resp = $read();
            if (!$expect($resp, [334])) return [false, "AUTH LOGIN not accepted. Log:\n" . implode("\n", $log)];

            $write(base64_encode(SMTP_USERNAME), true);
            $resp = $read();
            if (!$expect($resp, [334])) return [false, "Username rejected. Log:\n" . implode("\n", $log)];

            $write(base64_encode(SMTP_PASSWORD), true);
            $resp = $read();
            if (!$expect($resp, [235])) return [false, "Authentication failed — check SMTP username/password. Log:\n" . implode("\n", $log)];
        }

        // --- MAIL FROM / RCPT TO ---
        $write('MAIL FROM:<' . $fromEmail . '>');
        $resp = $read();
        if (!$expect($resp, [250])) return [false, "MAIL FROM rejected. Log:\n" . implode("\n", $log)];

        $write('RCPT TO:<' . $to . '>');
        $resp = $read();
        if (!$expect($resp, [250, 251])) return [false, "RCPT TO rejected (recipient refused). Log:\n" . implode("\n", $log)];

        // --- DATA ---
        $write('DATA');
        $resp = $read();
        if (!$expect($resp, [354])) return [false, "DATA command refused. Log:\n" . implode("\n", $log)];

        $from = $fromName !== '' ? "$fromName <$fromEmail>" : $fromEmail;
        $headers =
            "From: $from\r\n" .
            "To: <$to>\r\n" .
            "Subject: $subject\r\n" .
            "Date: " . date('r') . "\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n";

        // SMTP dot-stuffing: any line that starts with a literal "." must
        // have a second "." prepended, or the server will treat it as the
        // end-of-message marker and truncate the body there.
        $normalizedBody = str_replace("\r\n", "\n", $body);
        $normalizedBody = str_replace("\n", "\r\n", $normalizedBody);
        $stuffedBody = preg_replace('/^\./m', '..', $normalizedBody);

        $message = $headers . "\r\n" . $stuffedBody . "\r\n.";
        fwrite($socket, $message . "\r\n");
        $log[] = 'C: [message body, ' . strlen($message) . ' bytes]';

        $resp = $read();
        if (!$expect($resp, [250])) return [false, "Message rejected after DATA. Log:\n" . implode("\n", $log)];

        // --- QUIT ---
        $write('QUIT');
        $read();
        fclose($socket);

        return [true, "Sent OK. Log:\n" . implode("\n", $log)];

    } catch (Throwable $e) {
        if ($socket) @fclose($socket);
        return [false, 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Local helper so this file has no hard dependency on mailer.php's
 * site_base_url() — falls back gracefully if called outside a web request
 * (e.g. from a CLI test script).
 */
function site_base_url_safe(): string {
    if (function_exists('site_base_url')) {
        return site_base_url();
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return 'http://' . $host;
}
