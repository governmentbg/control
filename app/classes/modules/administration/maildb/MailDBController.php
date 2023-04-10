<?php

declare(strict_types=1);

namespace modules\administration\maildb;

use DateTime;
use vakata\collection\Collection;
use helpers\html\Button as Button;
use helpers\html\Table;
use helpers\html\TableRow;
use vakata\http\Uri as Url;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use modules\common\crud\CRUDController;
use vakata\config\Config;

class MailDBController extends CRUDController
{
    private MailDBService $service;
    protected bool $hasQueue = false;

    public function __construct(Request $request, Response $response, Views $views, MailDBService $service, Config $c)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
        $this->hasQueue = $c->get('MAILQUEUE');
    }

    protected function getTable(iterable $entities): Table
    {
        $table = parent::getTable($entities);
        $table->removeOperation('import');
        $table->removeOperation('export');
        $table->removeOperation('create');
        $table
            ->removeColumn('mail')
            ->removeColumn('started')
            ->removeColumn('content');
        $table
            ->getColumn('added')
                ->addClass('center aligned')
                ->setMap(function (mixed $v) {
                    return ($v && ($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                        $temp->format('d.m.Y H:i:s') : '');
                });
        $table
            ->getColumn('finished')
                ->addClass('center aligned')
                ->setMap(function (mixed $v) {
                    return ($v && ($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                        $temp->format('d.m.Y H:i:s') : '');
                });
        if (!$this->hasQueue) {
            $table
                ->removeColumn('finished')
                ->removeColumn('priority')
                ->removeColumn('result');
        }
        Collection::from($table->getRows())->each(function (TableRow $v) {
            $operations = [];
            $operations['download'] = (new Button("download"))
                ->setLabel($this->module . '.operations.download')
                ->setIcon('mail')
                ->setClass('skip blank mini blue icon button')
                ->setAttr('href', $this->module . '/download/' . $v->getAttr('id'));
            $v->setOperations($operations);
        });
        return $table;
    }

    public function getDownload(Response $res, Url $url): Response
    {
        $mail = $this->service->read($url->getSegment(2));
        return $res
            ->setBody($mail->content)
            ->withHeader('Content-Type', 'message/rfc822')
            ->withHeader('Content-Disposition', 'attachment; filename=dump.eml')
            ->withHeader('Content-Length', (string)strlen($mail->content));
    }
    public function getCreate(): Response
    {
        throw new \Exception('Not allowed', 400);
    }
    public function postCreate(): Response
    {
        throw new \Exception('Not allowed', 400);
    }
}
