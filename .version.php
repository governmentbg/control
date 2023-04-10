<?php

use helpers\AppStatic as App;

if (!($old = App::get('VERSION'))) {
    return;
}

$new = @json_decode(file_get_contents(__DIR__ . '/composer.json'), true)['version'] ?? '0.0.0';

// $dbc, $cache are available
