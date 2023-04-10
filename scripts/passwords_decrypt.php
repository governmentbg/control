#!/usr/bin/env php
<?php

/**
 * This script is used to encrypt all passwords (in case encryption is added later)
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

try {
    \helpers\Jobs::decrypt();
    echo 'DONE';
    exit(0);
} catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
