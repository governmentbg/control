#!/usr/bin/env php
<?php

declare(strict_types=1);

if (is_file(__DIR__ . '/../.version.pub.key') || is_file(__DIR__ . '/../.version.priv.key')) {
    echo 'Keys already exist' . "\r\n";
    exit(1);
}

$keypair   = sodium_crypto_sign_keypair();
file_put_contents(__DIR__ . '/../.version.pub.key', sodium_crypto_sign_publickey($keypair));
file_put_contents(__DIR__ . '/../.version.priv.key', sodium_crypto_sign_secretkey($keypair));
echo 'DONE' . "\r\n";
exit(0);
