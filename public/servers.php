<?php

use helpers\AppStatic;
use vakata\database\DB;
use vakata\random\Generator;

require_once __DIR__ . '/../bootstrap.php';

$db = new DB(AppStatic::get('DATABASE'));

foreach ($db->all("SELECT server FROM servers WHERE key_sik = ''") as $server) {
    $db->query(
        "UPDATE servers SET key_sik = ?, key_setup = ?, key_real = ? WHERE server = ?",
        [
            Generator::string(64),
            Generator::string(64),
            Generator::string(64),
	    $server
        ]
    );
}
