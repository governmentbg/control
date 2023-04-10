#!/usr/bin/env php
<?php

/**
 * This script deletes all files older than 48 hours form the temp directory (defined as STORAGE_TMP in the config).
 *
 * If disk usage is an issue - execute once a day in a low proprity, for example:
 * 0 3 * * * cleantmp.php > /dev/null
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

if (!App::get('STORAGE_TMP')) {
    echo 'Directory not found!' . "\r\n";
    exit(1);
}

\helpers\Jobs::cleantmp();

echo 'DONE' . "\r\n";
exit(0);
