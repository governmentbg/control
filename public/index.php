<?php

declare(strict_types=1);

// housekeeping and config
require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;
use vakata\http\Emitter;

$stack = array_filter([
    App::middleware(\middleware\Intl::class),
    App::middleware(\middleware\Logger::class),
    App::middleware(\middleware\Fixer::class),
    App::middleware(\middleware\ClientIP::class),
    App::middleware(\middleware\User::class),
    App::middleware(\middleware\Session::class),
    App::middleware(\middleware\UserDecorator::class),
    App::get('PUSH_NOTIFICATIONS') ? App::middleware(\middleware\PushNotifications::class) : null,
    App::get('FORCE_HTTPS') ? App::middleware(\middleware\HTTPS::class) : null,
    App::get('GZIP') ? App::middleware(\middleware\Gzip::class) : null,
    //App::middleware(\middleware\OWASP::class),
    App::get('CSP') ? App::middleware(\middleware\CSP::class) : null,
    App::get('FP') ? App::middleware(\middleware\FP::class) : null,
    App::get('RATELIMIT_REQUESTS') && App::get('RATELIMIT_SECONDS') ?
        App::middleware(\middleware\Ratelimit::class) :
        null,
    App::get('IDS') ? App::middleware(\middleware\IDS::class) : null,
    App::get('CSRF') ? App::middleware(\middleware\CSRF::class) : null,
    App::get('CORS') ? App::middleware(\middleware\CORS::class) : null,
    App::get('FORGOT_PASSWORD') ? App::middleware(\middleware\Restore::class) : null,
    App::get('REGISTER_PASSWORD') ? App::middleware(\middleware\Register::class) : null,
    App::get('BASIC_AUTH') ? App::middleware(\middleware\Basic::class) : null,
    App::middleware(\middleware\Auth::class),
    App::middleware(\middleware\TFA::class),
    App::get('MAINTENANCE') ? App::middleware(\middleware\Maintenance::class) : null,
    App::middleware(\middleware\Uploads::class),
    new \middleware\Core(
        App::db(true),
        App::users(),
        App::auth(),
        App::cache(),
        App::views(),
        App::di(),
        App::get('BASEDIR'),
        App::get('FORCE_TFA'),
        App::get('HELP')
    )
]);
$res = App::run($stack, App::req());
(new Emitter())->emit($res);
