<?php

declare(strict_types=1);

namespace modules\administration\settings;

use vakata\http\Uri as Url;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use vakata\user\User as User;
use vakata\session\Session as Session;

/** @SuppressWarnings(PHPMD.EvalExpression) */
class SettingsController
{
    public static function permissions(): array
    {
        return [ 'settings/shell', 'settings/files', 'settings/adminer' ];
    }

    protected User $user;
    private SettingsService $service;

    public function __construct(SettingsService $service, User $user)
    {
        $this->user    = $user;
        $this->service = $service;
    }
    public function getIndex(Response $res, Views $views): Response
    {
        if (!$views->getFolders()->exists('settings')) {
            $views->addFolder('settings', __DIR__ . '/views');
        }

        $status = $this->service->status();

        return $res->setBody(
            $views->render(
                'settings::index',
                [
                    'debug'       => $status['debug'],
                    'maintenance' => $status['maintenance'],
                    'csp'         => $status['csp'],
                    'csrf'        => $status['csrf'],
                    'cors'        => $status['cors'],
                    'ids'         => $status['ids'],
                    'gzip'        => $status['gzip'],
                    'https'       => $status['https'],
                    'totp'        => $status['totp'],
                    'ratelimit'   => $status['ratelimit'],
                    'shell'       => $this->user->hasPermission('settings/shell'),
                    'adminer'     => $this->user->hasPermission('settings/adminer'),
                    'files'       => $this->user->hasPermission('settings/files'),
                    'writable'    => $status['writable']
                ]
            )
        );
    }
    public function postClear(Response $res, Url $url): Response
    {
        $this->service->clearCache();
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postDatabase(Response $res, Url $url): Response
    {
        $this->service->updateDatabaseSchema();
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postClearfiles(Response $res, Url $url): Response
    {
        $this->service->clearFiles();
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postDebug(Response $res, Url $url): Response
    {
        $this->service->setDebug(!$this->service->status()['debug']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postMaintenance(Response $res, Url $url): Response
    {
        $this->service->setMaintenance(!$this->service->status()['maintenance']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postGzip(Response $res, Url $url): Response
    {
        $this->service->setGzip(!$this->service->status()['gzip']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postHttps(Response $res, Url $url): Response
    {
        $this->service->setHttps(!$this->service->status()['https']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postTotp(Response $res, Url $url): Response
    {
        $this->service->setTotp(!$this->service->status()['totp']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postCors(Response $res, Url $url): Response
    {
        $this->service->setCors(!$this->service->status()['cors']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postCsrf(Response $res, Url $url): Response
    {
        $this->service->setCsrf(!$this->service->status()['csrf']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postCsp(Response $res, Url $url): Response
    {
        $this->service->setCsp(!$this->service->status()['csp']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postIds(Response $res, Url $url): Response
    {
        $this->service->setIds(!$this->service->status()['ids']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postRatelimit(Response $res, Url $url): Response
    {
        $this->service->setRateLimit(!$this->service->status()['ratelimit']);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
}
