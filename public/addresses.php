<?php

require_once __DIR__ . '/../bootstrap.php';

use vakata\database\DB;

$db = new DB(\helpers\AppStatic::get('DATABASE'));

echo 'sik|address' . "\n";
foreach ($db->all("SELECT num, address FROM siks ORDER BY num") as $row) {
    echo 'СИК № '. $row['num'] . '|РИК '.substr($row['num'], 0, 2).', ' .  $row['address'] . "\n";
}
