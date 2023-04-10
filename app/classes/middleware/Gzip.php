<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\http\Response;

class Gzip
{
    public function __invoke(Request $req, callable $next): Response
    {
        $res = $next($req);
        $acc = $req->getHeaderLine('Accept-Encoding');
        $avf = stream_get_filters();
        if (
            !ini_get('zlib.output_compression') &&
            !$res->hasHeader("Content-Encoding") &&
            (in_array('zlib.*', $avf) || in_array('zlib.deflate', $avf)) &&
            (strpos($acc, '*') !== false || strpos($acc, 'gzip') !== false)
        ) {
            $stream = $res->getBody()->detach();
            stream_filter_append(
                $stream,
                'zlib.deflate',
                STREAM_FILTER_READ,
                [ 'level' => 6, 'window' => 30, 'memory' => 6 ]
            );
            $res->getBody()->attach($stream);
            return $res
                ->withoutHeader('Content-Length')
                ->withHeader('Content-Encoding', 'gzip');
        }
        return $res;
    }
}
