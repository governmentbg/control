<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\cache\CacheInterface;
use Laminas\Diactoros\Response\Serializer as ResponseSerializer;
use Psr\Http\Message\ResponseInterface;

class Cache
{
    protected CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
    public function __invoke(Request $req, callable $next): ResponseInterface
    {
        $cacheable = in_array($req->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE']);
        if (!$cacheable) {
            return $next($req);
        }
        $key = md5($req->getMethod() . ' ' . $req->getUrl()->getRealPath());
        if ($cached = $this->cache->get($key)) {
            return ResponseSerializer::fromString($cached)->withHeader('X-Cache-Hit', $key);
        }
        $res = $next($req);
        $this->cache->set($key, ResponseSerializer::toString($res));
        return $res;
    }
}
