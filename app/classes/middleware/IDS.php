<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\ids\IDS as Intrusion;
use vakata\http\Response;

class IDS
{
    protected int $impact;
    protected array $exclude;

    public function __construct(int $impact = 0, array $exclude = [])
    {
        $this->impact = $impact;
        $this->exclude = $exclude;
    }
    public function __invoke(Request $req, callable $next): Response
    {
        $impact = 0;
        if (!in_array($req->getUrl()->getRealPath(), $this->exclude)) {
            // simple IDS using the rules from Expose
            $ids = Intrusion::fromDefaults();
            $impact = $ids->analyzeData(['get' => $req->getQueryParams(), 'post' => $req->getParsedBody() ]);
            if ($this->impact && $impact >= $this->impact) { // impact is too high - do not process the request
                throw new \Exception('IDS limits exceeded', 400);
            }
        }
        // provided IDS is happy and rate limits are met - continue with processing
        $res = $next($req);
        if ($impact > 0) { // impact is non zero
            $res = $res->withHeader('X-Log', 'IDS Impact: ' . $impact);
        }
        return $res;
    }
}
