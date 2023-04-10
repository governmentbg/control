<?php

declare(strict_types=1);

namespace helpers;

use helpers\events\Dispatcher;
use helpers\events\Event;
use helpers\events\EventInterface;
use helpers\log\DBHandler;
use League\Plates\Engine;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use vakata\authentication\Credentials;
use vakata\cache\CacheInterface;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\DB;
use vakata\cache\Memcached;
use vakata\cache\Libmemcached;
use vakata\cache\Filecache;
use vakata\cache\Redis;
use vakata\collection\Collection;
use vakata\di\DIContainer;
use vakata\files\FileDatabase;
use vakata\files\FileDatabaseStorage;
use vakata\files\FileStorageInterface;
use vakata\http\Request;
use vakata\http\Response;
use vakata\http\Uri;
use vakata\intl\Intl;
use vakata\mail\driver\FileSender;
use vakata\mail\driver\MailSender;
use vakata\mail\driver\MultiSender;
use vakata\mail\driver\SenderInterface;
use vakata\mail\driver\SMTPSender;
use vakata\random\Generator;
use vakata\session\handler\SessionCache;
use vakata\session\handler\SessionDatabase;
use vakata\session\handler\SessionFile;
use vakata\session\Session;
use vakata\user\Provider;
use vakata\user\User;
use vakata\user\UserException;
use vakata\user\UserManagementDatabase;
use vakata\user\UserManagementInterface;

class App
{
    protected Config $config;
    protected DIContainer $container;

    public function __construct(array $options)
    {
        $dir = realpath(__DIR__ . '/../../../') ?: throw new \RuntimeException();
        $defaults = [
            'CLI'                   => php_sapi_name() === 'cli',
            'BASEDIR'               => $dir,
            'APPNAME'               => basename($dir),
            'APPNAME_CLEAN'         => strtoupper(preg_replace('([^a-z0-9_]+)', '_', basename($dir)) ?? ''),
            'TIMEZONE'              => 'Europe/Sofia',
            'DATABASE'              => 'postgre://postgres@127.0.0.1/' . basename($dir),
            'CACHE'                 => 'file',
            'DEBUG'                 => false,
            'MAINTENANCE'           => false,
            'GZIP'                  => false,
            'CORS'                  => false,
            'CSRF'                  => true,
            'CSP'                   => true,
            'FP'                    => true,
            'IDS'                   => '',
            'RATELIMIT_REQUESTS'    => 0,
            'RATELIMIT_SECONDS'     => 0,
            'FORCE_HTTPS'           => false,
            'FORCE_TFA'             => false,
            'TFA_REMEMBER'          => true,
            'BASIC_AUTH'            => false,
            'AUTOREGISTER'          => false,
            'CSRF_TIMEOUT'          => 7200,
            'SESSION_TIMEOUT'       => 1800,
            'SESSION_REGENERATE'    => 300,
            'SIGNATUREKEY'          => 'Place-a-random-signature-key-here',
            'ENCRYPTIONKEY'         => '12345678901234567890123456789012',
            'PASSWORDKEY'           => '',
            'STORAGE_UPLOADS'       => $dir . '/storage/uploads',
            'STORAGE_CACHE'         => $dir . '/storage/cache',
            'STORAGE_SESSION'       => $dir . '/storage/session',
            'STORAGE_LOG'           => $dir . '/storage/log',
            'STORAGE_TMP'           => $dir . '/storage/tmp',
            'STORAGE_INTL'          => $dir . '/storage/intl',
            'STORAGE_DATABASE'      => $dir . '/storage/database',
            'STORAGE_MAIL'          => $dir . '/storage/mail',
            'STORAGE_REQ'           => $dir . '/storage/req',
            'STORAGE_CERTIFICATES'  => $dir . '/storage/certificates',
            'STORAGE_KEYS'          => $dir . '/storage/keys',
            'PUSH_NOTIFICATIONS'    => false,
            'GROUP_ADMINS'          => '1',
            'GROUP_USERS'           => '2',
            'SMTPCONNECTION'        => '',
            'MAILQUEUE'             => false,
            'FORGOT_PASSWORD'       => 0,
            'REGISTER_PASSWORD'     => 0,
            'TRANSLATIONS'          => false,
            'MESSAGING'             => false,
            'HELP'                  => false,
            'FORUM'                 => false,
            'STATUS_CHECKER_USER'   => '',
            'STATUS_CHECKER_PASS'   => '',
            'LOGIN_URL'             => 'login',
            'PUBLIC_URL'            => '/',
            'MULTISITE'             => false,
            'CMS'                   => true,
            'LANGUAGES'             => 'bg',
            'SENDFILE'              => '',
            'MAX_IMAGE_SIZE'        => 0,
        ];
        $this->config = new Config($defaults);
        $this->config->fromArray($options);
        $this->container = new DIContainer();
        $this->container->register($this->container);
        $this->container->register(clone $this->config, [], [], true); // clone in order to prevent changes
    }
    public function get(string $k, mixed $default = null): mixed
    {
        return $this->config->get($k, $default);
    }
    public function config(): Config
    {
        return $this->container->instance(Config::class, [], true);
    }

