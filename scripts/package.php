#!/usr/bin/env php
<?php

/**
 * This script is used by the developers to mark a new version.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

set_time_limit(0);

$bdir = App::get('BASEDIR');

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
                $new = $dir . str_replace('\\', '/', substr($name, strlen($path)));
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
