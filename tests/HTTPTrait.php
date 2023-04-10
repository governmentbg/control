<?php

declare(strict_types=1);

namespace tests;

use Laminas\Diactoros\Response\Serializer as ResponseSerializer;
use Laminas\Diactoros\Request\Serializer as RequestSerializer;
use helpers\AppStatic as App;
use helpers\AuthManager;
use vakata\session\Session;

trait HTTPTrait
{
    protected $request = null;
    protected $response = null;
    protected $cookies = [];
    protected $csrf = null;
    protected $stack = [];

    protected function exec(): void
    {
        if (!count($this->stack)) {
            // prevent parsing request from globals
            App::di()->register(\vakata\http\Request::fromString(
                'GET / HTTP/1.1' . "\r\n" .
                'Host: localhost' . "\r\n"
            ));
            App::url()->setBasePath('/')->withHost('localhost');
            // mock authentication - converts admin:admin to mockauth:mockauth
            App::di()->register(
                MockAuthManager::fromArray(
                    App::cache()->getSet(
                        'AuthManager',
                        function () {
                            return App::db()->all(
                                "SELECT * FROM authentication WHERE disabled = 0 ORDER BY position, authentication"
                            );
                        },
                        App::get('APPNAME_CLEAN'),
                        3600 * 24
                    ),
                    App::db(),
                    rtrim(App::url()->linkTo(App::get('LOGIN_URL'), [], true), '/'),
                    App::get('PASSWORDKEY')
                ),
                [ AuthManager::class ],
                [],
                true
            );
            // mock usermanagement - to allow mockauth:mockauth credentials
            App::di()->register(
                new MockUserManagementDatabase(
                    App::db(),
                    [
                        'tableUsers'             => 'users',
                        'tableProviders'         => 'user_providers',
                        'tableGroups'            => 'grps',
                        'tablePermissions'       => 'permissions',
                        'tableGroupsPermissions' => 'group_permissions',
                        'tableUserGroups'        => 'user_groups'
                    ],
                    [],
                    App::cache()
                ),
                null,
                [],
                true
            );
            // mock session
            App::di()->register(new Session(false));
            $this->stack = array_filter([
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
                App::middleware(\middleware\OWASP::class),
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
        }
        $this->request->getUrl()->setBasePath('/')->withHost('localhost');
        $this->response = App::run($this->stack, $this->request);
    }
    protected function debug(string $message): self
    {
        //file_put_contents(__DIR__ . '/dump.log', $message . "\r\n", FILE_APPEND);
        return $this;
    }
    protected function clear(): self
    {
        $this->request = null;
        $this->response = null;
        $this->cookies = [];
        $this->csrf = null;
        return $this;
    }
    protected function get(string $url, bool $follow = false): self
    {
        $cookies = [];
        foreach ($this->cookies as $k => $v) {
            $cookies[] = $k . '=' . $v;
        }
        $url = str_replace('http://localhost/', '', $url);
        $this->request = \vakata\http\Request::fromString(
            'GET /' . ltrim($url, '/') . ' HTTP/1.1' . "\r\n" .
            'Host: localhost' . "\r\n" .
            ($this->request !== null ? 'Referer: ' . $this->request->getUrl() . "\r\n" : '') .
            'Cookie: ' . implode('; ', $cookies) . "\r\n"
        );
        $this->exec();
        if ($this->response instanceof \vakata\http\Response) {
            foreach ($this->response->getHeader('Set-Cookie') as $cookie) {
                $cookie = explode('=', explode(';', $cookie)[0], 2);
                $this->cookies[$cookie[0]] = $cookie[1] ?? '';
            }
            $body = (string)$this->response->getBody();
            if (strpos($body, 'name="_csrf_token"')) {
                $this->csrf = explode('"', explode('value="', explode('name="_csrf_token"', $body, 2)[1], 2)[1], 2)[0];
            }
            $this->debug(RequestSerializer::toString($this->request));
            $this->debug("==============");
            $this->debug(ResponseSerializer::toString($this->response));
            $this->debug("\r\n");
        }
        if ($follow && $this->response->hasHeader('Location')) {
            return $this->get($this->response->getHeaderLine('Location'), true);
        }
        return $this;
    }
    protected function post($url = null, array $data = []): self
    {
        if (is_array($url)) {
            $data = $url;
            $url = null;
        }
        if ($url === null) {
            $url = '/' . $this->request->getUrl()->getRealPath();
        }
        $cookies = [];
        foreach ($this->cookies as $k => $v) {
            $cookies[] = $k . '=' . $v;
        }
        $data['_csrf_token'] = $this->csrf;
        $data = http_build_query($data);
        $this->request = \vakata\http\Request::fromString(
            'POST /' . ltrim($url, '/') . ' HTTP/1.1' . "\r\n" .
            'Host: localhost' . "\r\n" .
            ($this->request !== null ? 'Referer: ' . $this->request->getUrl() . "\r\n" : '') .
            'Cookie: ' . implode('; ', $cookies) . ";\r\n" .
            'Content-Type: application/x-www-form-urlencoded;' . ";\r\n" .
            'Content-Length: ' . strlen($data) . ";\r\n" .
            "\r\n" .
            $data
        );
        $this->exec();
        if ($this->response instanceof \vakata\http\Response) {
            foreach ($this->response->getHeader('Set-Cookie') as $cookie) {
                $cookie = explode('=', explode(';', $cookie)[0], 2);
                $this->cookies[$cookie[0]] = $cookie[1] ?? '';
            }
            $body = (string)$this->response->getBody();
            if (strpos($body, 'name="_csrf_token"')) {
                $this->csrf = explode('"', explode('value="', explode('name="_csrf_token"', $body, 2)[1], 2)[1], 2)[0];
            }
            $this->debug(RequestSerializer::toString($this->request));
            $this->debug("***");
            $this->debug(ResponseSerializer::toString($this->response));
            $this->debug("\r\n");
        }
        return $this;
    }
    protected function follow(): self
    {
        if (!$this->response) {
            throw new \Exception('No response available to follow');
        }
        if (!$this->response->hasHeader('Location')) {
            throw new \Exception('No Location header set');
        }
        return $this->get($this->response->getHeaderLine('Location'));
    }
    protected function assertStatus($status, $message = ''): self
    {
        $this->assertEquals($status, $this->response->getStatusCode(), $message);
        return $this;
    }
    protected function assertHeader($name, $value): self
    {
        $this->assertEquals($value, $this->response->getHeaderLine($name));
        return $this;
    }
    protected function assertBodyContains($needle): self
    {
        $this->assertStringContainsString($needle, (string)$this->response->getBody());
        return $this;
    }
    protected function assertLocation($url): self
    {
        $this->assertEquals(trim($url, '/'), trim($this->request->getUrl()->getRealPath(), '/'));
        return $this;
    }
}
