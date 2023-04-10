<?php

declare(strict_types=1);

namespace modules\administration\errors;

use DateTime;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;

class ErrorsController
{
    protected ErrorsService $service;

    public function __construct(ErrorsService $service)
    {
        $this->service = $service;
    }
    public function getIndex(Request $req, Response $res, Views $views): Response
    {
        if (!$views->getFolders()->exists('errors')) {
            $views->addFolder('errors', __DIR__ . '/views');
        }

        $date = DateTime::createFromFormat('d.m.Y', $req->getQuery('date', date('d.m.Y')));
        if (!$date) {
            $date = new DateTime();
        }

        return $res->setBody(
            $views->render('errors::index', [
                'date'   => $date->format('d.m.Y'),
                'errors' => $this->service->list($date)
            ])
        );
    }
}
