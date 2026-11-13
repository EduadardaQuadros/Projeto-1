<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

session_name('VOLEI_SESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require __DIR__ . '/helpers.php';
require __DIR__ . '/Auth.php';
require __DIR__ . '/regras.php';

set_exception_handler(static function (Throwable $e): void {
    error_log($e->__toString());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo 'Ocorreu um erro interno.';
});