    public function db(bool $schema = false): DBInterface
    {
        $dbc = $this->container->instance(DB::class, [], true) ?? new DB($this->get('DATABASE'));
        if (!$this->container->has(DB::class)) {
            $this->container->register($dbc, null, [], true);
        }
        if ($schema && !$dbc->hasSchema()) {
            $this->schema();
        }
        return $dbc;
    }
    public function schema(bool $refresh = false): void
    {
        $key = $this->get('APPNAME_CLEAN') . '_schema';
        $dbc = $this->db(false);
        $cache = $this->cache();
        if (!$refresh && $cached = $cache->get($key)) {
            $dbc->setSchema($cached);
        } else {
            $cache->prepare($key);
            $dbc->parseSchema();
            $cache->set(
                $key,
                serialize($dbc->getSchema(false)),
                null,
                $this->get('DEBUG') ? '+10 minutes' : '+1 year'
            );
        }
    }
    public function cache(): CacheInterface
    {
        if ($this->container->has(CacheInterface::class)) {
            return $this->container->instance(CacheInterface::class, [], true);
        }
        $key =  $this->get('APPNAME_CLEAN');
        $loc =  $this->get('STORAGE_CACHE');
        switch ($this->get('CACHE')) {
            case 'memcache':
            case 'memcached':
                $cache = extension_loaded('memcached') ?
                    new Libmemcached($loc, $key) :
                    new Memcached($loc, $key);
                $cache->enableNamespaceCache();
                break;
            case 'redis':
                $cache = new Redis($loc, $key);
                break;
            default:
                $dir = realpath($loc) ?:
                throw new \RuntimeException();
                $cache = new Filecache($dir, $key);
                break;
        }
        $this->container->register($cache, null, [], true);
        return $cache;
    }
    public function req(): Request
    {
        if ($this->container->has(Request::class)) {
            return $this->container->instance(Request::class, [], true);
        }
        $req = Request::fromGlobals();
        $this->container->register($req, null, [], true);
        return $req;
    }
    public function url(): Uri
    {
        if ($this->container->has(Uri::class)) {
            return $this->container->instance(Uri::class, [], true);
        }
        $url = $this->req()->getUrl();
        $this->container->register($url, null, [], true);
        return $url;
    }
    public function files(): FileStorageInterface
    {
        if ($this->container->has(FileStorageInterface::class)) {
            return $this->container->instance(FileStorageInterface::class, [], true);
        }
        $files = $this->get('STORAGE_UPLOADS') === 'DATABASE' ?
            new FileDatabase(
                realpath($this->get('STORAGE_TMP')) ?: throw new \RuntimeException(),
                $this->db(false),
                'uploads'
            ) :
            new FileDatabaseStorage(
                realpath($this->get('STORAGE_UPLOADS')) ?: throw new \RuntimeException(),
                $this->db(false),
                'uploads',
                false,
                date('Y/m/d'),
                realpath($this->get('STORAGE_TMP')) ?: sys_get_temp_dir()
            );
        $this->container->register($files, null, [], true);
        $this->container->register($files, 'file', [], true);
        return $files;
    }
    public function sess(): Session
    {
        if ($this->container->has(Session::class)) {
            return $this->container->instance(Session::class, [], true);
        }
        switch ($this->get('STORAGE_SESSION')) {
            case 'DATABASE':
                $sess = new SessionDatabase($this->db());
                break;
            case 'CACHE':
                $sess = new SessionCache($this->cache(), $this->get('APPNAME_CLEAN') . '_sess');
                break;
            case 'PHP':
                $sess = null;
                break;
            default:
                $dir = realpath($this->get('STORAGE_SESSION')) ?:
                throw new \RuntimeException();
                $sess = new SessionFile($dir, 'sess_');
                break;
        }
        $sess = new Session(
            (
                !$this->get('CLI') &&
                !preg_match('(token|basic|bearer|oauth)i', $this->req()->getHeaderLine('Authorization'))
            ),
            $sess
        );
        $this->container->register($sess, null, [], true);
        return $sess;
    }
    public function intl(): Intl
    {
        if ($this->container->has(Intl::class)) {
            return $this->container->instance(Intl::class, [], true);
        }
        $intl = new Intl();
        $this->container->register($intl, null, [], true);
        return $intl;
    }
    public function views(): Engine
    {
        if ($this->container->has(Engine::class)) {
            return $this->container->instance(Engine::class, [], true);
        }
        $req = $this->req();
        $url = $this->url();
        $intl = $this->intl();
        $cache = $this->cache();
        $views  = (new Engine(realpath($this->get('BASEDIR') . '/app/views/') ?: throw new \RuntimeException()))
            ->addData([
                'req'   => $req,
                'url'   => $url,
                'intl'  => $intl,
                'config' => function (string $k): mixed {
                    return $this->get($k);
                },
                'asset' => function (string $path = '', array $params = []) use ($url, $cache): string {
                    static $assets_version;
                    $path = explode('.', $path);
                    $fext = array_pop($path);
                    if (!$this->get('DEBUG')) {
                        if (!$assets_version) {
                            $assets_version = $cache->getSet('assets_version', function () {
                                return time();
                            }, null, '+1 year');
                        }
                        $path[] = $assets_version;
                    }
                    $path[] = $fext;
                    return $url->linkTo(implode('.', $path), $params);
                }
            ]);
        $this->container->register($views, null, [], true);
        return $views;
    }
    public function mail(): SenderInterface
    {
        if ($this->container->has(SenderInterface::class)) {
            return $this->container->instance(SenderInterface::class, [], true);
        }
        if ($this->get('MAILQUEUE')) {
            $mail = new MailDatabase($this->db(true));
        } else {
            $mail = new MultiSender([
                $this->get('SMTPCONNECTION') ?
                        new SMTPSender($this->get('SMTPCONNECTION')) :
                        new MailSender(),
                $this->get('STORAGE_MAIL') === 'DATABASE' ?
                    new MailDatabase($this->db(true)) :
                    new FileSender(rtrim($this->get('STORAGE_MAIL'), '\\/') . '/' . date('Y-m-d'))
            ]);
        }
        $this->container->register($mail, null, [], true);
        $this->container->register($mail, 'mail', [], true);
        return $mail;
    }
    public function users(): UserManagementInterface
    {
        if ($this->container->has(UserManagementInterface::class)) {
            return $this->container->instance(UserManagementInterface::class, [], true);
        }
        $users = new UserManagementDatabase(
            $this->db(),
            [
                'tableUsers'             => 'users',
                'tableProviders'         => 'user_providers',
                'tableGroups'            => 'grps',
                'tablePermissions'       => 'permissions',
                'tableGroupsPermissions' => 'group_permissions',
                'tableUserGroups'        => 'user_groups'
            ],
            [],
            $this->cache()
        );
        $this->container->register($users, null, [], true);
        return $users;
    }
    public function auth(): AuthManager
    {
        if ($this->container->has(AuthManager::class)) {
            return $this->container->instance(AuthManager::class, [], true);
        }
        $cache = $this->cache();
        $dbc   = $this->db();
        $req   = $this->req();
        $usrm  = $this->users();
        $auth  = AuthManager::fromArray(
            $cache->getSet(
                'AuthManager',
                function () use ($dbc) {
                    return $dbc->all(
                        "SELECT * FROM authentication WHERE disabled = 0 ORDER BY position, authentication"
                    );
                },
                $this->get('APPNAME_CLEAN'),
                3600 * 24
            ),
            $dbc,
            rtrim($req->getUrl()->linkTo($this->get('LOGIN_URL'), [], true), '/'),
            $this->get('PASSWORDKEY')
        );
        $auth->addCallback(function (Credentials $credentials) use ($dbc, $usrm) {
            if ($this->get('AUTOREGISTER')) {
                try {
                    $usrm->getUserByProviderID($credentials->getProvider(), $credentials->getID());
                } catch (UserException $e) {
                    $user = new \vakata\user\User(
                        '',
                        [
                            'name' => $credentials->get('name', ''),
                            'mail' => $credentials->get('mail', '')
                        ]
                    );
                    $user->addGroup($usrm->getGroup((string)$this->get('GROUP_USERS')));
                    $user->addProvider(new Provider($credentials->getProvider(), $credentials->getID()));
                    $usrm->saveUser($user);
                }
            }
            if (
                $dbc->one(
                    "SELECT 1 FROM user_providers WHERE provider = ? AND id = ? AND disabled = 0",
                    [ $credentials->getProvider(), $credentials->getID() ]
                )
            ) {
                $dbc->query(
                    "UPDATE user_providers SET used = ?, details = ? WHERE provider = ? AND id = ? AND disabled = 0",
                    [
                        date('Y-m-d H:i:s'),
                        json_encode($credentials->getData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        $credentials->getProvider(),
                        $credentials->getID()
                    ]
                );
            } else {
                if (
                    !$dbc->one(
                        "SELECT 1 FROM user_pending WHERE provider = ? AND id = ?",
                        [ $credentials->getProvider(), $credentials->getID() ]
                    )
                ) {
                    $dbc->query(
                        "INSERT INTO user_pending (provider, id, name, mail, created, details) VALUES (??)",
                        [
                            $credentials->getProvider(),
                            $credentials->getID(),
                            $credentials->get('name', ''),
                            $credentials->get('mail', ''),
                            date('Y-m-d H:i:s'),
                            json_encode(
                                $credentials->getData(),
                                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                            )
                        ]
                    );
                }
            }
            return new Credentials(
                $credentials->getProvider(),
                $credentials->getID(),
                array_filter([
                    'name' => $credentials->get('name', null),
                    'mail' => $credentials->get('mail', null)
                ])
            );
        });
        $this->container->register($auth, null, [], true);
        return $auth;
    }
    public function di(): DIContainer
    {
        return $this->container;
    }
    public function log(): Logger
    {
        if ($this->container->has(Logger::class)) {
            return $this->container->instance(Logger::class, [], true);
        }
        $log = new Logger($this->get('APPNAME_CLEAN'));
        $log->pushHandler(new DBHandler($this->db(true)));
        $this->container->register($log, [], [], true);
        return $log;
    }
    public function migrations(): Migrations
    {
        return new Migrations(
            $this->db(),
            $this->get('STORAGE_DATABASE'),
            [
                'CMS' => $this->get('CMS'),
                'FORUM' => $this->get('FORUM'),
                'HELP' => $this->get('HELP'),
                'MESSAGING' => $this->get('MESSAGING'),
            ]
        );
    }

    public function middleware(string $class): callable
    {
        switch ($class) {
            case \middleware\Intl::class:
                return new \middleware\Intl(
                    $this->intl(),
                    Collection::from(explode(',', $this->get('LANGUAGES')))
                        ->mapKey(function (string $v): string {
                            return $v;
                        })
                        ->map(function (string $v): string {
                            return $this->get('STORAGE_INTL') . '/' . $v . '.json';
                        })
                        ->toArray(),
                    $this->get('APPNAME_CLEAN'),
                    $this->get('TRANSLATIONS')
                );
            case \middleware\Logger::class:
                $login = $this->get('LOGIN_URL');
                return new \middleware\Logger(
                    (new Logger($this->get('APPNAME_CLEAN')))
                        ->pushHandler(
                            (new StreamHandler(
                                realpath($this->get('STORAGE_LOG')) . '/' . date('Y') . '/' . date('m.d') . '.log',
                                $this->get('DEBUG') ? Logger::DEBUG : Logger::INFO
                            ))
                        ),
                    $this->db(true),
                    ['csp-report', 'ect-report', 'xss-report'],
                    [ $login, 'profile', $login . '/forgot', $login . '/register' ],
                    $this->get('STORAGE_REQ'),
                    [],
                    $this->get('STORAGE_CERTIFICATES'),
                    $this->get('DEBUG')
                );
            case \middleware\Fixer::class:
                return new \middleware\Fixer();
            case \middleware\ClientIP::class:
                return new \middleware\ClientIP();
            case \middleware\User::class:
                return new \middleware\User(
                    $this->get('SIGNATUREKEY'),
                    $this->get('ENCRYPTIONKEY'),
                    $this->get('APPNAME_CLEAN'),
                    $this->users(),
                    $this->get('SESSION_TIMEOUT'),
                    $this->get('LOGIN_URL'),
                    false
                );
            case \middleware\Session::class:
                return new \middleware\Session($this->sess(), $this->get('SESSION_REGENERATE'));
            case \middleware\UserDecorator::class:
                $dbc = $this->db();
                $cache = $this->cache();
                return new \middleware\UserDecorator(
                    $dbc,
                    $this->get('APPNAME_CLEAN') . '_SITE',
                    function (User $user) use ($dbc, $cache) {
                        $user->set(
                            'auth',
                            $cache->getSet(
                                'user-callback-' . $user->getID(),
                                function () use ($dbc, $user) {
                                    return $dbc->all(
                                        "SELECT provider, id, details FROM user_providers
                                        WHERE disabled = 0 AND details IS NOT NULL AND usr = ?",
                                        $user->getID()
                                    );
                                },
                                null,
                                180
                            )
                        );
                    },
                    $this->cache(),
                    90,
                    $this->get('MESSAGING'),
                    $this->get('FORUM'),
                    $this->get('CMS'),
                    $this->get('MULTISITE')
                );
            case \middleware\PushNotifications::class: // if PUSH_NOTIFICATIONS
                return new \middleware\PushNotifications($this->db());
            case \middleware\HTTPS::class: // if FORCE_HTTPS
                return new \middleware\HTTPS(
                    $this->get('DEBUG') ? 1 : 30,
                    $this->get('DEBUG') ? 1 : 30,
                    false,
                    $this->req()->getUrl()->linkTo('ect-report', [], true)
                );
            case \middleware\Gzip::class: // if GZIP
                return new \middleware\Gzip();
            case \middleware\OWASP::class:
                return new \middleware\OWASP($this->url()->linkTo('xss-report', [], true));
            case \middleware\CSP::class: // if CSP
                $req = $this->req();
                $nonce = $req->isAjax() && !$req->isCors() && $req->hasHeader('X-CSPNonce') ?
                    $req->getHeaderLine('X-CSPNonce') :
                    Generator::string();
                $this->views()->addData([ 'cspNonce' => $nonce ]);
                return new \middleware\CSP([
                    // 'default-src'    => [ "'self'", "'unsafe-inline'" ],
                    // 'script-src'     => ["'self'", "'nonce-" . $nonce . "'"],
                    // 'style-src'      => ["'self'", "'nonce-" . $nonce . "'"],
                    'img-src'        => ["'self'", "data:", "blob:", "https://*.tile.openstreetmap.org"],
                    'font-src'       => ["'self'", "data:"],
                    'frame-src'      => ["'self'"],
                    'report-uri'     => $req->getUrl()->linkTo('csp-report', [], true),
                    // 'style-src-elem' => ["'self'", "'nonce-" . $nonce . "'"],
                    'style-src-attr' => ["'self'", "'nonce-" . $nonce . "'" , "'unsafe-inline'"],
                ]);
            case \middleware\FP::class: // if FP
                return new \middleware\FP();
            case \middleware\Ratelimit::class: // if RATELIMIT_REQUESTS & RATELIMIT_SECONDS
                return new \middleware\Ratelimit(
                    $this->db(),
                    $this->get('RATELIMIT_REQUESTS'),
                    $this->get('RATELIMIT_SECONDS')
                );
            case \middleware\IDS::class: // if IDS
                return new \middleware\IDS((int)$this->get('IDS'));
            case \middleware\CSRF::class: // if CSRF
                return new \middleware\CSRF(
                    $this->get('SIGNATUREKEY'),
                    $this->get('ENCRYPTIONKEY'),
                    [ 'iss'  => $this->get('APPNAME') ],
                    $this->get('CSRF_TIMEOUT')
                );
            case \middleware\CORS::class: // if CORS
                return new \middleware\CORS();
            case \middleware\Restore::class: // if FORGOT_PASSWORD
                return new \middleware\Restore(
                    $this->users(),
                    $this->auth(),
                    $this->views(),
                    $this->get('LOGIN_URL') . '/forgot',
                    $this->get('SIGNATUREKEY'),
                    $this->get('ENCRYPTIONKEY'),
                    $this->get('APPNAME'),
                    $this->mail(),
                    $this->intl(),
                    $this->get('FORGOT_PASSWORD')
                );
            case \middleware\Register::class: // if REGISTER_PASSWORD
                return new \middleware\Register(
                    $this->users(),
                    $this->auth(),
                    $this->views(),
                    $this->get('LOGIN_URL') . '/register',
                    $this->get('SIGNATUREKEY'),
                    $this->get('ENCRYPTIONKEY'),
                    $this->get('APPNAME'),
                    $this->mail(),
                    $this->intl(),
                    $this->get('REGISTER_PASSWORD'),
                    [ $this->get('GROUP_USERS') ]
                );
            case \middleware\Basic::class: // if BASIC_AUTH
                return new \middleware\Basic(
                    $this->auth(),
                    $this->users(),
                    $this->views(),
                    $this->get('LOGIN_URL'),
                    false
                );
            case \middleware\Auth::class:
                return new \middleware\Auth(
                    $this->auth(),
                    $this->views(),
                    $this->get('LOGIN_URL'),
                    array_filter([
                        $this->get('LOGIN_URL') . '/forgot' => $this->get('FORGOT_PASSWORD') ?
                            'common.login.forgot' :
                            null,
                        $this->get('LOGIN_URL') . '/register' => $this->get('REGISTER_PASSWORD') ?
                            'common.login.register' :
                            null
                    ]),
                    $this->get('BASIC_AUTH')
                );
            case \middleware\TFA::class:
                return new \middleware\TFA(
                    $this->users(),
                    $this->views(),
                    $this->get('APPNAME_CLEAN'),
                    $this->get('LOGIN_URL') . '/tfa',
                    $this->get('FORCE_TFA'),
                    $this->get('TFA_REMEMBER')
                );
            case \middleware\Maintenance::class: // if MAINTENANCE
                return new \middleware\Maintenance($this->get('GROUP_ADMINS'), $this->get('LOGIN_URL'));
            case \middleware\Uploads::class:
                return new \middleware\Uploads(
                    $this->files(),
                    'upload',
                    $this->get('STORAGE_TMP'),
                    $this->get('SENDFILE'),
                    $this->get('MAX_IMAGE_SIZE'),
                );
            default:
                throw new \RuntimeException('Unknown middleware');
        }
    }
    public function run(array $stack, Request $req): Response
    {
        $this->container->register($req);
        $this->container->register($req->getUrl());
        $this->views()->addData([
            'req' => $req,
            'url' => $req->getUrl()
        ]);
        $run = function (Request $req) use (&$stack, &$run): Response {
            return call_user_func(array_shift($stack), $req, $run);
        };
        return $run($req);
    }

    public function dispatcher(): Dispatcher
    {
        if ($this->container->has(Dispatcher::class)) {
            return $this->container->instance(Dispatcher::class, [], true);
        }
        $dispatcher = new Dispatcher();
        $this->container->register($dispatcher, null, [], true);
        return $dispatcher;
    }
    public function listen(string $event, callable $listener): Dispatcher
    {
        return $this->dispatcher()->listen($event, $listener);
    }
    public function dispatch(EventInterface $event, bool $lazy = false): Dispatcher
    {
        return $this->dispatcher()->dispatch($event, $lazy);
    }
}
