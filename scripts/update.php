#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

$bdir = App::get('BASEDIR');
$file = $argv[1] ?? '';

if (!is_file($bdir . '/.version.pub.key')) {
    echo 'Missing key' . "\r\n";
    exit(1);
}
if (!$file || !is_file($file) || !is_readable($file)) {
    echo 'Invalid file' . "\r\n";
    exit(1);
}

$migrations = App::migrations();

$data = file_get_contents($file) ?: throw new \RuntimeException();
$npos = strrpos($data, "\n") ?: throw new \RuntimeException();
$hash = substr($data, $npos + 1);
$data = substr($data, 0, $npos);
if (
    !sodium_crypto_sign_verify_detached(
        base64_decode($hash) ?: throw new \RuntimeException(),
        md5($data),
        file_get_contents($bdir . '/.version.pub.key') ?: throw new \RuntimeException()
    )
) {
    echo 'Invalid signature' . "\r\n";
    exit(1);
}
$zip = new \ZipArchive();
$zip->open($file);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $item = $zip->statIndex($i);
    if ($item === false) {
        continue;
    }
    if (is_file($bdir . '/' . $item['name']) || is_dir($bdir . '/' . $item['name'])) {
        if (!is_writable($bdir . '/' . $item['name'])) {
            echo 'Insufficient permissions for extract' . "\r\n";
            exit(1);
        }
    } else {
        $dir = dirname($bdir . '/' . $item['name']);
        while (!is_dir($dir)) {
            $dir = dirname($dir);
        }
        if ($dir === '.' || !is_writable($dir)) {
            echo 'Insufficient permissions for extract' . "\r\n";
            exit(1);
        }
    }
}

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

$bkp = new \ZipArchive();
if ($bkp->open($bdir . '/.version.bkp', \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
    return false;
}
foreach ($file as $name => $path) {
    $bkp->addFile($path, $name);
}
$bkp->addFromString('.version.db', implode("\n", array_filter($migrations->current())));
$bkp->close();

$zip->extractTo($bdir . '/');
$zip->close();

\helpers\Jobs::cleancache();
\helpers\Jobs::permissions();
\helpers\Jobs::langs();

$migrations->up();

\helpers\Jobs::schema();

if (is_file($bdir . '/.version.php')) {
    include $bdir . '/.version.php';
}
echo 'DONE';
