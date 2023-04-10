<?php

declare(strict_types=1);

namespace modules\tcontrol\modes;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use modules\common\crud\CRUDController;
use modules\common\crud\CRUDException;
use vakata\database\schema\Entity;

class ModesController extends CRUDController
{
    private ModesService $service;

    public function __construct(Request $request, Response $response, Views $views, ModesService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
    }

    protected function getTable(iterable $entities) : Table
    {
        $table = parent::getTable($entities);
        $table
            ->removeColumn('mode');
        $table
            ->getColumn('enabled')
                ->setMap(function ($value) {
                    return (int) $value ? 'Да' : 'Не';
                });
        $table
            ->getColumn('enabled_from')
                ->setMap(function ($value) {
                    return $value ? date('d.m.Y H:i:s', strtotime($value)) : '';
                });
        $table
            ->getColumn('enabled_to')
                ->setMap(function ($value) {
                    return $value ? date('d.m.Y H:i:s', strtotime($value)) : '';
                });

        $table->removeOperation('create');

        return $table;
    }
    protected function getRow(Entity $entity, mixed $id) : TableRow
    {
        $row = parent::getRow($entity, $id);
        $row->removeOperation('delete');

        return $row;
    }
    protected function getUpdateForm(?Entity $entity = null, array $data = []) : Form
    {
        $form = parent::getUpdateForm($entity, $data);
        $form->removeField('mode');
        $form->getField('enabled')
            ->setType('checkbox');
        $form->getField('enabled_from')
            ->setType('datetime');
        $form->getField('enabled_to')
            ->setType('datetime');
        $form->getField('name')
            ->disable();

        return $form
            ->setLayout([
                [ 'name', 'enabled', 'enabled_from', 'enabled_to' ]
            ]);
    }
    public function getDelete() : never
    {
        throw new CRUDException('Not allowed');
    }
    public function postDelete() : never
    {
        throw new CRUDException('Not allowed');
    }
    public function getCreate() : never
    {
        throw new CRUDException('Not allowed');
    }
    public function postCreate() : never
    {
        throw new CRUDException('Not allowed');
    }
}
