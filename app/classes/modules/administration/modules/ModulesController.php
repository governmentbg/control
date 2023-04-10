<?php

declare(strict_types=1);

namespace modules\administration\modules;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\http\Uri as Url;
use helpers\html\Form as Form;
use helpers\html\Field as Field;
use League\Plates\Engine as Views;

class ModulesController
{
    private ModulesService $service;

    public function __construct(ModulesService $service)
    {
        $this->service = $service;
    }
    public function getIndex(Response $res, Views $views): Response
    {
        if (!$views->getFolders()->exists('modules')) {
            $views->addFolder('modules', __DIR__ . '/views');
        }
        $modules = $this->service->getModules();

        $form = new Form();
        $form->addField(
            new Field(
                'json',
                [
                    'name' => 'modules',
                    'value' => $modules
                ],
                [
                    'add'     => false,
                    'delete'  => false,
                    'reorder' => true,
                    'label'   => 'modules.columns.title',
                    'form'    => (new Form())
                        ->addClass('compact')
                        ->addField(
                            new Field(
                                'text',
                                [ 'name' => 'name', 'disabled' => true ],
                                [ 'label' => 'modules.columns.name' ]
                            )
                        )
                        ->addField(
                            new Field(
                                'text',
                                [ 'name' => 'classname' ],
                                [ 'label' => 'modules.columns.classname' ]
                            )
                        )
                        ->addField(
                            new Field(
                                'checkbox',
                                [ 'name' => 'loaded' ],
                                [ 'nobr' => true, 'label' => 'modules.columns.loaded' ]
                            )
                        )
                        ->addField(
                            new Field(
                                'checkbox',
                                [ 'name' => 'menu' ],
                                [ 'nobr' => true, 'label' => 'modules.columns.menu' ]
                            )
                        )
                        ->addField(
                            new Field(
                                'checkbox',
                                [ 'name' => 'dashboard' ],
                                [ 'nobr' => true, 'label' => 'modules.columns.dashboard' ]
                            )
                        )
                        ->addField(
                            new Field(
                                'text',
                                [ 'name' => 'parent' ],
                                [ 'nobr' => true, 'label' => 'modules.columns.parent']
                            )
                        )
                        ->addField(
                            new Field(
                                'text',
                                [ 'name' => 'icon' ],
                                [ 'nobr' => true, 'label' => 'modules.columns.icon']
                            )
                        )
                        ->addField(
                            new Field(
                                'text',
                                [ 'name' => 'color' ],
                                [ 'nobr' => true, 'label' => 'modules.columns.color']
                            )
                        )
                        ->setLayout([
                            [
                                'name:2',
                                'classname:4',
                                'loaded:2',
                                'menu:2',
                                'dashboard:2',
                                'parent:2',
                                'icon:1',
                                'color:1'
                            ]
                        ])
                ]
            )
        );
        return $res->setBody(
            $views->render('modules::index', [
                'form' => $form
            ])
        );
    }
    public function postIndex(Request $req, Response $res, Url $url): Response
    {
        $this->service->setModules(json_decode($req->getPost('modules'), true) ?? []);
        return $res->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
}
