<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Only serve public assets directly; all page routes use the front controller.
if (is_string($path) && preg_match('~^/assets/[a-zA-Z0-9/_ .-]+$~', $path) && is_file(__DIR__ . $path)) return false;
require __DIR__ . '/index.php';
