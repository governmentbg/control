<?php

declare(strict_types=1);

namespace modules\administration\authentication;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use helpers\html\HTML;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use modules\common\crud\CRUDController;
use vakata\collection\Collection;

class AuthenticationController extends CRUDController
{
    public function __construct(Request $request, Response $response, Views $views, AuthenticationService $service)
    {
        parent::__construct($request, $response, $views, $service);
    }

    protected function getTable(iterable $entities): Table
    {
        $table = parent::getTable($entities);
        $table->setOperations([]);
        $table
            ->removeColumn('authentication')
            ->removeColumn('settings')
            ->removeColumn('position')
            ->getColumn('disabled')
                ->setSortable(false)
                ->setMap(function (mixed $v) {
                    return $v ?
                        '' :
                        new HTML(
                            '<i class="ui check icon"></i>'
                        );
                });
        $table
        ->getColumn('authenticator')
            ->setSortable(false);
        $table
            ->getColumn('conditions')
                ->setSortable(false)
                ->setMap(function (mixed $v) {
                    return $v && strlen($v) ?
                        new HTML(
                            '<i class="ui check icon"></i>'
                        ) :
                        '';
                });
        Collection::from($table->getRows())->each(function (TableRow $v) {
            $v->removeOperation('read');
            $v->removeOperation('delete');
        });
        return $table;
    }

    protected function getForm(): Form
    {
        $form = parent::getForm();
        $form->removeField('authentication');
        $form->getField('authenticator')->disable();
        $form->getField('conditions')->setType('textarea');
        $form->getField('position')->setType('number');
        $form->getField('settings')->setType('textarea')->setAttr('rows', 12);
        $form->getField('disabled')
            ->setType('select')
            ->setOption('values', [0 => 'yes', 'no'])
            ->setOption('translate', true);
        $form->setLayout([
            ['authenticator', 'disabled', 'position'],
            ['settings'],
            ['conditions']
        ]);
        return $form;
    }
}
