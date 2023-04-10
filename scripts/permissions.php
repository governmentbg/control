#!/usr/bin/env php
<?php

/**
 * This script makes sure all STORAGE dirs in the config are writeable. It also sets the +x flag on all scripts in the
 * current directory.
 *
 * The permissions are usually set by the installer or manually when deploying, so it is NOT needed to add a cronjob.
 * The script exists just in case - to assist in reapplying permissions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

\helpers\Jobs::permissions();

echo 'DONE' . "\r\n";
exit(0);
