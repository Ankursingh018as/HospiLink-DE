<?php
/**
 * HospiLink Background Mailer CLI
 * Runs in the background to send emails without blocking the main web request.
 * Usage: php background_mailer.php <json_file_path>
 */

// Disable time limit to ensure it finishes
set_time_limit(0);
ignore_user_abort(true);

if ($argc < 2) {
    error_log("[Background Mailer] Error: Missing JSON file path");
    exit(1);
}

$jsonPath = $argv[1];
if (!file_exists($jsonPath)) {
    error_log("[Background Mailer] Error: File not found: $jsonPath");
    exit(1);
}

$data = json_decode(file_get_contents($jsonPath), true);
if (!$data || empty($data['to']) || empty($data['subject']) || empty($data['html'])) {
    error_log("[Background Mailer] Error: Invalid JSON structure in $jsonPath");
    @unlink($jsonPath);
    exit(1);
}

// Re-use SMTP sending logic from hospi_notify
require_once __DIR__ . '/env_loader.php';

$to           = $data['to'];
$subject      = $data['subject'];
$htmlBody     = $data['html'];

$smtpHost     = env('SMTP_HOST', 'smtp.gmail.com');
$smtpPort     = (int) env('SMTP_PORT', 587);
$smtpUser     = env('SMTP_USERNAME', '');
$smtpPass     = env('SMTP_PASSWORD', '');
$smtpFrom     = env('SMTP_FROM_EMAIL', $smtpUser);
$smtpFromName = env('SMTP_FROM_NAME', 'HospiLink');

if (empty($smtpUser) || empty($smtpPass)) {
    error_log('[Background Mailer] SMTP credentials not configured');
    @unlink($jsonPath);
    exit(1);
}

try {
    $conn = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
    if (!$conn) {
        error_log("[Background Mailer] SMTP connect failed: $errstr ($errno)");
        @unlink($jsonPath);
        exit(1);
    }
    stream_set_timeout($conn, 30);

    $read = function() use ($conn) {
        $r = '';
        while ($line = @fgets($conn, 515)) {
            $r .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $r;
    };
    $cmd = function($c) use ($conn, $read) {
        @fwrite($conn, $c . "\r\n");
        return $read();
    };

    $read(); // banner
    $cmd('EHLO ' . gethostname());
    $cmd('STARTTLS');

    $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
    if (!stream_socket_enable_crypto($conn, true, $crypto)) {
        error_log('[Background Mailer] TLS upgrade failed');
        fclose($conn);
        @unlink($jsonPath);
        exit(1);
    }

    $cmd('EHLO ' . gethostname());
    $cmd('AUTH LOGIN');
    @fwrite($conn, base64_encode($smtpUser) . "\r\n"); $read();
    @fwrite($conn, base64_encode($smtpPass) . "\r\n"); $read();

    // Support multiple recipients in a single SMTP session (comma separated)
    $emails = is_array($to) ? $to : array_filter(array_map('trim', explode(',', $to)));
    $emails = array_unique(array_filter($emails));

    foreach ($emails as $email) {
        $cmd("MAIL FROM: <$smtpFrom>");
        $cmd("RCPT TO: <$email>");
        $cmd('DATA');

        $headers  = "From: $smtpFromName <$smtpFrom>\r\n";
        $headers .= "To: $email\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $headers .= "X-Mailer: HospiLink Background Mailer\r\n";

        @fwrite($conn, $headers . "\r\n" . quoted_printable_encode($htmlBody) . "\r\n.\r\n");
        $read();
    }

    $cmd('QUIT');
    fclose($conn);
    error_log("[Background Mailer] [SUCCESS] Successfully sent email batch to: " . implode(', ', $emails));

} catch (Exception $e) {
    error_log('[Background Mailer] Exception: ' . $e->getMessage());
}

// Clean up JSON file
@unlink($jsonPath);
exit(0);
