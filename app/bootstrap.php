<?php
declare(strict_types=1);
// Public pages and account workflows use native PHP; PDF dependencies load on demand.
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/services.php';
$config = require __DIR__ . '/config/site.php';
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
if (isset($_SESSION['last_seen']) && time()-$_SESSION['last_seen']>7200) { $_SESSION=[]; session_regenerate_id(true); }
$_SESSION['last_seen']=time();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; form-action 'self' https://api.razorpay.com; frame-ancestors 'none'; base-uri 'self'");
function e(string|int|null $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function current_user(): array|false { return isset($_SESSION['user_id']) ? query('SELECT id,name,email,role FROM users WHERE id=?', [$_SESSION['user_id']])->fetch() : false; }
function redirect(string $path): never { header('Location: '.$path, true, 303); exit; }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function field(string $name, int $max=3000): string { $v=trim((string)($_POST[$name] ?? '')); if (mb_strlen($v)>$max) throw new DomainException('The '.str_replace('_',' ',$name).' field is too long.'); return $v; }
function require_user(bool $admin=false): array { $u=current_user(); if (!$u) redirect('/login'); if ($admin && $u['role']!=='admin') { http_response_code(403); exit('Access denied.'); } return $u; }
function flash(string $message): void { $_SESSION['notice']=$message; }
function money(int $paise): string { return '₹'.number_format($paise/100,2); }
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$notice=$_SESSION['notice'] ?? ''; unset($_SESSION['notice']); $errors=[];
$path=trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?? '', '/');
$page=($path==='' || $path==='index.php') ? 'home' : $path;
try {
 if ($page==='webhook') { process_webhook($config); exit; }
 if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($_SESSION['csrf'],(string)($_POST['csrf'] ?? ''))) { http_response_code(403); throw new DomainException('Your session expired. Refresh and try again.'); }
  require __DIR__.'/actions.php';
 }
 if (in_array($page,['member-login','donor-dashboard'],true)) { require_user(); }
 if ($page==='admin') require_user(true);
 if ($page==='download') { deliver_document(); exit; }
 if (in_array($page,['member-card','member-certificate','receipt'],true)) { render_pdf($page); exit; }
 if ($page==='export') { export_report(); exit; }
} catch (DomainException $ex) { $errors[]=$ex->getMessage(); }
catch (Throwable $ex) { error_log($ex->getMessage()); http_response_code(500); $errors[]='The request could not be completed. Please try again or contact the Foundation.'; }
