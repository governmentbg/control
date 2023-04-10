#!/usr/bin/env php
<?php

/**
 * This script deletes all cached content. Usually cache expires by itself, this is for manual purge only - therefore
 * no cron job is necessary.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

$cache = App::cache();
\helpers\Jobs::cleancache();

echo 'DONE' . "\r\n";
exit(0);
