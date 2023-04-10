<?php

declare(strict_types=1);

namespace modules\administration\forums;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use helpers\html\Field;
use helpers\html\HTML;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use modules\common\crud\CRUDController;
use vakata\collection\Collection;

class ForumsController extends CRUDController
{
    private ForumsService $service;

    public static function permissions(ForumsService $service): array
    {
        $forums = [];
        foreach ($service->getForums() as $k => $v) {
            $forums['forums/' . $k] = 'Раздел: ' . $v;
        }
        return array_merge(['forums/moderator'], $forums);
    }

    public function __construct(Request $request, Response $response, Views $views, ForumsService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
    }

    protected function getTable(iterable $entities): Table
    {
        $table = parent::getTable($entities);
        $table
            ->removeColumn('forum')
            ->removeColumn('usr')
            ->removeColumn('created');
        $table
            ->getColumn('hidden')
                ->setMap(function (mixed $v) {
                    $types = ['eye', 'eye slash'];
                    return new HTML(
                        '<i class="ui ' . $types[$v] . ' icon"></i>'
                    );
                });
        $table
            ->getColumn('locked')
                ->setMap(function (mixed $v) {
                    $types = ['lock open', 'lock'];
                    return new HTML(
                        '<i class="ui ' . $types[$v] . ' icon"></i>'
                    );
                });
        Collection::from($table->getRows())->each(function (TableRow $v) {
            $v->removeOperation('read');
        });
        return $table;
    }

    protected function getForm(): Form
    {
        $form = parent::getForm();
        $form->removeField('forum');
        $form->removeField('created');
        $form->removeField('usr');
        $form->getField('hidden')->setType('checkbox');
        $form->getField('locked')->setType('checkbox');
        $form->addField(
            new Field(
                'checkboxes',
                [
                    'name' => 'grps'
                ],
                [
                    'reset'  => true,
                    'inline' => true,
                    'nolabel' => true,
                    'grid'   => 4,
                    'values' => $this->service->getGroups(),
                    'label'  => 'forums.groups'
                ]
            )
        );
        $form->setLayout([
            ['name'],
            'forums.settings',
            ['hidden', 'locked'],
            'forums.permissions',
            ['grps']
        ]);
        return $form;
    }
}
