<?php

declare(strict_types=1);

namespace modules\administration\groups;

use vakata\collection\Collection;
use helpers\html\Field as Field;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use vakata\intl\Intl;
use modules\common\crud\CRUDController;

class GroupsController extends CRUDController
{
    protected Intl $intl;
    private GroupsService $service;

    public function __construct(Request $request, Response $response, Views $views, GroupsService $service, Intl $intl)
    {
        parent::__construct($request, $response, $views, $service);
        if (!$views->getFolders()->exists('groups')) {
            $views->addFolder('groups', __DIR__ . '/views');
        }
        $this->intl = $intl;
        $this->service = $service;
    }

    protected function getTable(iterable $entities): Table
    {
        $table = parent::getTable($entities);
        $table
            ->removeColumn('grp')
            ->removeColumn('created');
        Collection::from($table->getRows())->each(function (TableRow $v) {
            $operations = $v->getOperations();
            $operations = [
                'update' => $operations['update']
            ];
            $v->setOperations($operations);
        });
        return $table;
    }

    protected function getForm(): Form
    {
        $perms = $this->service->getStoredPermissions();
        $form = parent::getForm();
        $form
            ->removeField('grp')
            ->removeField('created');
        $modules = [];
        $additional = [];
        foreach ($perms as $k => $v) {
            if (ctype_digit((string)$k)) {
                if (strpos($v, '/') || strpos($v, '.')) {
                    $additional[$k] = $v;
                } else {
                    $modules[] = $v;
                }
            } else {
                if (strpos($k, '/') || strpos($k, '.')) {
                    $additional[$k] = $v;
                } else {
                    $modules[] = $v;
                }
            }
        }
        $modules = array_combine(
            $modules,
            array_map(function ($v) {
                return call_user_func($this->intl, $v . '.title');
            }, $modules)
        );
        asort($modules);
        $form->addField(
            new Field(
                'checkboxes',
                [ 'name' => 'permissions' ],
                [
                    'label' => $this->module . '.columns.permissions',
                    'values' => $modules
                ]
            )
        );
        $rslt = [];
        foreach ($additional as $k => $v) {
            if (ctype_digit((string)$k)) {
                $m = preg_split('(/|\.)', $v);
                $m = $m && isset($m[0]) ? $m[0] : '';
                $rslt[$v] = call_user_func($this->intl, $m . '.title') . ' > ' .
                    call_user_func($this->intl, 'permission.' . $v);
            } else {
                $m = preg_split('(/|\.)', (string)$k);
                $m = $m && isset($m[0]) ? $m[0] : '';
                $rslt[$k] = call_user_func($this->intl, $m . '.title') . ' > ' .
                    call_user_func($this->intl, $v);
            }
        }
        asort($rslt);
        $form->addField(
            new Field(
                'checkboxes',
                [ 'name' => 'additional' ],
                [
                    'label' => $this->module . '.columns.additional',
                    'values' => $rslt
                ]
            )
        );
        return $form;
    }

    public function getRead(): Response
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
}
