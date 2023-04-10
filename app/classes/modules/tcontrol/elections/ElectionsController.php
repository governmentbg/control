<?php

declare(strict_types=1);

namespace modules\tcontrol\elections;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use modules\common\crud\CRUDController;
use modules\common\crud\CRUDException;
use vakata\database\schema\Entity;

class ElectionsController extends CRUDController
{
    private ElectionsService $service;

    public function __construct(Request $request, Response $response, Views $views, ElectionsService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
    }

    protected function getTable(iterable $entities) : Table
    {
        $table = parent::getTable($entities);
        $table
            ->removeColumn('election')
            ->removeColumn('keyenc');
        $table
            ->getColumn('enabled')
                ->setMap(function ($value) {
                    return (int) $value ? 'Да' : 'Не';
                });

        return $table->setOrder([ 'name', 'slug', 'enabled' ]);
    }
    protected function getRow(Entity $entity, mixed $id) : TableRow
    {
        $row = parent::getRow($entity, $id);
        $row->removeOperation('delete');

        return $row;
    }
    protected function getForm() : Form
    {
        $form = parent::getForm();
        $form->getField('enabled')
            ->setType('checkbox');
        $form->removeField('election');

        return $form;
    }
    protected function getCreateForm(array $data = []) : Form
    {
        $form = parent::getCreateForm($data);
        $form->removeField('keyenc');

        return $form
            ->setLayout([
                [ 'name', 'slug', 'enabled' ]
            ]);
    }
    protected function getUpdateForm(?Entity $entity = null, array $data = []) : Form
    {
        $form = parent::getUpdateForm($entity, $data);
        $form->getField('keyenc')
            ->disable();

        return $form
            ->setLayout([
                [ 'name', 'slug', 'enabled', 'keyenc' ]
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
}
