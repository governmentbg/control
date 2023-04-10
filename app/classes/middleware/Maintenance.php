<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\user\User;

class Maintenance
{
    protected string $group;
    protected string $login;

    public function __construct(string $group, string $login)
    {
        $this->group = $group;
        $this->login = $login;
    }
    public function __invoke(Request $req, callable $next): Response
    {
        $user = $req->getAttribute('user');
        if ($user instanceof User && !$user->inGroup($this->group)) {
            return (new Response())
                ->withHeader('Location', $req->getUrl()->linkTo($this->login))
                ->withHeader('X-Log', 'Maintenance mode activated');
        }
        return $next($req);
    }
}
