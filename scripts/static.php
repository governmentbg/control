#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$args = [];
if (isset($argv) && isset($argv[1])) {
    $args[] = (int)$argv[1] === 1;
}
if (isset($argv) && isset($argv[2])) {
    $args[] = (int)$argv[2] === 1;
}
if (isset($argv) && isset($argv[3])) {
    $args[] = (int)$argv[3] === 1;
}
if (isset($argv) && isset($argv[4])) {
    $args[] = $argv[4];
}
if (isset($argv) && isset($argv[5])) {
    $args[] = $argv[5];
}

call_user_func_array('\helpers\Jobs::static', $args);

echo 'DONE' . "\r\n";
exit(0);
