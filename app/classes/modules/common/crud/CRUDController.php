<?php

declare(strict_types=1);

namespace modules\common\crud;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\http\Uri as Url;
use helpers\html\Form as Form;
use helpers\html\Field as Field;
use helpers\html\Button as Button;
use helpers\html\Table as Table;
use helpers\html\TableRow as TableRow;
use helpers\html\TableColumn as TableColumn;
use League\Plates\Engine as Views;
use vakata\session\Session;
use vakata\collection\Collection;
use vakata\files\FileStorageInterface as FSI;
use vakata\spreadsheet\Reader;
use vakata\intl\Intl;
use DateTime;
use vakata\config\Config;
use vakata\database\schema\Entity;
use vakata\spreadsheet\Writer;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class CRUDController
{
    protected Request $request;
    protected Response $response;
    protected Url $url;
    protected Session $session;
    protected string $module;
    protected Views $views;
    private CRUDServiceInterface $service;

    public function __construct(Request $request, Response $response, Views $views, CRUDServiceInterface $service)
    {
        $this->request = $request;
        $this->response = $response;
        $this->url = $this->request->getUrl();
        $this->module = $this->url->getSegment(0);
        $this->service = $service;
        $this->views = $views;
        $this->session = $this->request->getAttribute('session') ?? new Session(false);
    }

    protected function getView(string $name): string
    {
        try {
            if ($this->views->exists($this->module . '::' . $name)) {
                return $this->module . '::' . $name;
            }
        } catch (\Exception $ignore) {
        }
        return 'crud::' . $name;
    }
    protected function getForm(): Form
    {
        $form = new Form();
        $cols = Collection::from($this->service->getFields())->mapKey(function (array $v, string $k) {
            return strtolower($k);
        });
        foreach ($cols as $k => $v) {
            if (in_array($k, ['usr', 'grp', '_created', '_updated', 'org', 'site', '_status'])) {
                continue;
            }
            switch ($v['type']) {
                case 'date':
                    $form->addField(
                        new Field('date', [ 'name' => $k ], [ 'label' => $this->module . '.columns.' . $k ])
                    );
                    break;
                case 'datetime':
                    $form->addField(
                        new Field('datetime', [ 'name' => $k ], [ 'label' => $this->module . '.columns.' . $k ])
                    );
                    break;
                case 'enum':
                    $form->addField(
                        new Field(
                            'select',
                            [ 'name' => $k ],
                            [ 'label' => $this->module . '.columns.' . $k, 'values' => $v['values'] ]
                        )
                    );
                    break;
                case 'int':
                    // do not use field type number! getting the value in JS is faulty
                    $form->addField(
                        new Field('text', [ 'name' => $k ], [ 'label' => $this->module . '.columns.' . $k ])
                    );
                    break;
                default:
                    $text = new Field('text', [ 'name' => $k ], [ 'label' => $this->module . '.columns.' . $k ]);
                    if ($v['length']) {
                        $text->setAttr('maxlength', $v['length']);
                    }
                    $form->addField($text);
                    break;
            }
        }
        if ($this->service instanceof CRUDServiceAdvanced && isset($cols['_status'])) {
            $form->addField(
                new Field(
                    'select',
                    [ 'name' => '_status' ],
                    [
                        'label' => $this->module . '.columns._status',
                        'values' => $this->service->canPublish() ?
                            [
                                'draft' => 'draft',
                                'pending' => 'pending',
                                'published' => 'published',
                                'rejected' => 'rejected'
                            ] :
                            [
                                'draft' => 'draft',
                                'pending' => 'pending'
                            ]
                    ]
                )
            );
        }
        return $form;
    }
    protected function getTable(iterable $entities): Table
    {
        $table = new Table();
        $table->addOperation(
            (new Button("create"))
                ->setLabel($this->module . '.operations.create')
                ->setIcon('plus')
                ->setClass('green icon labeled button')
                ->setAttr('href', $this->module . '/create')
        );
        $table->addOperation(
            (new Button("import"))
                ->setIcon('download')
                ->setClass('yellow icon button')
                ->setAttr('href', $this->module . '/import')
                ->hide()
        );
        $table->addOperation(
            (new Button("export"))
                ->setIcon('upload')
                ->setClass('olive icon button export-button')
                ->setAttr('href', $this->module . '/export')
                ->hide()
        );
        $visible = $this->service->listColumns();
        Collection::from($this->service->getFields())
            ->mapKey(function (array $v, string $k) {
                return strtolower($k);
            })
            ->filter(function (array $v, string $k) use ($visible) {
                return in_array($k, $visible);
            })
            ->map(function (array $v, string $k): TableColumn {
                $column = new TableColumn($k);
                if ($v['type'] === 'date') {
                    $column->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "date",
                                ['name' => $v['name'] . '[beg]'],
                                ['label' => $this->module . '.filters.' . $v['name'] . '.beg' ]
                            ))
                            ->addField(new Field(
                                "date",
                                ['name' => $v['name'] . '[end]'],
                                ['label' => $this->module . '.filters.' . $v['name'] . '.end' ]
                            ))
                            ->setLayout([[$v['name'] . '[beg]', $v['name'] . '[end]']])
                    );
                }
                if ($v['type'] === 'datetime') {
                    $column->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "datetime",
                                ['name' => $v['name'] . '[beg]'],
                                ['label' => $this->module . '.filters.' . $v['name'] . '.beg' ]
                            ))
                            ->addField(new Field(
                                "datetime",
                                ['name' => $v['name'] . '[end]'],
                                ['label' => $this->module . '.filters.' . $v['name'] . '.end' ]
                            ))
                            ->setLayout([[$v['name'] . '[beg]', $v['name'] . '[end]']])
                    );
                }
                if ($v['type'] === 'enum') {
                    $column->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "multipleselect",
                                ['name' => $v['name'] . '[]'],
                                [
                                    'label' => $this->module . '.filters.' . $v['name'],
                                    'values' => array_combine($v['values'], $v['values'])
                                ]
                            ))
                    );
                }
                return $column;
            })
            ->each(function (TableColumn $v) use ($table) {
                $table->addColumn($v);
            });
        Collection::from($entities)
            ->each(function (Entity $v, mixed $k) use ($table) {
                $table->addRow(
                    $this->getRow($v, $k)
                );
            });
        return $table;
    }
    protected function getRow(Entity $entity, mixed $id): TableRow
    {
        $row = (new TableRow($entity))
            ->setAttr('id', $id)
            ->addOperation(
                (new Button("read"))
                    ->setLabel($this->module . '.operations.read')
                    ->setIcon('eye')
                    ->setClass('mini teal icon button')
                    ->setAttr('href', $this->module . '/read/' . $id)
            );
        if (!($this->service instanceof CRUDServiceAdvanced) || $this->service->canUpdate($entity)) {
            $row->addOperation(
                (new Button("update"))
                    ->setLabel($this->module . '.operations.update')
                    ->setIcon('pencil')
                    ->setClass('mini orange icon button')
                    ->setAttr('href', $this->module . '/update/' . $id)
            );
        }
        $row
            ->addOperation(
                (new Button("copy"))
                    ->setLabel($this->module . '.operations.copy')
                    ->setIcon('copy')
                    ->setClass('mini purple icon button')
                    ->setAttr('href', $this->module . '/copy/' . $id)
                    ->hide()
            )
            ->addOperation(
                (new Button("delete"))
                    ->setLabel($this->module . '.operations.delete')
                    ->setIcon('remove')
                    ->setClass('mini red icon button')
                    ->setAttr('href', $this->module . '/delete/' . $id)
            );
        if ($this->service instanceof CRUDServiceVersionedInterface) {
            $row->addOperation(
                (new Button("history"))
                    ->setLabel($this->module . '.operations.history')
                    ->setIcon('history')
                    ->setClass('mini grey icon button')
                    ->setAttr('href', $this->module . '/history/' . $id)
                    ->hide()
            );
        }
        return $row;
    }

    public function getIndex(): Response
    {
        $query = $this->request->getQuery();
        if (isset($query['l']) && $query['l'] === 'all') {
            unset($query['l']);
        }
        $params = array_merge($this->service->listDefaults(), $query);
        $entities = $this->service->list($params);
        $count    = count($entities);
        $entities = Collection::from($entities->iterator())
            ->mapKey(function (Entity $v) {
                return implode('|', $this->service->getID($v));
            })
            ->toArray();
        $table    = $this->getTable($entities)->addClass('basic selectable compact table-read');
        $filtered = Collection::from($this->service->getFields(true))
                ->merge(array_keys($table->getColumns()))
                ->add("q")
                ->reduce(function (bool $carry, string $v) use ($params): bool {
                    return $carry || (
                        isset($params[$v]) &&
                        (
                            (is_string($params[$v]) && strlen($params[$v])) ||
                            is_array($params[$v])
                        )
                    );
                }, false);

        if ($filtered) {
            $table->addClass('attached');
        }
        if (isset($params['o']) && $table->hasColumn($params['o'])) {
            //$table->getColumn($params['o'])->addClass('active');
        }

        foreach ($table->getColumns() as $column) {
            if ($column->hasFilter()) {
                $column->getFilter()->populate($params);
            }
        }

        if (!$this->request->isAjax()) {
            $this->session->set($this->module . '.index', $this->url->self());
        }
        return $this->response->setBody(
            $this->views->render(
                $this->getView('index'),
                [
                    'module'     => $this->module,
                    'views'      => [
                        'actions'   => $this->getView('index_actions'),
                        'filters'   => $this->getView('index_filters'),
                        'table'     => $this->getView('index_table'),
                        'table_row' => $this->getView('index_table_row'),
                    ],
                    'params'     => $params,
                    'count'      => $count,
                    'table'      => $table,
                    'hidden'     => Collection::from($table->getColumns())
                        ->filter(function (TableColumn $v) {
                            return $v->isHidden();
                        })
                        ->map(function (TableColumn $v): string {
                            return $v->getName();
                        })
                        ->values()
                        ->toArray(),
                    'filtered'   => $filtered,
                    'filters'    => $this->getFilters(),
                    'created'    => $this->session->get('success') === $this->module . '.messages.created'
                ]
            )
        );
    }
    protected function getFilters(): array
    {
        return [];
    }
    public function postIndex(Intl $intl, Config $config): Response
    {
        $query = $this->request->getQuery();
        if (isset($query['l']) && $query['l'] === 'all') {
            unset($query['l']);
        }
        $params   = array_merge($this->service->listDefaults(), $query);
        $data     = $this->request->getPost();
        if (!isset($data['current_page_only'])) {
            $params['p'] = 1;
            $params['l'] = 'all';
        }
        $format = $data['format'] ?? '';
        $entities = $this->service->list($params);
        $fields = Collection::from($this->getForm()->getFields())
            ->filter(function (Field $v) {
                return $v->getType() !== 'hidden';
            })
            ->mapKey(function (Field $v) {
                return $v->getName();
            })
            ->map(function (Field $v) use ($intl) {
                return $intl($v->getOption('label', $this->module . '.' . $v->getName()));
            })
            ->toArray();
        $columns = [];
        if (!isset($data['all_columns']) && isset($data['columns'])) {
            foreach (array_filter(explode(',', $data['columns'])) as $column) {
                if (isset($fields[$column])) {
                    $columns[$column] = $fields[$column];
                }
            }
        } else {
            $columns = $fields;
        }

        foreach (Writer::headers($format, 'export.' . $format) as $k => $v) {
            $this->response = $this->response->withHeader($k, $v);
        }
        return $this->response
            ->withCallback(function () use ($format, $columns, $entities, $config) {
                // excel:true is for the csv writer Excel compatibility
                $writer = Writer::toBrowser(
                    $format,
                    [ 'temp' => $config->get('STORAGE_TMP'), 'excel' => true, 'defaultSheet' => 'Sheet1' ]
                );
                $writer->getDriver()
                    ->addRow(array_values($columns));
                $writer->fromIterable(
                        Collection::from($entities->getIterator())
                            ->map(function (Entity $v) use ($columns) {
                                $row = [];
                                foreach (array_keys($columns) as $column) {
                                    $row[$column] = $v->{$column} ?? null;
                                }
                                return $row;
                            })
                    );
            });
    }
    protected function getReadForm(?Entity $entity = null): Form
    {
        return $this->getUpdateForm($entity)->validate(null)->disable();
    }
    public function getRead(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->module . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
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
                    'pkey'       => $this->service->getID($entity),
                    'title'      => $this->module . '.titles.read',
                    'icon'       => 'eye',
                    'breadcrumb' => $this->module . '.breadcrumb.read',
                    'back'       => $this->url->linkTo($this->session->get($this->module . '.index', $this->module)),
                    'update'     => $row->hasOperation('update'),
                    'delete'     => $row->hasOperation('delete'),
                    'history'    => $row->hasOperation('history') &&
                        ($this->service instanceof CRUDServiceVersionedInterface)
                ]
            )
        );
    }
    protected function getHistoryForm(Entity $entity = null): Form
    {
        return $this->getReadForm($entity);
    }
    public function getHistory(): Response
    {
        if (!($this->service instanceof CRUDServiceVersionedInterface)) {
            $this->session->set('error', $this->module . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
            );
        }

        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->module . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
            );
        }
        $versions = $this->service->versions($entity);
        return $this->response->setBody(
            $this->views->render(
                $this->getView('history'),
                [
                    'versions'   => Collection::from($versions)
                        ->map(function (array $v) {
                            return [
                                'form'    => $this->getHistoryForm()->populate(json_decode($v['entity'])),
                                'author'  => $v['usr_name'],
                                'created' => ($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v['created'])) ?
                                    $temp->format('d.m.Y H:i:s') : ''
                            ];
                        })
                        ->reverse()
                        ->toArray(),
                    'pkey'       => $this->service->getID($entity),
                    'title'      => $this->module . '.titles.history',
                    'icon'       => 'clock',
                    'breadcrumb' => $this->module . '.breadcrumb.history',
                    'back'       => $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
                ]
            )
        );
    }
    protected function getCreateForm(array $data = []): Form
    {
        return $this->getForm()
            ->populate($data)
            ->validate($this->service->getValidator(true));
    }
    public function getCreate(): Response
    {
        $form = $this->getForm();

        $referer = parse_url($this->request->getHeaderLine('Referer'), PHP_URL_QUERY);
        $referer = $referer ? Request::fixedQueryParams($referer) : [];
        $multiTypes = ['checkboxes', 'files', 'images', 'multipleselect', 'tags', 'tree'];
        $invalidTypes = ['comments', 'hidden', 'password'];
        foreach ($referer as $k => $v) {
            $type = $form->hasField($k) ? $form->getField($k)->getType() : null;
            if (!$type || strpos($k, '.') !== false || in_array($type, $invalidTypes)) {
                unset($referer[$k]);
                continue;
            }
            if (is_array($v) && !in_array($type, $multiTypes)) {
                $referer[$k] = array_values($v)[0] ?? '';
            }
        }

        $form = $this->getCreateForm($this->session->del($this->module . '.create') ?? $referer);

        return $this->response->setBody(
            $this->views->render(
                $this->getView('create'),
                [
                    'form'       => $form,
                    'title'      => $this->module . '.titles.create',
                    'icon'       => 'plus',
                    'breadcrumb' => $this->module . '.breadcrumb.create',
                    'back'       => $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
                ]
            )
        );
    }
    public function postCreate(): Response
    {
        try {
            $data = $this->request->getPost();
            $entity = $this->service->insert($data);
        } catch (CRUDException $e) {
            $errors = Collection::from($e->getErrors())->pluck('message')->toArray();
            if (!count($errors)) {
                $errors[] = $e->getMessage();
            }
            $this->session->set($this->module . '.create', $this->request->getPost());
            $this->session->set('error', $errors);
            $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
            return $this->response->withHeader('Location', (string)$this->request->getUri());
        }
        $this->session->del($this->module . '.create');
        $this->session->set('success', $this->module . '.messages.created');
        $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
        return $this->response->withHeader(
            'Location',
            (int)$this->request->getPost('redirect_to_id') ?
                $this->url->linkTo($this->module, $this->service->getID($entity)) :
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
        );
    }
    protected function getUpdateForm(?Entity $entity = null, array $data = []): Form
    {
        return $this->getForm()
            ->populate($entity)
            ->populate($data)
            ->validate($this->service->getValidator());
    }
    public function getUpdate(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->module . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
            );
        }

        $form = $this->getUpdateForm($entity, $this->session->del($this->module . '.update') ?? []);

        return $this->response->setBody(
            $this->views->render(
                $this->getView('update'),
                [
                    'form' => $form,
                    'pkey' => $this->service->getID($entity),
                    'entity' => $entity,
                    'title' => $this->module . '.titles.update',
                    'icon' => 'pencil',
                    'breadcrumb' => $this->module . '.breadcrumb.update',
                    'back' => $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
                ]
            )
        );
    }
    public function postUpdate(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->module . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
            );
        }

        try {
            $data = $this->request->getPost();
            $this->service->update($this->service->getID($entity), $data);
        } catch (CRUDException $e) {
            $errors = Collection::from($e->getErrors())->pluck('message')->toArray();
            if (!count($errors)) {
                $errors[] = $e->getMessage();
            }
            $this->session->set($this->module . '.update', $this->request->getPost());
            $this->session->set('error', $errors);
            $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
            return $this->response->withHeader('Location', (string)$this->request->getUri());
        }
        $this->session->del($this->module . '.update');
        $this->session->set('success', $this->module . '.messages.update');
        $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
        return $this->response->withHeader(
            'Location',
            $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
        );
    }
    public function postPatch(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            return $this->response
                ->setContentTypeByExtension('json')
                ->setBody(json_encode(['errors' => [ $this->module . '.messages.notfound']]) ?: '')
                ->withStatus(404);
        }

        try {
            $data = $this->request->getPost();
            if (!is_array($data) || !isset($data['column']) || !isset($data['value'])) {
                throw new CRUDException('Invalid input');
            }
            $temp = [];
            $temp[$data['column']] = $data['value'];
            $entity = $this->service->patch($this->service->getID($entity), $temp);
        } catch (CRUDException $e) {
            $errors = Collection::from($e->getErrors())->pluck('message')->toArray();
            if (!count($errors)) {
                $errors[] = $e->getMessage();
            }
            return $this->response
                ->setContentTypeByExtension('json')
                ->setBody(json_encode(['errors' => $errors]) ?: '')
                ->withStatus(400);
        }
        return $this->response
            ->setContentTypeByExtension('json')
            ->setBody(json_encode(['value' => $entity->{$data['column']}]) ?: '')
            ->withStatus(200);
    }
    protected function getDeleteForm(?Entity $entity = null): Form
    {
        return $this->getUpdateForm($entity)->validate(null)->disable();
    }
    public function getDelete(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->module . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
            );
        }

        $form = $this->getDeleteForm($entity);

        return $this->response->setBody(
            $this->views->render(
                $this->getView('delete'),
                [
                    'form'       => $form,
                    'pkey'       => $this->service->getID($entity),
                    'entity'     => $entity,
                    'title'      => $this->module . '.titles.delete',
                    'icon'       => 'trash',
                    'breadcrumb' => $this->module . '.breadcrumb.delete',
                    'back'       => $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
                ]
            )
        );
    }
    public function postDelete(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->module . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
            );
        }

        try {
            $this->service->delete($this->service->getID($entity));
        } catch (CRUDException $e) {
            $errors = Collection::from($e->getErrors())->pluck('message')->toArray();
            if (!count($errors)) {
                $errors[] = $e->getMessage();
            }
            $this->session->set('error', $errors);
            $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
            return $this->response->withHeader('Location', (string)$this->request->getUri());
        }
        $this->session->set('success', $this->module . '.messages.deleted');
        $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
        return $this->response->withHeader(
            'Location',
            $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
        );
    }
    protected function getCopyForm(Entity $entity = null, array $data = []): Form
    {
        return $this->getUpdateForm($entity, $data);
    }
    public function getCopy(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            $this->session->set('error', $this->module . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
            );
        }

        $form = $this->getCopyForm($entity, $this->session->del($this->module . '.copy') ?? []);

        return $this->response->setBody(
            $this->views->render(
                $this->getView('copy'),
                [
                    'form'       => $form,
                    'pkey'       => $this->service->getID($entity),
                    'entity'     => $entity,
                    'title'      => $this->module . '.titles.copy',
                    'icon'       => 'copy',
                    'breadcrumb' => $this->module . '.breadcrumb.copy',
                    'back'       => $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
                ]
            )
        );
    }
    public function postCopy(): Response
    {
        try {
            $data = $this->request->getPost();
            $this->service->insert($data);
        } catch (CRUDException $e) {
            $errors = Collection::from($e->getErrors())->pluck('message')->toArray();
            if (!count($errors)) {
                $errors[] = $e->getMessage();
            }
            $this->session->set($this->module . '.copy', $this->request->getPost());
            $this->session->set('error', $errors);
            $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
            return $this->response->withHeader('Location', (string)$this->request->getUri());
        }
        $this->session->del($this->module . '.copy');
        $this->session->set('success', $this->module . '.messages.copied');
        $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
        return $this->response->withHeader(
            'Location',
            $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
        );
    }
    public function getImport(): Response
    {
        $fields = Collection::from($this->getCreateForm()->getFields())
            ->filter(function (Field $v) {
                return $v->getType() !== 'hidden';
            })
            ->mapKey(function (Field $v) {
                return $v->getName();
            })
            ->map(function (Field $v): string {
                return $v->getOption('label', $this->module . '.' . $v->getName());
            })
            ->toArray();
        return $this->response->setBody(
            $this->views->render(
                $this->getView('import'),
                [
                    'form' => (new Form())
                        ->addField(new Field(
                            "file",
                            ['name' => 'import'],
                            ['label' => $this->module . '.import' ]
                        )),
                    'fields' => $fields,
                    'title' => $this->module . '.titles.import',
                    'icon' => 'upload',
                    'breadcrumb' => $this->module . '.breadcrumb.import',
                    'errors' => $this->session->del('import_errors'),
                    'back' => $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
                ]
            )
        );
    }
    public function postImport(FSI $files): Response
    {
        try {
            $data   = $this->request->getPost();
            if (!is_array($data) || !isset($data['import'])) {
                throw new CRUDException('Invalid input');
            }
            $file   = $files->get($data['import']);
            $name   = $file->name();
            $first  = true;
            $fields = Collection::from($this->getCreateForm()->getFields())
                ->mapKey(function (Field $v) {
                    return $v->getName();
                })
                ->map(function (Field $v) {
                    return $this->module . '.columns.' . $v->getName();
                })
                ->toArray();
            $errors = [];
            // TODO: fix for using a central document store
            foreach ((new Reader($file->path() ?: throw new \RuntimeException(), $file->ext())) as $k => $row) {
                if (isset($data['skip_first']) && $first) {
                    $first = false;
                    continue;
                }
                $first = false;
                $temp = [];
                foreach ($data['columns'] as $ind => $name) {
                    if (!$name || !isset($fields[$name])) {
                        continue;
                    }
                    $temp[$name] = $row[$ind] ?? null;
                }
                try {
                    $this->service->insert($temp);
                } catch (CRUDException $e) {
                    $temp = Collection::from($e->getErrors())
                        ->pluck('message')
                        ->values()
                        ->toArray();
                    if (!count($temp)) {
                        $temp[] = $e->getMessage();
                    }
                    $errors[$k] = $temp;
                    if (count($errors) >= 5) {
                        break;
                    }
                }
            }
            if (count($errors)) {
                $this->session->set('import_errors', $errors);
                return $this->response->withHeader('Location', (string)$this->request->getUri());
            }
        } catch (\Exception $e) {
            $this->session->set('error', ['import.error']);
            $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
            return $this->response->withHeader('Location', (string)$this->request->getUri());
        }
        $this->session->set('success', $this->module . '.messages.imported');
        $this->session->set('removeLS', 'local:/' . trim($this->url->getPath(), '/'));
        return $this->response->withHeader(
            'Location',
            $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
        );
    }
    public function getJSON(): Response
    {
        $field  = $this->request->getQuery('field', null);
        $entity = $this->request->getQuery('id');
        if ($entity) {
            $entity = explode(',', $entity);
            $result = [];
            foreach ($entity as $e) {
                $temp = $this->service->read($e);
                $id   = $this->service->getID($temp);
                $result[] = [ 'name' => $temp->{$field} ?? null, 'value' => count($id) === 1 ? current($id) : $id ];
            }
            $result = [ 'success' => true, 'results' => $result ];
        } else {
            $params = array_merge($this->service->listDefaults(), $this->request->getQuery());
            $result = Collection::from($this->service->list($params))
                ->mapKey(function (Entity $v) {
                    return implode('|', $this->service->getID($v));
                });
            if ($field) {
                $result = $result
                    ->map(function (Entity $v) use ($field) {
                        $id = $this->service->getID($v);
                        return [ 'name' => $v->{$field} ?? null, 'value' => count($id) === 1 ? current($id) : $id ];
                    })
                    ->values()
                    ->toArray();
            } else {
                $result = $result->toArray();
            }
            $result = [ 'success' => true, 'results' => $result ];
        }
        return $this->response
            ->setContentTypeByExtension('json')
            ->setBody(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '');
    }
    public function postRedraw(): Response
    {
        $form = (int)$this->url->getSegment(2) ?
            $this->getUpdateForm($this->service->read($this->url->getSegment(2)), $this->request->getPost()) :
            $this->getCreateForm($this->request->getPost());
        return $this->response->setBody(
            $this->views->render('common/form', [ 'form' => $form ])
        );
    }
    /*
    public function getCreateForm(array $data = [])
    {
        $form = parent::getCreateForm($data);
        $form->getField('title')->setAttr('data-redraw', '1');
        if (isset($data['title'])) {
            $form->getField('title')->setValue('123');
        }
        return $form;
    }
     */
}
