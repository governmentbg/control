<?php

declare(strict_types=1);

namespace modules\tcontrol\apilog;

use helpers\html\Field;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use helpers\html\Form;
use helpers\html\HTML;
use helpers\html\Table;
use helpers\html\TableRow;
use modules\common\crud\CRUDController;
use modules\common\crud\CRUDException;
use vakata\database\schema\Entity;

class ApilogController extends CRUDController
{
    private ApilogService $service;

    public function __construct(Request $request, Response $response, Views $views, ApilogService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
    }
    protected function getTable(iterable $entities) : Table
    {
        $table = parent::getTable($entities);
        $table->removeOperation('create');
        $table->removeColumn('request');
        $table->removeColumn('response');
        $siks = $this->service->getSiks();
        $table->getColumn('type')
            ->setFilter(
                (new Form())
                    ->addField(
                        new Field(
                            'select',
                            [ 'name' => 'type' ],
                            [
                                'label'     => $this->module . '.filters.type',
                                'values'    => [ 'auth' => 'auth', 'check' => 'check' ]
                            ]
                        )
                    )
            );
        $table->getColumn('udi')
            ->setFilter(
                (new Form())
                    ->addField(
                        new Field(
                            'text',
                            [ 'name' => 'udi' ],
                            [ 'label'     => $this->module . '.filters.udi' ]
                        )
                    )
            );
        $table->getColumn('sik')
            ->setFilter(
                (new Form())
                    ->addField(
                        new Field(
                            'multipleselect',
                            [ 'name' => 'sik[]' ],
                            [
                                'label'     => $this->module . '.filters.sik',
                                'values'    => $siks
                            ]
                        )
                    )
            )
            ->setMap(function (mixed $value) use ($siks) : string {
                return $siks[$value] ?? '';
            });
        $modes = $this->service->getModes();
        $table->getColumn('mode')
            ->setFilter(
                (new Form())
                    ->addField(
                        new Field(
                            'multipleselect',
                            [ 'name' => 'mode[]' ],
                            [
                                'label'     => $this->module . '.filters.mode',
                                'values'    => $modes
                            ]
                        )
                    )
            )
            ->setMap(function (mixed $value) use ($modes) : string {
                return $modes[$value] ?? '';
            });
        $table->getColumn('err')
            ->setFilter(
                (new Form())
                    ->addField(
                        new Field(
                            'select',
                            [ 'name' => 'err' ],
                            [
                                'label'     => $this->module . '.filters.err',
                                'values'    => [ 'Не', 'Да' ]
                            ]
                        )
                    )
            )
            ->setMap(function (mixed $value) : HTML {
                return new HTML((int) $value ? '<i class="ui icon asterisk"></i>' : '');
            });
        $table->getColumn('created')
            ->setMap(function (mixed $value) : string {
                return date('d.m.Y H:i:s', strtotime($value));
            });

        return $table;
    }
    protected function getRow(Entity $entity, mixed $id): TableRow
    {
        $row = parent::getRow($entity, $id);
        if ((int) $entity->err) {
            $row->addClass('negative');
        }
        $row
            ->removeOperation('update')
            ->removeOperation('delete');

        return $row;
    }
    protected function getForm() : Form
    {
        $form = parent::getForm();
        $form->getField('sik')
            ->setType('select')
            ->setOption('values', $this->service->getSiks());
        $form->getField('mode')
            ->setType('select')
            ->setOption('values', $this->service->getModes());
        $form->getField('request')
            ->setType('textarea');
        $form->getField('response')
            ->setType('textarea');
        $form->getField('err')
            ->setType('checkbox');

        return $form;
    }
    public function getCreate(): never
    {
        throw new CRUDException('Not allowed');
    }
    public function postCreate(): never
    {
        throw new CRUDException('Not allowed');
    }
    public function getUpdate(): never
    {
        throw new CRUDException('Not allowed');
    }
    public function postUpdate(): never
    {
        throw new CRUDException('Not allowed');
    }
    public function getDelete(): never
    {
        throw new CRUDException('Not allowed');
    }
    public function postDelete(): never
    {
        throw new CRUDException('Not allowed');
    }
}
