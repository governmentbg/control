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

    if (!isset($data['url']) || !$data['url']) {
        throw new RuntimeException('Invalid url', 400);
    }
    $url = $data['url'];

    $db = new DB(AppStatic::get('DATABASE'));
    $insert = [
        'created' => time(),
        'src' => 'live',
        'udi' => null,
        'sik' => null,
        'mode' => null,
        'url' => $url
    ];
    if (!preg_match('((test-setup|test-sik|real))', $url, $match)) {
        throw new RuntimeException('Mode not found');
    }
    $insert['mode'] = $db->one("SELECT mode FROM modes WHERE name = ?", $match[1]);
    if (!$insert['mode']) {
        throw new RuntimeException('Invalid mode');
    }
    if ($match[0] === 'test-setup') {
        if (!preg_match('(\D(\d{6})\D)', $url, $match)) {
            throw new RuntimeException('UDI not found');
        }
        $insert['udi'] = $match[1];
        if (!$db->one('SELECT 1 FROM devices WHERE udi = ?', $insert['udi'])) {
            throw new RuntimeException('Invalid UDI');
        }
    } else {
        if (!preg_match('(\D(\d{9})\D)', $url, $match)) {
            throw new RuntimeException('SIK not found');
        }
        $insert['sik'] = $match[1];
        $insert['sik'] = $db->one(
            'SELECT sik FROM siks
             WHERE num = ? AND EXISTS (SELECT 1 FROM elections WHERE election = siks.election AND enabled = 1)',
            $insert['sik']
        );
        if (!$insert['sik']) {
            throw new RuntimeException('Invalid SIK');
        }
    }
    if (preg_match('(\D(16\d{8}))', $url, $match)) {
        $insert['created'] = (int)$match[1];
    }

    $db->query(
        'INSERT INTO events (created, type, url, stream) VALUES (?, ?, ?, ?)',
        [ date('Y-m-d H:i:s'), 'recording', $insert['url'], null ]
    );
    $insert['created'] = date('Y-m-d H:i:s', $insert['created']);
    $db->query(
        'INSERT INTO recordings (created, src, udi, sik, mode, url) VALUES (?, ?, ?, ?, ?, ?)',
        [ $insert['created'], $insert['src'], $insert['udi'], $insert['sik'], $insert['mode'], $insert['url'] ]
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
    echo json_encode([ 'error' => 'Internal Server Error' . $e->getMessage() ]);
}
