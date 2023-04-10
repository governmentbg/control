<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\database\DBInterface;
use vakata\http\Response;

class Ratelimit
{
    protected DBInterface $db;
    protected int $requests;
    protected int $seconds;

    public function __construct(DBInterface $db, int $requests = 10, int $seconds = 2)
    {
        $this->db = $db;
        $this->requests = $requests;
        $this->seconds = $seconds;
    }
    public function __invoke(Request $req, callable $next): Response
    {
        if ($req->getAttribute('client-ip')) {
            $cnt = $this->db->one(
                "SELECT COUNT(id) FROM log WHERE ip = ? AND created > ?",
                [ $req->getAttribute('client-ip'), date('Y-m-d H:i:s', time() - $this->seconds) ]
            );
            if ($cnt >= $this->requests) {
                throw new \Exception("Rate limit exceeded", 429);
            }
        }
        return $next($req);
    }
}
