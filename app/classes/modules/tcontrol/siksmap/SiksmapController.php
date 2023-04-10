<?php

declare(strict_types=1);

namespace modules\tcontrol\siksmap;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use vakata\http\Uri as Url;

class SiksmapController
{
    protected Request $request;
    protected Response $response;
    protected Url $url;
    protected string $module;
    protected Views $views;
    protected SiksmapService $service;

    public function __construct(Request $request, Response $response, Views $views, SiksmapService $service)
    {
        $this->request = $request;
        $this->response = $response;
        $this->url = $this->request->getUrl();
        $this->module = $this->url->getSegment(0);
        $this->views = $views;
        $this->service = $service;
        $this->views->addFolder($this->module, __DIR__ . '/views');
    }
    public function getIndex() : Response
    {
        $period = $this->request->getQuery('period', null, 'string');

        return $this->response
            ->setBody(
                $this->views->render(
                    $this->module . '::index',
                    [
                        'points'    => $this->service->getSiksCoordinates($period),
                        'period'    => $period
                    ]
                )
            );
    }
    public function getPoints() : Response
    {
        $period = $this->request->getQuery('period', null, 'string');

        return $this->response
            ->setBody(
                json_encode(
                    array_values($this->service->getSiksCoordinates($period))
                )
            )
            ->setContentTypeByExtension('json');
    }
}