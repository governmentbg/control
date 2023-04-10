<?php

declare(strict_types=1);

// composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use helpers\AppStatic as App;
use vakata\config\Config;

App::init(
    array_merge(
        ((require __DIR__ . '/.env.php') ?? Config::parseEnvFile(__DIR__ . '/.env')),
        [
            'VERSION' => '3.8.0',
            'CONFIGFILE' => __DIR__ . '/.env'
        ]
    )
);

// timezone & locale
setlocale(LC_ALL, 'en_US.UTF-8');
date_default_timezone_set(App::get('TIMEZONE'));
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// ERROR HANDLING
error_reporting(E_ALL);
ini_set('log_errors', 'On');
ini_set('display_errors', ( App::get('DEBUG') ? 'On' : 'Off' ));
ini_set('display_start_up_errors', ( App::get('DEBUG') ? 'On' : 'Off' ));
ini_set('log_errors_max_len', '0');
ini_set('ignore_repeated_errors', '1');
ini_set('ignore_repeated_source', '0');
ini_set('track_errors', '0');
ini_set('html_errors', '0');
ini_set('report_memleaks', '1');
if (App::get('DEBUG')) {
    ini_set('opcache.enable', '0');
}
// create a default exception handler
set_exception_handler(function ($e) {
    @error_log(
        date("[d-M-Y H:i:s e] ") .
        'PHP Exception:' .
        ((int)$e->getCode() ? ' ' . $e->getCode() . ' -' : '') . ' ' . $e->getMessage() .
        ' in ' . $e->getFile() . ' on line ' . $e->getLine()
    );
    while (ob_get_level() && ob_end_clean()) {
    }
    if (App::get('CLI')) {
        if (App::get('DEBUG')) {
            echo $e->getMessage() . ' > ' . $e->getFile() . ':' . $e->getLine() . "\r\n";
            echo $e->getTraceAsString() . "\r\n";
        } else {
            echo 'Try again later' . "\r\n";
        }
        die();
    }
    if (!headers_sent()) {
        header(
            'Content-Type: text/html; charset=utf-8',
            true,
            $e->getCode() >= 200 && $e->getCode() <= 503 ? (int)$e->getCode() : 500
        );
    }
    echo '
    <!DOCTYPE html>
    <html>
        <head>
            <meta charset="UTF-8"><title>Please, try again later.</title>
            <style>body { background:#e0e0e0; min-width:320px; }
                h1 { font-size:1.4em; text-align:center; margin:2em 0 0 0; color:#8b0000; text-shadow:1px 1px 0 white; }
                p { font-size:1.2em; text-align:center; margin:2em 0 0 0; }
            </style>
        </head>
        <body>
            <h1>Please, try again later.</h1>' .
            (App::get('DEBUG') ?
                '<p>
                    <strong>' . htmlspecialchars($e->getMessage()) . '</strong><br />
                    <code>' . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . '</code>
                </p>
                <pre>' .
                htmlspecialchars(str_replace(': ', ": \n\t", $e->getTraceAsString())) .
                '</pre>' :
                ''
            ) .
        '</body>
    </html>';
    die();
});
// turn all errors into exceptions
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // do not touch errors where @ is used or that are not marked for reporting
    if ($errno === 0 || !($errno & error_reporting())) {
        return true;
    }
    // do not throw exceptions for "lightweight" errors - those will end up in the log and will not break execution
    if (in_array($errno, [ E_NOTICE, E_DEPRECATED, E_STRICT, E_USER_NOTICE, E_USER_DEPRECATED ])) {
        @error_log(
            date("[d-M-Y H:i:s e] ") .
            'PHP Notice: ' . $errno . ' ' . $errstr .
            ($errfile && $errline ? ' in ' . $errfile . ' on line ' . $errline : '')
        );
        return true;
        // return false;
    }
    // throw exception for all others
    throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
});

if (!App::get('CLI')) {
    // normalize REDIRECT_ vars
    foreach ($_SERVER as $k => $v) {
        if (substr($k, 0, 9) === 'REDIRECT_' && !isset($_SERVER[substr($k, 9)])) {
            $_SERVER[substr($k, 9)] = $v;
        }
    }

    // normalize cert number
    if (isset($_SERVER['SSL_CLIENT_M_SERIAL']) && is_string($_SERVER['SSL_CLIENT_M_SERIAL'])) {
        $_SERVER['SSL_CLIENT_M_SERIAL'] = strtoupper(ltrim($_SERVER['SSL_CLIENT_M_SERIAL'], '0'));
    }

    // normalize session
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.name', App::get('APPNAME_CLEAN') . '_SESSID');
    ini_set('session.gc_maxlifetime', App::get('SESSION_TIMEOUT'));
    ini_set('session.cookie_name', App::get('APPNAME_CLEAN') . '_SESSID');
    ini_set('session.cookie_path', App::url()->linkTo(''));
    ini_set('session.cookie_lifetime', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (
        isset($_SERVER['HTTPS']) &&
        !empty($_SERVER['HTTPS']) &&
        (!is_string($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) !== 'off')
    ) {
        ini_set('session.cookie_secure', '1');
    }
    // ini_set("session.entropy_file", "/dev/urandom");
    // ini_set("session.entropy_length", "32");
    // ini_set('session.hash_bits_per_character', 6);
    // if (!(int)ini_get('session.gc_probability') || !(int)ini_get('session.gc_divisor')) {
    //     ini_set('session.gc_probability', '1');
    //     ini_set('session.gc_divisor', '100');
    // }

    // remove revealing headers
    @header_remove('x-powered-by');
}
