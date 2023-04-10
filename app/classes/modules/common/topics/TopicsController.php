<?php

declare(strict_types=1);

namespace modules\common\topics;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use helpers\html\Field;
use helpers\html\TableColumn;
use helpers\html\HTML;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use modules\common\crud\CRUDController;
use modules\common\crud\CRUDException;
use vakata\collection\Collection;

class TopicsController extends CRUDController
{
    private TopicsService $service;

    public function __construct(Request $request, Response $response, Views $views, TopicsService $service)
    {
        parent::__construct($request, $response, $views, $service);
        if (!$views->getFolders()->exists('topics')) {
            $views->addFolder('topics', __DIR__ . '/views');
        }
        $this->service = $service;
    }

    protected function getTable(iterable $entities): Table
    {
        $table = parent::getTable($entities);
        $table
            ->removeColumn('topic')
            ->removeColumn('hidden')
            ->removeColumn('content')
            ->removeColumn('files')
            ->removeColumn('usr')
            ->removeColumn('forum')
            ->removeColumn('created');
        $table
            ->getColumn('locked')
                ->setMap(function (mixed $v) {
                    $types = ['lock open', 'lock'];
                    return new HTML(
                        '<i class="ui ' . $types[$v] . ' icon"></i>'
                    );
                });
        $table
            ->addColumn(
                (new TableColumn('forums.name'))
                    ->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "select",
                                [ 'name' => 'forums.forum[]' ],
                                [ 'label' => $this->module . '.filters.forum', 'values' => $this->service->getForums() ]
                            ))
                    )
            );
        $columns = [
            $table->getColumn('forums.name'),
            $table->getColumn('name'),
            $table->getColumn('updated'),
            $table->getColumn('locked')
        ];
        $table->setColumns($columns);
        Collection::from($table->getRows())->each(function (TableRow $v) {
            if ($v->hidden) {
                $v->addClass('error');
            }
            $v->removeOperation('delete');
            if (!$this->service->isModerator()) {
                $v->removeOperation('update');
            }
        });
        return $table;
    }

    public function getFilters(): array
    {
        $filters = [];
        foreach ($this->service->getForums() as $k => $v) {
            $filters[] = [ 'search' => '?forum=' . $k, 'name' => $v ];
        }
        return count($filters) > 1 ? $filters : [];
    }

    protected function getForm(): Form
    {
        $form = parent::getForm();
        $form->removeField('topic');
        $form->removeField('created');
        $form->removeField('updated');
        $form->removeField('usr');
        if (!$this->service->isModerator()) {
            $form->removeField('hidden');
            $form->removeField('locked');
        } else {
            $form->getField('hidden')->setType('checkbox');
            $form->getField('locked')->setType('checkbox');
        }
        $form->getField('forum')->setType('select')
            ->setOption('values', $this->service->getForums(false));
        $form->getField('content')->setType('richtext');
        $form->getField('files')->setType('files');
        return $form;
    }
    protected function getCreateForm(array $data = []): Form
    {
        $form = parent::getCreateForm($data);
        $form->getField('forum')->setOption('values', $this->service->getForums(true));
        return $form;
    }
    public function postRead(): Response
    {
        $this->service->postReply([
            'topic' => $this->url->getSegment(2),
            'files' => $this->request->getPost('files'),
            'content' => $this->request->getPost('content')
        ]);
        return $this->response->withHeader('Location', $this->url->self());
    }
    public function postFollow(): Response
    {
        $this->service->addStar((int)$this->url->getSegment(2));
        return $this->response->withHeader(
            'Location',
            $this->url->get($this->url->getSegment(0) . '/read/' . $this->url->getSegment(2))
        );
    }
    public function postUnfollow(): Response
    {
        $this->service->removeStar((int)$this->url->getSegment(2));
        return $this->response->withHeader(
            'Location',
            $this->url->get($this->url->getSegment(0) . '/read/' . $this->url->getSegment(2))
        );
    }
    public function postHide(): Response
    {
        $topic = $this->service->hideReply((int)$this->url->getSegment(2));
        return $this->response->withHeader(
            'Location',
            $this->url->get($this->url->getSegment(0) . '/read/' . $topic)
        );
    }
    public function postShow(): Response
    {
        $topic = $this->service->showReply((int)$this->url->getSegment(2));
        return $this->response->withHeader('Location', $this->url->get($this->url->getSegment(0) . '/read/' . $topic));
    }

    public function getAjax(): Response
    {
        $result = $this->service->getUnread();
        $result = array_map(function (array $v) {
            $u = strtotime($v['updated']);
            $s = strtotime($v['seen']);
            //$v['link'] = strlen($v['link']) ? $v['link'] : 'notifications/read/' . $v['notification'];
            //$v['link'] = strpos($v['link'], '//') !== false ? $v['link'] : $url->linkTo($v['link']);
            $v['link'] = $this->url->linkTo('topics/read/' . $v['topic']);
            $v['sent'] = date('d.m.Y H:i', $u);
            $v['unread'] = $u > $s;
            return $v;
        }, $result);
        return $this->response
            ->setContentTypeByExtension('json')
            ->setBody(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '');
    }

    public function getRead(): Response
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

        $row = $this->getRow($entity, null);

        $form = $this->getReadForm($entity);
        return $this->response->setBody(
            $this->views->render(
                $this->getView('read'),
                [
                    'form'       => $form,
                    'entity'     => $entity,
                    'moderator'  => $this->service->isModerator(),
                    'pkey'       => $this->service->getID($entity),
                    'title'      => $this->url->getSegment(0) . '.titles.read',
                    'icon'       => 'eye',
                    'breadcrumb' => $this->url->getSegment(0) . '.breadcrumb.read',
                    'back'       => $this->url->linkTo(
                        $this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0))
                    ),
                    'update'     => $row->hasOperation('update'),
                    'delete'     => $row->hasOperation('delete')
                ]
            )
        );
    }
}
