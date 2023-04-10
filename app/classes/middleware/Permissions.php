<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\user\User;

class Permissions
{
    public function __invoke(Request $req, callable $next): Response
    {
        $url   = $req->getUrl();
        $user  = $req->getAttribute('user');

        if (!$user || !($user instanceof User)) {
            $next($req);
        }
        $seg = explode('/', trim($url->getRealPath(), '/'));
        $cnt = count($seg);
        for ($i = 1; $i <= $cnt; $i++) {
            $tmp = implode('/', array_slice($seg, 0, $i));
            if (!$user->hasPermission($tmp)) {
                throw new \Exception('Not allowed', 403);
            }
        }
        return $next($req);
    }
}
