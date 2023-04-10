#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

set_time_limit(0);

$dir = App::get('BASEDIR');

$zip = new \ZipArchive();
$zip->open($dir . '/.version.bkp');
$zip->extractTo($dir . '/');
$zip->close();

if (is_file($dir . '/.version.db')) {
    $migrations = App::migrations();
    $migrations->to(array_filter(explode("\n", (file_get_contents($dir . '/.version.db') ?: ''))));
    @unlink($dir . '/.version.db');
    App::schema(true);
}

echo 'DONE' . "\r\n";
exit(0);
