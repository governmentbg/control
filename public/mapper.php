<?php

use vakata\database\DB;

require_once __DIR__ . '/../bootstrap.php';

define('AUTH_TOKEN', '123');

try {
    if (!isset($_GET['token']) || $_GET['token'] !== AUTH_TOKEN) {
        throw new RuntimeException('Invalid token', 403);
    }
    $db = new DB(\helpers\AppStatic::get('DATABASE'));
    $sql = "SELECT
            siks.num,
            (
                SELECT 
                    servers.inner_host
                FROM
                    streams,
                    servers
                WHERE
                    streams.sik = siks.sik AND
                    servers.server = streams.server AND
                    streams.ended IS NULL
                ORDER BY
                    created DESC
                LIMIT 1
            ) server
        FROM siks ";
    $par = [];
    if (isset($_GET['sik'])) {
        $sql .= 'WHERE siks.num = ?';
        $par[] = $_GET['sik'];
    } elseif (isset($_GET['rik'])) {
        $sql .= 'WHERE siks.mik = ?';
        $par[] = $_GET['rik'];
    }
    header('Content-Type: text/plain; charset=utf-8');
    foreach ($db->all($sql, $par) as $row) {
        echo implode(',', $row) . "\n";
    }
} catch (RuntimeException $e) {
    header(
        'Content-Type: text/plain; charset=utf-8',
        true,
        $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
    );
    echo $e->getMessage();
} catch (Throwable $e) {
    header(
        'Content-Type: text/plain; charset=utf-8',
        true,
        500
    );
    echo 'Internal server error';
    echo $e->getMessage();
}