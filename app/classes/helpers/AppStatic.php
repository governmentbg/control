<?php

declare(strict_types=1);

namespace helpers;

use helpers\events\Dispatcher;
use helpers\events\EventInterface;
use League\Plates\Engine;
use Monolog\Logger;
use vakata\cache\CacheInterface;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\di\DIContainer;
use vakata\files\FileStorageInterface;
use vakata\http\Request;
use vakata\http\Response;
use vakata\http\Uri;
use vakata\intl\Intl;
use vakata\mail\driver\SenderInterface;
use vakata\session\Session;
use vakata\user\UserManagementInterface;

class AppStatic
{
    protected static App $instance;

    public static function init(array $options): void
    {
        static::$instance = new App($options);
    }
    public static function get(string $k, mixed $default = null): mixed
    {
        return static::$instance->get($k, $default);
    }
    public static function config(): Config
    {
        return static::$instance->config();
    }
    public static function db(bool $schema = true): DBInterface
    {
        return static::$instance->db($schema);
    }
    public static function cache(): CacheInterface
    {
        return static::$instance->cache();
    }
    public static function req(): Request
    {
        return static::$instance->req();
    }
    public static function url(): Uri
    {
        return static::$instance->url();
    }
    public static function files(): FileStorageInterface
    {
        return static::$instance->files();
    }
    public static function sess(): Session
    {
        return static::$instance->sess();
    }
    public static function migrations(): Migrations
    {
        return static::$instance->migrations();
    }
    public static function intl(): Intl
    {
        return static::$instance->intl();
    }
    public static function views(): Engine
    {
        return static::$instance->views();
    }
    public static function mail(): SenderInterface
    {
        return static::$instance->mail();
    }
    public static function users(): UserManagementInterface
    {
        return static::$instance->users();
    }
    public static function auth(): AuthManager
    {
        return static::$instance->auth();
    }
    public static function di(): DIContainer
    {
        return static::$instance->di();
    }
    public static function middleware(string $class): callable
    {
        return static::$instance->middleware($class);
    }
    public static function schema(bool $refresh = false): void
    {
        static::$instance->schema($refresh);
    }
    public static function log(): Logger
    {
        return static::$instance->log();
    }
    public static function run(array &$stack, Request $req): Response
    {
        return static::$instance->run($stack, $req);
    }
    public static function dispatcher(): Dispatcher
    {
        return static::$instance->dispatcher();
    }
    public function listen(string $event, callable $listener): Dispatcher
    {
        return static::$instance->listen($event, $listener);
    }
    public function dispatch(EventInterface $event, bool $lazy = false): Dispatcher
    {
        return static::$instance->dispatch($event, $lazy);
    }
}
