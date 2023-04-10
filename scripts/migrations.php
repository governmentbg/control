#!/usr/bin/env php
<?php

/**
 * This script applies all database migrations by checking the STORAGE_DATABASE folder for files higher than the number
 * contained in the STORAGE_DATABASE/status file.
 *
 * THIS SCRIPT SHOULD ONLY BE RUN MANUALLY! AND ONLY IN DEVELOPMENT MODE! NO CRONJOB NECESSARY!
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

set_time_limit(0);

if (!App::get('STORAGE_DATABASE')) {
    echo 'Database migrations storage not found!' . "\r\n";
    exit(1);
}

$migrations = App::migrations();

switch ($argv[1] ?? '') {
    case 'up':
        $migrations->up();
        break;
    case 'down':
        $migrations->down();
        break;
    case 'reset':
        $migrations->reset();
        break;
    case 'install':
        $migrations->reset();
        $migrations->up();
        break;
}

// update schema cache
App::schema(true);

echo 'DONE' . "\r\n";
exit(0);
