#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

$file = App::get('BASEDIR') . '/' . basename(isset($argv[1]) ? $argv[1] : '.env');
if (is_file($file)) {
    file_put_contents(
        $file,
        'ENVCACHE = true' . "\n" . preg_replace(
            '(ENVCACHE.*?\n)',
            '',
            file_get_contents($file) ?: throw new \RuntimeException()
        )
    );
    if (App::get('DEBUG')) {
        file_put_contents(
            $file,
            'DEBUG = false' . "\n" . preg_replace(
                '(DEBUG.*?\n)',
                '',
                file_get_contents($file) ?: throw new \RuntimeException()
            )
        );
    }
    file_put_contents($file, "\n", FILE_APPEND);
    if (!App::get('STATUS_CHECKER_USER') || !App::get('STATUS_CHECKER_PASS')) {
        file_put_contents(
            $file,
            preg_replace('(STATUS_CHECKER_USER.*?\n)', '', file_get_contents($file) ?: throw new \RuntimeException())
        );
        file_put_contents(
            $file,
            preg_replace('(STATUS_CHECKER_PASS.*?\n)', '', file_get_contents($file) ?: throw new \RuntimeException())
        );
        file_put_contents(
            $file,
            'STATUS_CHECKER_USER = "' . \vakata\random\Generator::string(10) . '"' . "\n" .
            'STATUS_CHECKER_PASS = "' . \vakata\random\Generator::string(32) . '"' . "\n",
            FILE_APPEND
        );
    }
    if (readline("Rotate token keys? (y/N): ") === "y") {
        file_put_contents($file, preg_replace(
            '(ENCRYPTIONKEY.*?\n)',
            '',
            file_get_contents($file) ?: throw new \RuntimeException()
        ));
        file_put_contents($file, preg_replace(
            '(SIGNATUREKEY.*?\n)',
            '',
            file_get_contents($file) ?: throw new \RuntimeException()
        ));
        file_put_contents(
            $file,
            'SIGNATUREKEY = "' . \vakata\random\Generator::string(32) . '"' . "\n" .
            'ENCRYPTIONKEY = "' . \vakata\random\Generator::string(32) . '"' . "\n",
            FILE_APPEND
        );
    }
    if (App::get('STORAGE_REQ') !== '' && readline("Disable request/response storage? (y/N): ") === "y") {
        file_put_contents($file, preg_replace(
            '(STORAGE_REQ.*?\n)',
            '',
            file_get_contents($file) ?: throw new \RuntimeException()
        ));
        file_put_contents(
            $file,
            'STORAGE_REQ = ""' . "\n",
            FILE_APPEND
        );
    }
    if (!is_file(App::get('STORAGE_DATABASE') . '/status')) {
        file_put_contents(App::get('STORAGE_DATABASE') . '/status', '0');
    }
    \helpers\Jobs::permissions();
    \helpers\Jobs::encrypt();
    \helpers\Jobs::env();
    \helpers\Jobs::langs();
}

$pkey = App::get('BASEDIR') . '/.version.pub.key';
if (is_file($pkey)) {
    @chmod($pkey, 0440);
}
$priv = App::get('BASEDIR') . '/.version.priv.key';
if (is_file($priv)) {
    if (readline("Delete update private key? (y/N): ") === "y") {
        @unlink($priv);
    }
}
