#!/usr/bin/env php
<?php

/**
 * This script downloads all needed dev tools as phar archives.
 */

declare(strict_types=1);

$tools = [
    'phpunit.phar' => [
        'https://phar.phpunit.de/phpunit-10.0.7.phar',
        '7cf5b28c3ce859c923f4297b330ab263'
    ],
    'phpstan.phar' => [
        'https://github.com/phpstan/phpstan/releases/download/1.9.17/phpstan.phar',
        '7cdd3fd98dadadce318d3b277e3ecc3f'
    ],
    'phpcs.phar'   => [
        'https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.7.1/phpcs.phar',
        'e61fbf31ce6db6b1e08f5f8a8ac94dcb'
    ],
    'phpcbf.phar'  => [
        'https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.7.1/phpcbf.phar',
        '06cb98810608bced771b237ce9eb4d90'
    ],
    'composer.phar' => [
        'https://getcomposer.org/download/2.5.4/composer.phar',
        '004e4062f9b7d244d525839a1cdff3ff'
    ],
    'psalm.phar' => [
        'https://github.com/vimeo/psalm/releases/download/5.6.0/psalm.phar',
        '2c27387a3de213d539f37fdf7b4ed418'
    ],
    'phan.phar' => [
        'https://github.com/phan/phan/releases/download/5.4.1/phan.phar',
        '4ac744d5ab5ddfb4ab1cf6e62a15efb3'
    ],
    'phpmd.phar' => [
        'https://github.com/phpmd/phpmd/releases/download/2.13.0/phpmd.phar',
        '03d55c64e575663a51fba8e0462cdb69'
    ]
];
foreach ($tools as $name => $data) {
    $file = __DIR__ . '/../tools/' . basename($name);
    if (file_exists($file) && md5_file($file) === $data[1]) {
        continue;
    }
    $temp = file_get_contents($data[0]);
    if ($temp !== false) {
        if (md5($temp) === $data[1]) {
            @file_put_contents($file, $temp);
        } else {
            echo "MD5 mismatch: " . basename($name) . " > " . md5($temp) . "\n";
        }
    }
}
