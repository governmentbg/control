<?php

declare(strict_types=1);

namespace modules\tcontrol\map;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use vakata\http\Uri as Url;

class MapController
{
    protected Request $request;
    protected Response $response;
    protected Url $url;
    protected string $module;
    protected Views $views;
    protected MapService $service;

    public function __construct(Request $request, Response $response, Views $views, MapService $service)
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
                        'points'    => $this->service->getDevicesCoordinates($period),
                        'period'    => $period
                    ]
                )
            );
    }
    public function getPoints() : Response
    {
        return $this->response
            ->setBody(
                json_encode(
                    $this->service->getDevicesCoordinates(
                        $this->request->getQuery('period', null, 'string')
                    )
                )
            )
            ->setContentTypeByExtension('json');
    }
}