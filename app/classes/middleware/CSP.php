<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\http\Response;

class CSP
{
    protected array $csp;

    public function __construct(array $csp = [])
    {
        $this->csp = count($csp) ? $csp : [ 'default-src' => "'self'" ];
    }
    public function __invoke(Request $req, callable $next): Response
    {
        $res = $next($req);
        if (count($this->csp)) {
            $value = '';
            foreach ($this->csp as $k => $v) {
                $value .= $k . ' ' . implode(' ', is_array($v) ? $v : [$v]) . '; ';
            }
            $res = $res->withHeader('Content-Security-Policy', $value);
        }
        return $res;
    }
}
