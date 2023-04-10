<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\database\DBInterface;
use vakata\http\Response;

class PushNotifications
{
    protected DBInterface $db;
    protected string $path;

    public function __construct(DBInterface $db, string $path = 'pushnotifications')
    {
        $this->db = $db;
        $this->path = $path;
    }
    public function __invoke(Request $req, callable $next): Response
    {
        $user = $req->getAttribute('user');
        if ($user && trim($req->getUrl()->getRealPath(), '/') === $this->path && $req->getPost('subscription')) {
            $push = $this->db->one("SELECT push FROM users WHERE usr = ?", [ $user->getID() ]);
            $push = $push ? (json_decode($push, true) ?? []) : [];
            $subscription = json_decode($req->getPost('subscription'), true);
            if (is_array($subscription) && isset($subscription['endoint']) && is_string($subscription['endoint'])) {
                $push[md5($subscription['endpoint'])] = $subscription;
                $this->db->query("UPDATE users SET push = ? WHERE usr = ?", [ json_encode($push), $user->getID() ]);
            }
            return (new Response());
        }
        return $next($req);
    }
}
