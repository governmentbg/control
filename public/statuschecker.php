<?php

require_once __DIR__ . '/../bootstrap.php';

use vakata\mail\driver\SMTPSender;
use helpers\AppStatic as App;

if (!App::get('CLI') && App::get('STATUS_CHECKER_USER') && App::get('STATUS_CHECKER_PASS')) {
    if (!isset($_SERVER['HTTP_AUTHORIZATION']) || !$_SERVER['HTTP_AUTHORIZATION']) {
        header('WWW-Authenticate: Basic realm="_status"');
        header('HTTP/1.0 401 Unauthorized', true, 401);
        die();
    }
    list($type, $auth) = explode(' ', $_SERVER['HTTP_AUTHORIZATION'], 2);
    list($user, $pass) = explode(':', base64_decode($auth), 2);
    if ($user !== App::get('STATUS_CHECKER_USER') || $pass !== App::get('STATUS_CHECKER_PASS')) {
        header('WWW-Authenticate: Basic realm="_status"');
        header('HTTP/1.0 401 Unauthorized', true, 401);
        die();
    }
}

if (!App::get('CLI')) {
    header('Content-Type: text/plain; charset=utf-8');
}
$meta = isset($_GET['meta']) || (App::get('CLI') && isset($argv[1]));
$glob = isset($_GET['glob']);
$fail = 0;

ob_start();
foreach (
    [
        'STORAGE_UPLOADS',
        'STORAGE_CACHE',
        'STORAGE_SESSION',
        'STORAGE_LOG',
        'STORAGE_TMP',
        'STORAGE_INTL',
        'STORAGE_DATABASE',
        'STORAGE_MAIL',
        'STORAGE_REQ',
        'STORAGE_CERTIFICATES',
        'STORAGE_KEYS'
    ] as $const
) {
    if ($dir = App::get($const)) {
        echo $const . ': ';
        if ($const === 'STORAGE_SESSION' && ($dir === 'DATABASE' || $dir === 'CACHE')) {
            echo 'OK';
        } elseif ($const === 'STORAGE_REQ' && $dir === 'DATABASE') {
            echo 'OK';
        } elseif ($const === 'STORAGE_MAIL' && $dir === 'DATABASE') {
            echo 'OK';
        } elseif ($const === 'STORAGE_CACHE' && App::get('CACHE') !== 'file') {
            echo 'OK';
        } elseif ($const === 'STORAGE_UPLOADS' && $dir === 'DATABASE') {
            echo 'OK';
        } elseif (is_dir($dir) && is_writeable($dir)) {
            echo 'OK';
        } else {
            $fail++;
            echo 'FAIL';
        }
        if ($meta) {
            echo "\r\n" . $dir;
        }
        echo "\r\n\r\n";
    }
}

if (App::get('DATABASE')) {
    echo 'DATABASE: ';
    try {
        $dbc = App::db();
        $dbc->one("SELECT 1 FROM users WHERE usr = ?", [1]);
        if (strpos(App::get('DATABASE'), 'mysql') === 0) {
            $mysqlVer = $dbc->one("SELECT VERSION()");
        }
        echo 'OK';
    } catch (\Exception $e) {
        $fail++;
        echo 'FAIL';
    }
    echo "\r\n\r\n";
}

if (App::get('SMTPCONNECTION')) {
    echo 'SMTP CONNECTION: ';
    try {
        $mailer = new SMTPSender(App::get('SMTPCONNECTION'));
        $mailer->connect();
        echo 'OK';
    } catch (\Exception $e) {
        $fail++;
        echo 'FAIL';
    }
    echo "\r\n\r\n";
}

// VERSIONS
$phpVer = phpversion();
$parts = explode('.', (string)$phpVer);
echo 'PHP VERSION: ';
if ((int)$parts[0] > 7 || (int)$parts[1] >= 1) {
    echo 'OK';
} else {
    $fail++;
    echo 'FAIL';
}
if ($meta) {
    echo "\r\n" . $phpVer;
}
echo "\r\n\r\n";

$phpExt = get_loaded_extensions();

echo 'PHP IMAGE EDITING: ';
if (in_array('gd', $phpExt) || in_array('imagick', $phpExt)) {
    echo 'OK';
} else {
    $fail++;
    echo 'FAIL';
}
if ($meta) {
    echo "\r\n" . (in_array('gd', $phpExt) ? "GD " : "") . (in_array('imagick', $phpExt) ? "IMAGICK" : "");
}
echo "\r\n\r\n";

foreach (['zip', 'SimpleXML', 'mbstring', 'iconv', 'openssl', 'sockets'] as $ext) {
    echo 'PHP EXT ' . strtoupper($ext) . ': ';
    if (in_array($ext, $phpExt)) {
        echo 'OK';
    } else {
        $fail++;
        echo 'FAIL';
    }
    echo "\r\n\r\n";
}

if (isset($mysqlVer)) {
    $parts = explode('.', $mysqlVer);
    echo 'MYSQL VERSION: ';
    if ((int)$parts[0] > 5 || (int)$parts[1] >= 7) {
        echo 'OK';
    } else {
        $fail++;
        echo 'FAIL';
    }
    if ($meta) {
        echo "\r\n" . $mysqlVer;
    }
    echo "\r\n\r\n";
}

if (isset($_SERVER["SERVER_SOFTWARE"]) && strpos(strtolower($_SERVER["SERVER_SOFTWARE"]), 'apache/') === 0) {
    list($name, $version) = explode('/', $_SERVER["SERVER_SOFTWARE"], 2);
    $parts = explode('.', $version);
    echo 'APACHE VERSION: ';
    if ((int)$parts[0] > 2 || (int)$parts[1] >= 4) {
        echo 'OK';
    } else {
        $fail++;
        echo 'FAIL';
    }
    if ($meta) {
        echo "\r\n" . $version;
    }
    echo "\r\n\r\n";
}

echo 'CRYPTO & HASHING: ';
if (in_array('aes-256-gcm', openssl_get_cipher_methods()) && in_array('sha256', hash_algos())) {
    echo 'OK';
} else {
    $fail++;
    echo 'FAIL';
}
echo "\r\n\r\n";

if (!App::get('CLI')) {
    echo 'APACHE MOD_REWRITE: ';
    if (isset($_SERVER['APACHE_MOD_REWRITE']) && (int)$_SERVER['APACHE_MOD_REWRITE']) {
        echo 'OK';
    } else {
        $fail++;
        echo 'FAIL';
    };
    echo "\r\n\r\n";
}

$details = ob_get_contents();
ob_end_clean();

if (!$fail) {
    echo "ALL CHECKS PASSED";
} else {
    echo $fail . " CHECKS FAILED";
}
if (!$glob) {
    echo "\r\n\r\n";
    echo "\r\n\r\n";
    echo $details;
}
