<?php

declare(strict_types=1);

namespace modules\common\notifications;

use DateTime;
use vakata\collection\Collection;
use helpers\html\Field as Field;
use helpers\html\HTML as HTML;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use modules\common\crud\CRUDController;
use modules\common\crud\CRUDException;
use vakata\database\schema\Entity;

class NotificationsController extends CRUDController
{
    private NotificationsService $service;

    public function __construct(Request $request, Response $response, Views $views, NotificationsService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
        if (!$views->getFolders()->exists('notifications')) {
            $views->addFolder('notifications', __DIR__ . '/views');
        }
    }

    protected function getTable(iterable $entities): Table
    {
        $recpt = $this->service->getAvailableRecipients();
        $table = parent::getTable($entities);
        if (!count($recpt)) {
            $table->getOperation('create')->hide();
        }
        $table
            ->removeColumn('notification')
            ->removeColumn('thread')
            ->removeColumn('body')
            ->removeColumn('link')
            ->removeColumn('files')
            ->removeColumn('reply')
            ->removeColumn('mail');
        $table
            ->getColumn('sent')
                ->addClass('left aligned')
                ->setMap(function (mixed $v) {
                    return new HTML(
                        '<i class="ui clock icon"></i> ' .
                        ((($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                            $temp->format('d.m.Y H:i:s') : '')
                        )
                    );
                });
        $table
            ->getColumn('sender')
                ->addClass('left aligned')
                ->setMap(function (mixed $v, Entity $data) {
                    if ($v === $this->service->getUser()) {
                        return new HTML('<i class="ui share icon"></i> ' . $data->users->name);
                    }
                    return $v ?
                        new HTML('<i class="ui user icon"></i> ' . $data->users->name) :
                        new HTML('<i class="ui server icon"></i> <strong>*</strong>');
                });
        return $table;
    }
    protected function getRow(Entity $entity, mixed $id): TableRow
    {
        $v = parent::getRow($entity, $id);
        $operations = $v->getOperations();
        $temp = [];
        $temp['read'] = $operations['read']->show();
        $v->setOperations($temp);
        if ($v->getData()->sender === $this->service->getUser()) {
            $v->addClass('warning');
        } elseif (!$v->getData()->notification_recipients[0]->opened) {
            $v->addClass('positive');
        }
        return $v;
    }

    protected function getForm(): Form
    {
        $layout = [
                [ 'body' ],
                [ 'files' ],
            ];
        $form = parent::getForm();
        $form->removeField('notification');
        $form->getField('body')->setType('textarea');
        $form->getField('thread')->setType('hidden');
        $form->getField('files')->setType('files');
        $form->getField('reply')->setType('checkbox');
        return $form->setLayout($layout);
    }
    protected function getCreateForm(array $data = []): Form
    {
        $recpt = $this->service->getAvailableRecipients();
        $layout = [
            [ 'recipients[]' ],
            [ 'title' ],
            [ 'body' ],
            [ 'files' ],
            [ 'reply' ],
        ];
        $form = parent::getCreateForm($data);
        $form->removeField('notification');
        $form->getField('body')->setType('textarea');
        $form->getField('thread')->setType('hidden');
        $form->getField('files')->setType('files');
        $form->getField('reply')->setType('checkbox');
        $form->addField(
            new Field(
                'multipleselect',
                ['name' => 'recipients[]'],
                ['label' => 'notifications.columns.recipient', 'values' => ['' => ''] + $recpt]
            )
        );
        return $form->setLayout($layout);
    }
    public function getCreate(): Response
    {
        if (!count($this->service->getAvailableRecipients())) {
            throw new \Exception('Not implemented', 404);
        }
        return parent::getCreate();
    }
    public function postCreate(): Response
    {
        if (!count($this->service->getAvailableRecipients())) {
            throw new \Exception('Not implemented', 404);
        }
        return parent::postCreate();
    }
    public function getUpdate(): Response
    {
        throw new \Exception('Not implemented', 404);
    }
    public function postUpdate(): Response
    {
        throw new \Exception('Not implemented', 404);
    }
    public function getDelete(): Response
    {
        throw new \Exception('Not implemented', 404);
    }
    public function postDelete(): Response
    {
        throw new \Exception('Not implemented', 404);
    }
    public function getRead(): Response
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
        if ($this->request->getQuery('follow')) {
            if (strlen($entity->link)) {
                return $this->response->withHeader(
                    'Location',
                    strpos($entity->link, '//') !== false ? $entity->link : $this->url->linkTo($entity->link)
                );
            } else {
                return $this->response->withHeader(
                    'Location',
                    $this->url->linkTo($this->url->getSegment(0) . '/read/' . $entity->notification)
                );
            }
        }
        return $this->response->setBody(
            $this->views->render(
                $this->getView('read'),
                [
                    'form'       => $this->getForm(),
                    'entity'     => $entity,
                    'title'      => $this->url->getSegment(0) . '.titles.read',
                    'icon'       => 'eye',
                    'breadcrumb' => $this->url->getSegment(0) . '.breadcrumb.read',
                    'back'       => $this->url->linkTo(
                        $this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0))
                    ),
                ]
            )
        );
    }
    public function postRead(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->url->getSegment(0) . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo(
                    $this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0))
                )
            );
        }

        try {
            $data = $this->request->getPost();
            $data['thread'] = $entity->thread ?? $entity->notification;
            $this->service->insert($data);
        } catch (CRUDException $e) {
            $errors = Collection::from($e->getErrors())->pluck('message')->toArray();
            if (!count($errors)) {
                $errors[] = $e->getMessage();
            }
            $this->session->set($this->url->getSegment(0) . '.update', $this->request->getPost());
            $this->session->set('error', $errors);
            return $this->response->withHeader('Location', (string)$this->request->getUri());
        }
        $this->session->del($this->url->getSegment(0) . '.update');
        $this->session->set('success', $this->url->getSegment(0) . '.messages.update');
        return $this->response->withHeader('Location', (string)$this->request->getUri());
    }
    public function getAjax(): Response
    {
        $result = $this->service->getNotifications();
        $result = array_map(function (array $v): array {
            //$v['link'] = strlen($v['link']) ? $v['link'] : 'notifications/read/' . $v['notification'];
            //$v['link'] = strpos($v['link'], '//') !== false ? $v['link'] : $url->linkTo($v['link']);
            $v['link'] = $this->url->linkTo('notifications/read/' . $v['notification'], ['follow' => '1']);
            try {
                $v['sent'] = date('d.m.Y H:i', strtotime($v['sent']));
            } catch (\Exception $e) {
                $v['sent'] = '';
            }
            return $v;
        }, $result);
        return $this->response
            ->setContentTypeByExtension('json')
            ->setBody(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '');
    }
}
