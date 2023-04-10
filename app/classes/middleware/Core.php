<?php

declare(strict_types=1);

namespace middleware;

use helpers\AuthManager;
use League\Plates\Engine;
use modules\administration\groups\GroupsService;
use modules\administration\permissions\PermissionsService;
use vakata\cache\CacheInterface;
use vakata\database\DBInterface;
use vakata\di\DIContainer;
use vakata\http\Request;
use vakata\http\Response;
use vakata\user\UserManagementInterface;

class Core
{
    protected DBInterface $dbc;
    protected AuthManager $auth;
    protected UserManagementInterface $usrm;
    protected CacheInterface $cache;
    protected Engine $views;
    protected DIContainer $di;
    protected string $base;
    protected bool $forceTFA = false;
    protected bool $help = false;

    public function __construct(
        DBInterface $dbc,
        UserManagementInterface $usrm,
        AuthManager $auth,
        CacheInterface $cache,
        Engine $views,
        DIContainer $di,
        string $base,
        bool $forceTFA = false,
        bool $help = false
    ) {
        $this->dbc = $dbc;
        $this->auth = $auth;
        $this->usrm = $usrm;
        $this->cache = $cache;
        $this->views = $views;
        $this->di = $di;
        $this->base = $base;
        $this->forceTFA = $forceTFA;
        $this->help = $help;
    }
    public function __invoke(Request $req): Response
    {
        $di = $this->di;
        $dbc = $this->dbc;
        $usrm = $this->usrm;
        $auth = $this->auth;
        $cache = $this->cache;
        $views = $this->views;
        $url = $req->getUrl();
        $user = $req->getAttribute('user');
        $token = $req->getAttribute('token');

        // di and views
        $di->register($req, null, [], true);
        $di->register($user);
        $di->register($token);
        foreach ($auth->getProviders() as $method) {
            $di->register($method);
        }
        $sess = $req->getAttribute('session');
        if ($sess !== null) {
            $views->addData(['session' => $sess]);
        }
        $views->addData(['req' => $req]);

        $temp = $cache->getSet('modules', function () use ($dbc) {
            return $dbc->all("SELECT * FROM modules WHERE loaded = 1 ORDER BY pos", [], 'name');
        }, null, 365 * 24 * 3600);
        $modules = [];
        if ($this->forceTFA && !$token->getClaim('tfa') && isset($temp['profile'])) {
            $modules['profile'] = $temp['profile'];
        } else {
            $modules = $temp;
            foreach ($modules as $name => $data) {
                if (!$data['classname']) {
                    unset($modules[$name]);
                    continue;
                }
                GroupsService::module($data['classname']);
                PermissionsService::module($data['classname']);
            }
        }
        // add the CRUD views to the views registry
        if (!$views->getFolders()->exists('crud')) {
            $views->addFolder('crud', $this->base . '/app/classes/modules/common/crud/views');
        }
        // initialize all modules
        $definitions = [];
        foreach ($modules as $name => $data) {
            if ($user->hasPermission($name)) {
                $definitions[$name] = $data;
            }
        }
        // open the first ALLOWED module by default
        $controller = $url->getSegment(0, (string)(array_keys($definitions)[0] ?? ''));
        // hit the index method if none is specified
        $method = $url->getSegment(1, 'index');
        $views->addData(['modules' => $definitions ]);
        $views->addData(['app' => $di ]);
        $views->addData(['usrm' => $usrm ]);
        $views->addData(['user' => $user ]);
        if ($this->help) {
            $views->addData(['helper'  => (new \modules\common\help\HelpService($dbc))->get($url->getRealPath()) ]);
        }
        // try to run a registered module based on the first two parts of the path
        if (!isset($definitions[$controller])) {
            throw new \Exception('Controller not found', 404);
        }
        $verb = strtolower($req->getMethod());
        $controller = $di->instance($definitions[$controller]['classname']);
        foreach ([ $verb . ucfirst($method), $method, '_' . $verb, '_any' ] as $method) {
            if (method_exists($controller, $method)) {
                return $di->invoke($controller, $method);
            }
        }
        throw new \Exception('Method not found', 404);
    }
}
