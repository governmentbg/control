<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\session\Session as Sess;

class Session
{
    protected Sess $session;
    protected int $regenerate;

    public function __construct(Sess $session, int $regenerate = 0)
    {
        $this->session = $session;
        $this->regenerate = $regenerate;
    }

    public function __invoke(Request $req, callable $next): Response
    {
        $res = $next(
            $req->withAttribute('session', $this->session)
        );
        if ($this->session->isStarted()) {
            if ($this->session->get('_SESSID_REGENERATED') === null) {
                $this->session->set('_SESSID_REGENERATED', time());
            }
            if ($this->regenerate && (int)$this->session->get('_SESSID_REGENERATED') + $this->regenerate < time()) {
                $this->session->regenerate(true);
                $this->session->set('_SESSID_REGENERATED', time());
            }
        }
        return $res;
    }
}
