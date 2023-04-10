<?php

declare(strict_types=1);

namespace modules\tcontrol\mikreport;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use vakata\http\Uri as Url;

class MikreportController
{
    protected Request $request;
    protected Response $response;
    protected Url $url;
    protected string $module;
    protected Views $views;
    protected MikreportService $service;

    public function __construct(Request $request, Response $response, Views $views, MikreportService $service)
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
        return $this->response
            ->setBody(
                $this->views->render(
                    $this->module . '::index',
                    [
                        'data' => $this->service->getData()
                    ]
                )
            );
    }
}