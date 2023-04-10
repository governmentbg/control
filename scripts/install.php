#!/usr/bin/env php
<?php

declare(strict_types=1);

if (!is_file(__DIR__ . '/../.env.php')) {
    file_put_contents(
        __DIR__ . '/../.env.php',
        '<?' . 'php return null;' . "\n"
    );
}

require_once __DIR__ . '/../vendor/autoload.php';

if (!is_file(__DIR__ . '/../.env')) {
    $name = basename(realpath(__DIR__ . '/../'));
    file_put_contents(
        __DIR__ . '/../.env',
        implode("\n", [
            'DEBUG = true',
            'ENVCACHE = false',
            'TIMEZONE = "Europe/Sofia"',
            'DATABASE = "mysql://root@127.0.0.1/' . $name . '"',
            'SIGNATUREKEY = "' . \vakata\random\Generator::string(32) . '"',
            'ENCRYPTIONKEY = "' . \vakata\random\Generator::string(32) . '"'
        ])
    );
}

require_once __DIR__ . '/../bootstrap.php';

\helpers\Jobs::permissions();
