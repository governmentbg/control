<?php

use helpers\AppStatic;
use vakata\database\DB;

require_once __DIR__ . '/../bootstrap.php';

try {
    $data = file_get_contents('php://input');
    if (!strlen($data)) {
        throw new RuntimeException('Use POST', 400);
    }
    $data = @json_decode($data, true);
    if (!$data) {
        throw new RuntimeException('Use JSON', 400);
    }

    if (!isset($data['hostname']) || !$data['hostname']) {
        throw new RuntimeException('No hostname', 400);
    }

    $db = new DB(AppStatic::get('DATABASE'));

    $server = $db->one("SELECT server FROM servers WHERE inner_host = ?", $data['hostname']);
    $restreamer = $db->one("SELECT restreamer FROM restreamers WHERE inner_host = ?", $data['hostname']);

    if (!$server && !$restreamer) {
        throw new RuntimeException('Invalid hostname', 400);
    }

    $db->query(
        'INSERT INTO monitor (created, server, restreamer, data) VALUES (?, ?, ?, ?)',
        [ date('Y-m-d H:i:s'), $server, $restreamer, json_encode($data, JSON_UNESCAPED_UNICODE) ]
    );

    // if (isset($data['xml'])) {
    //     $data['xml'] = base64_decode($data['xml']);
    //     $data['xml'] = json_decode(json_encode(simplexml_load_string($data['xml'])), true);
    // }
    if ($server) {
        $db->query(
            "UPDATE servers SET monitor = ? WHERE server = ?",
            [ json_encode($data, JSON_UNESCAPED_UNICODE), $server ]
        );
    }
    if ($restreamer) {
        $db->query(
            "UPDATE restreamers SET monitor = ? WHERE restreamer = ?",
            [ json_encode($data, JSON_UNESCAPED_UNICODE), $restreamer ]
        );
    }
    header('Content-Type: application/json; charset=utf-8');
} catch (RuntimeException $e) {
    header(
        'Content-Type: application/json; charset=utf-8',
        true,
        $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
    );
    echo json_encode([ 'error' => $e->getMessage() ]);
} catch (Throwable $e) {
    header(
        'Content-Type: application/json; charset=utf-8',
        true,
        500
    );
    echo json_encode([ 'error' => 'Internal Server Error' ]);
}
