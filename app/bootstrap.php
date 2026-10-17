<?php
declare(strict_types=1);
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
$config = require __DIR__ . '/config/site.php';
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$notice = ''; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403); $errors[] = 'Your session expired. Refresh the page and try again.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        if (strlen($name) < 2 || strlen($name) > 120) $errors[] = 'Enter a name between 2 and 120 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $errors[] = 'Enter a valid email address.';
        $message = trim((string)($_POST['message'] ?? ''));
        if ($message === '' || strlen($message) > 3000) $errors[] = 'Enter a message of up to 3000 characters.';
        if (empty($_POST['consent'])) $errors[] = 'Please acknowledge the demo notice.';
        if (!$errors) $notice = 'Demo validation complete. Your information has not been submitted or saved. Connect the application backend to receive enquiries.';
    }
}
