<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config.php';

require APP_ROOT . '/includes/db.php';
require APP_ROOT . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

set_exception_handler(function (Throwable $e) {
    error_log('[shoplab] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>系统繁忙</title>'
        . '<body style="font-family:sans-serif;padding:60px;text-align:center">'
        . '<h1>系统繁忙</h1><p>请稍后重试</p><p><a href="/index.php">返回首页</a></p></body>';
});
