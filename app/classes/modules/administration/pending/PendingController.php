<?php

declare(strict_types=1);

namespace modules\administration\pending;

use helpers\html\Button;
use helpers\html\Field;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use vakata\collection\Collection;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use modules\common\crud\CRUDController;
use modules\common\crud\CRUDException;
use vakata\database\schema\Entity;

class PendingController extends CRUDController
{
    private PendingService $service;

    public function __construct(Request $request, Response $response, Views $views, PendingService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
        if (!$views->getFolders()->exists('pending')) {
            $views->addFolder('pending', __DIR__ . '/views');
        }
    }

    protected function getRow(Entity $entity, mixed $id): TableRow
    {
        $row = parent::getRow($entity, $id);
        $row->addOperation(
            (new Button("user"))
                ->setLabel($this->module . '.operations.user')
                ->setIcon('user plus')
                ->setClass('mini orange icon button')
                ->setAttr('href', $this->module . '/user/' . $id)
        );
        $operations = $row->getOperations();
        $operations = [
            //'read' => $operations['read']->show(),
            'user' => $operations['user']->show()
        ];
        $row->setOperations($operations);
        return $row;
    }

    protected function getTable(iterable $entities): Table
    {
        $table = parent::getTable($entities);
        $table->setOperations([]);
        $table
            ->removeColumn('usrpend');
        $table->getColumn('details')->setMap(function (string $v): string {
            $v = json_decode($v, true) ?? [];
            $v = [ $v['name'] ?? '', $v['mail'] ?? ''];
            return implode(' / ', array_filter($v));
        });
        Collection::from($table->getRows())->each(function (TableRow $v) {
            $operations = $v->getOperations();
            // $operations = [
            //     'read' => $operations['read']
            // ];
            $v->setOperations($operations);
        });
        return $table;
    }

    protected function getForm(): Form
    {
        $form = parent::getForm();
        $form
            ->removeField('usrpend');
        $form->getField('details')->setType('textarea');
        $form->setLayout([
            ['provider', 'id', 'created'],
            ['name', 'mail'],
            ['details']
        ]);
        return $form;
    }
    public function getDelete(): Response
    {
        throw new \Exception('Not implemented', 404);
    }
    public function postDelete(): Response
    {
        throw new \Exception('Not implemented', 404);
    }
    public function getUser(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->url->getSegment(0) . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0)))
            );
        }
        $form = new Form();
        $form->addField(
            new Field(
                'module',
                [ 'name' => 'user' ],
                [
                    'label' => $this->module . '.columns.user',
                    'url' => 'users',
                    'id' => 'usr',
                    'multiple' => false
                ]
            )
        );
        return $this->response->setBody(
            $this->views->render('pending::user', [
                'user' => $entity,
                'form' => $form,
                'back' => $this->url->linkTo(
                    $this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0))
                )
            ])
        );
    }
    public function postUser(Request $req): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->url->getSegment(0) . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0)))
            );
        }
        if ($req->getPost('user_add')) {
            try {
                $uid = $this->service->auth($req->getPost('user', 0, 'int'), $entity);
                //return $this->response->withHeader('Location', $this->url->linkTo('users/update/' . $uid));
            } catch (\Throwable $e) {
                $this->session->set('error', $this->url->getSegment(0) . '.messages.tryagain');
                return $this->response->withHeader(
                    'Location',
                    (string)$this->url
                );
            }
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0)))
            );
        }
        try {
            $uid = $this->service->user($entity);
            return $this->response->withHeader('Location', $this->url->linkTo('users/update/' . $uid));
        } catch (\Throwable $e) {
            $this->session->set('error', $this->url->getSegment(0) . '.messages.exists');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0)))
            );
        }
    }
}
