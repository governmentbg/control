<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\database\DBInterface;
use vakata\http\Response;
use vakata\cache\CacheInterface;
use Closure;

class UserDecorator
{
    protected DBInterface $db;
    protected string $siteCookieName;
    protected ?Closure $callback;
    protected ?CacheInterface $cache;
    protected int $cacheTimeout;
    protected bool $messaging = false;
    protected bool $forum = false;
    protected bool $cms = false;
    protected bool $multisite = false;

    public function __construct(
        DBInterface $db,
        string $siteCookieName = 'SITE',
        ?callable $callback = null,
        ?CacheInterface $cache = null,
        int $cacheTimeout = 90,
        bool $messaging = false,
        bool $forum = false,
        bool $cms = false,
        bool $multisite = false
    ) {
        $this->db = $db;
        $this->cache = $cache;
        $this->cacheTimeout = $cacheTimeout;
        $this->siteCookieName = $siteCookieName;
        $this->callback = $callback ? Closure::fromCallable($callback) : null;
        $this->messaging = $messaging;
        $this->forum = $forum;
        $this->cms = $cms;
        $this->multisite = $multisite;
    }
    public function __invoke(Request $req, callable $next): Response
    {
        $user  = $req->getAttribute('user');
        if (!$user) {
            return $next($req);
        }
        $tmp = [];
        if ($this->cache) {
            $tmp = $this->cache->get('user-' . $user->getID());
        }
        $tmp['organization'] = $tmp['organization'] ?? $this->db->all(
            "SELECT o2.*
                FROM
                    organization o1,
                    organization o2,
                    user_organizations uo
                WHERE o1.org = uo.org AND o2.lft >= o1.lft AND o2.rgt <= o1.rgt AND uo.usr = ?
                ORDER BY o2.lft",
            $user->getID(),
            'org'
        );
        if ($this->messaging) {
            $tmp['notifications'] = $tmp['notifications'] ?? $this->db->one(
                "SELECT COUNT(notification) FROM notification_recipients
                    WHERE recipient = ? AND opened IS NULL",
                $user->getID()
            );
        }
        if ($this->forum) {
            $tmp['forums'] = $tmp['forums'] ?? $this->db->one(
                "SELECT COUNT(DISTINCT t.topic) FROM forum_topics t, user_forums u, forums f
                    WHERE
                    t.hidden = 0 AND f.hidden = 0 AND u.topic = t.topic AND u.usr = ? AND u.seen < t.updated",
                $user->getID()
            );
        }
        if ($this->cms) {
            if ($this->multisite) {
                $tmp['sites'] = $tmp['sites'] ?? $this->db->all(
                    "SELECT s.site, s.name
                    FROM sites s, user_site us
                    WHERE s.disabled = 0 AND s.site = us.site AND us.usr = ?
                    ORDER BY s.name",
                    $user->getID(),
                    'site',
                    true
                );
                $site = $req->getCookieParams()[$this->siteCookieName] ?? null;
                $user->set('site', isset($tmp['sites'][$site]) ? $site : (array_keys($tmp['sites'] ?? [])[0] ?? null));
            } else {
                $tmp['sites'] = $tmp['sites'] ?? $this->db->all(
                    "SELECT s.site, s.name
                    FROM sites s
                    WHERE disabled = 0 AND dflt = 1",
                    [],
                    'site',
                    true
                );
                $user->set('site', array_keys($tmp['sites'])[0] ?? null);
            }

            if ($user->site) {
                $tmp['languages'] = $tmp['languages'] ?? $this->db->all(
                    "SELECT l.lang, l.local
                    FROM languages l, user_lang ul, site_lang sl
                    WHERE l.lang = ul.lang AND ul.usr = ? AND sl.lang = ul.lang AND sl.site = ?
                    ORDER BY l.local",
                    [ $user->getID(), $user->site ],
                    'lang',
                    true
                );
            } else {
                $tmp['languages'] = $tmp['languages'] ?? $this->db->all(
                    "SELECT l.lang, l.local
                    FROM languages l, user_lang ul
                    WHERE l.lang = ul.lang AND ul.usr = ?
                    ORDER BY l.local",
                    $user->getID(),
                    'lang',
                    true
                );
            }
        }
        $tmp['sites'] = $tmp['sites'] ?? $this->db->all(
            "SELECT election, name FROM elections WHERE enabled = 1",
            null,
            'election',
            true
        );
        $site = $req->getCookieParams()[$this->siteCookieName] ?? null;
        $user->set('site', isset($tmp['sites'][$site]) ? $site : (array_keys($tmp['sites'] ?? [])[0] ?? null));
        if ($this->cache) {
            $this->cache->set('user-' . $user->getID(), $tmp, null, $this->cacheTimeout);
        }
        foreach ($tmp as $k => $v) {
            $user->set($k, $v);
        }
        if ($this->callback) {
            call_user_func($this->callback, $user);
        }
        return $next($req);
    }
}
