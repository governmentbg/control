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
    $db = new DB(AppStatic::get('DATABASE'));

    if (!isset($data['type']) || !in_array($data['type'], [ 'started', 'stopped' ])) {
        throw new RuntimeException('Invalid type', 400);
    }
    if (
        !isset($data['mode']) ||
        !($mode = $db->one('SELECT mode, name FROM modes WHERE name = ?', [ $data['mode'] ]))
    ) {
        throw new RuntimeException('Invalid mode', 400);
    }
    if (
        !isset($data['host']) ||
        !(
            $server = $db->one('SELECT server FROM servers WHERE inner_host = ?', [ $data['host'] ])
        )
    ) {
        throw new RuntimeException('Invalid host', 400);
    }
    $election = $db->one('SELECT election FROM elections WHERE enabled = 1 ORDER BY election DESC LIMIT 1');
    if (!$election) {
        throw new RuntimeException('Invalid server configuration', 500);
    }
    if (!isset($data['name'])) {
        throw new RuntimeException('Invalid stream name', 400);
    }
    if ($mode['name'] === 'test-setup') {
        $field = 'udi';
        $value = $data['name'];
    } else {
        $field = 'sik';
        $value = $db->one('SELECT sik FROM siks WHERE num = ? AND election = ?', [ $data['name'], $election ]);
        if (!$value) {
            throw new RuntimeException('Invalid sik', 400);
        }
    }
    $stream = null;
    $streams = $db->all(
        'SELECT
            stream,
            url
        FROM
            streams
        WHERE
            server = ? AND
            mode = ? AND
            election = ? AND
            ' . $field . ' = ?
        ORDER BY
            stream DESC',
        [ $server, $mode['mode'], $election, $value ]
    );
    foreach ($streams as $item) {
        if (isset($data['token']) && strpos(strtolower($item['url']), strtolower($data['token'])) !== false) {
            $stream = $item;
            break;
        }
    }
    if (!$stream) {
        throw new RuntimeException('Missing stream', 400);
    }

    $time = date('Y-m-d H:i:s');
    $db->query(
        'INSERT INTO events (created, type, url, stream) VALUES (?, ?, ?, ?)',
        [ $time, $data['type'], $stream['url'], $stream['stream'] ]
    );
    $db->query(
        'UPDATE streams SET ' . ($data['type'] === 'started' ? 'started = ?' : 'ended = ?') . ' WHERE stream = ?',
        [ $time, $stream['stream'] ]
    );

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