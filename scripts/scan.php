<?php

use helpers\AppStatic;
use vakata\database\DB;

require_once __DIR__ . '/../bootstrap.php';

$path = $argv[1];
$prefix = $argv[2] ?? '/';
$db = new DB(AppStatic::get('DATABASE'));

try {
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(
            realpath($path),
            \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
        ),
        \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($files as $k => $object) {
        if ($object->isFile()) {
            $process(rtrim($prefix, '/') . '/' . ltrim($name . str_replace('\\', '/', substr($k, strlen($path))), '/'));
        }
    }

    $process = function ($url) use ($db)
    {
        $insert = [
            'created' => time(),
            'src' => 'device',
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
        if (preg_match('(\D(2023\d\d\d\d_\d\d\d\d\d\d))', $url, $match)) {
            $insert['created'] = strtotime(str_replace('_', '', $match[1]));
        }

        // $db->query(
        //     'INSERT INTO events (created, type, url, stream) VALUES (?, ?, ?, ?)',
        //     [ date('Y-m-d H:i:s'), 'recording', $insert['url'], null ]
        // );
        $insert['created'] = date('Y-m-d H:i:s', $insert['created']);
        $db->query(
            'INSERT INTO recordings (created, src, udi, sik, mode, url) VALUES (?, ?, ?, ?, ?, ?)',
            [ $insert['created'], $insert['src'], $insert['udi'], $insert['sik'], $insert['mode'], $insert['url'] ]
        );
    };
} catch (Throwable $e) {
    echo $e->getMessage();
}
