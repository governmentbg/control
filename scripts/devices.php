#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;
use vakata\random\Generator;

$db = App::db(false);

if (!isset($argv[1]) || !(int) $argv[1]) {
    echo 'Invalid device type' . "\r\n";
    exit(1);
}
if (!isset($argv[2]) || !(int) $argv[2]) {
    echo 'Invalid device count' . "\r\n";
    exit(1);
}

$device = (int) $argv[1];
$count = (int) $argv[2];

$max = (int) $db->one(
    'SELECT MAX(udi) FROM devices WHERE floor(udi / 100000) = ?',
    [ $device ]
);
$preparedInsert = $db->prepare('INSERT INTO devices (udi, install_key, registered) VALUES (?, ?, ?)');

for ($i = 1; $i <= $count; $i++) {
    $preparedInsert->execute([
        ($max === 0 ? (int) $device * 100000 : $max) + $i,
        Generator::string(14),
        null
    ]);
}

App::log()->addNotice('Добавени устройства', ['count' => $count, 'type' => $device]);

echo 'DONE';
