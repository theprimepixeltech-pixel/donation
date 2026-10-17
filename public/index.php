<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/content.php';
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$page = $path === '' || $path === 'index.php' ? 'home' : $path;
if (!isset($pages[$page])) { http_response_code(404); $page = '404'; $pages[$page] = ['Page not found','The page you requested is unavailable.']; }
[$title, $description] = $pages[$page];
require dirname(__DIR__) . '/app/views/header.php';
require dirname(__DIR__) . '/app/views/page.php';
require dirname(__DIR__) . '/app/views/footer.php';
