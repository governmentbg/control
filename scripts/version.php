#!/usr/bin/env php
<?php

/**
 * This script is used by the developers to mark a new version.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

set_time_limit(0);

if (!isset($argv[1])) {
    echo 'Please supply a version number!' . "\r\n";
    exit(1);
}
if (!isset($argv[2])) {
    echo 'Please supply THE password!' . "\r\n";
    exit(1);
}
if (!preg_match('(^\d+\.\d+\.\d+$)', $argv[1])) {
    echo 'Please supply a valid version!' . "\r\n";
    exit(1);
}

$bdir = App::get('BASEDIR');

if (is_file($bdir . '/.version')) {
    $temp = file_get_contents($bdir . '/.version') ?: throw new \RuntimeException();
    $temp = explode(':', $temp)[0];
    if (preg_match('(^\d+\.\d+\.\d+$)', $temp)) {
        list($major, $minor, $patch) = explode('.', $temp, 3);
        list($new_major, $new_minor, $new_patch) = explode('.', $argv[1], 3);
        if (
            $new_major < $major ||
            ($new_major === $major && $new_minor < $minor) ||
            ($new_major === $major && $new_minor === $minor && $new_patch <= $patch)
        ) {
            echo 'Please supply a higher version!' . "\r\n";
            exit(1);
        }
    }
}

file_put_contents(
    $bdir . '/bootstrap.php',
    preg_replace(
        "('VERSION' => '\d+\.\d+\.\d+\')",
        "'VERSION' => '" . $argv[1] . "'",
        file_get_contents($bdir . '/bootstrap.php') ?: throw new \RuntimeException()
    )
);

$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc') ?: throw new \RuntimeException());
$payload = [
    'files' => (new \modules\administration\settings\SettingsService(App::db(), App::config()))->listFiles(),
    'created' => date('Y-m-d H:i:s'),
    'user' => ''
];

file_put_contents(
    $bdir . '/.version',
    implode(':', [
        $argv[1],
        base64_encode($iv),
        openssl_encrypt(json_encode($payload, JSON_THROW_ON_ERROR), 'aes-256-cbc', md5($argv[2]), 0, $iv)
    ])
);

if (is_file($bdir . '/.version.priv.key')) {
    $file = [];
    $dirs = ['app', 'public', 'scripts', 'vendor', 'storage/intl', 'storage/database'];
    foreach ($dirs as $dir) {
        $path = realpath($bdir . '/' . $dir) ?: throw new \RuntimeException();
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
            )
        );
        foreach ($files as $name => $object) {
            if ($object->isFile()) {
                $new = $bdir . str_replace('\\', '/', substr($name, strlen($path)));
                if (strpos($new, 'storage/database/') !== false && basename($new) === 'status') {
                    continue;
                }
                $file[$new] = $name;
            }
        }
    }
    $items = ['composer.json', 'composer.lock', 'bootstrap.php', '.version.pub.key', '.version', '.version.php'];
    foreach ($items as $name) {
        if (is_file($bdir . '/' . $name)) {
            $file[$name] = realpath($bdir . '/' . $name);
        }
    }

    $zip = new ZipArchive();
    if ($zip->open($bdir . '/.version.dat', ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        echo 'Cannot create ZIP' . "\r\n";
        exit(1);
    }
    foreach ($file as $name => $path) {
        $zip->addFile($path, $name);
    }
    $zip->close();
    $hash = md5_file($bdir . '/.version.dat') ?: throw new \RuntimeException();
    $signature = sodium_crypto_sign_detached(
        $hash,
        file_get_contents($bdir . '/.version.priv.key') ?: throw new \RuntimeException()
    );
    file_put_contents($bdir . '/.version.dat', "\n" . base64_encode($signature), FILE_APPEND);
}

echo 'OK' . "\r\n";
exit(0);
