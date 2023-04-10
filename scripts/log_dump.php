<?php

use helpers\AppStatic;
use vakata\database\DB;
use vakata\spreadsheet\writer\CSVWriter;

require_once __DIR__ . '/../bootstrap.php';

$db = new DB(AppStatic::get('DATABASE'));

$limit = 1000;
$offset = 0;
$writer = new CSVWriter(fopen(AppStatic::get('STORAGE_LOG') . '/APILog.csv', 'w'), [ 'excel' => true ]);
$writer->addRow([ 'id', 'created', 'type', 'udi', 'sik', 'mode', 'request', 'response', 'err', 'ip' ]);
while (true) {
    $data = $db->all('SELECT * FROM api_log ORDER BY id ASC LIMIT ' . $limit . ' OFFSET ' . $offset);

    if (!count($data)) {
        break;
    }
    foreach ($data as $item) {
        $item['request'] = json_encode(json_decode($item['request']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $item['response'] = json_encode(json_decode($item['response']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $writer->addRow($item);
    }

    $offset += $limit;
}
$writer->close();
echo "DONE\n";