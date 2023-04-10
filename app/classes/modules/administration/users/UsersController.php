<?php

declare(strict_types=1);

namespace modules\administration\users;

use vakata\collection\Collection;
use helpers\html\Button as Button;
use helpers\html\Field as Field;
use helpers\html\HTML as HTML;
use helpers\html\TableColumn;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use modules\common\crud\CRUDController;
use modules\common\crud\CRUDException;
use vakata\database\schema\Entity;
use vakata\jwt\JWT;

class UsersController extends CRUDController
{
    public static function permissions(): array
    {
        return [ 'users/impersonate', 'users/master', 'users/languages' ];
    }

    private UsersService $service;

    public function __construct(Request $request, Response $response, Views $views, UsersService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
        if (!$views->getFolders()->exists('users')) {
            $views->addFolder('users', __DIR__ . '/views');
        }
    }

    protected function getTable(iterable $entities): Table
    {
        $groups = $this->service->getAvailableGroups();
        $table = parent::getTable($entities);
        $table
            ->removeColumn('usr')
            ->removeColumn('tfa')
            ->removeColumn('disabled')
            ->removeColumn('avatar')
            ->removeColumn('push')
            ->removeColumn('avatar_data')
            ->removeColumn('data');
        $table
            ->getColumn('name')
                ->setMap(function (mixed $value, Entity $user) {
                    if ($user->avatar_data) {
                        return new HTML('<img class="ui avatar image" src="' . $user->avatar_data . '"> ' . $value);
                    } else {
                        return new HTML('' .
                            '<span class="ui grey circular label user-td-label">' .
                                '<i class="ui user icon"></i>' .
                            '</span> ' . $value);
                    }
                });
        $table
            ->addColumn(
                (new TableColumn('user_groups.grp'))
                    ->setMap(function (mixed $k, Entity $row) use ($groups) {
                        $tags = [];
                        foreach ($row->user_groups as $group) {
                            if (isset($groups[$group->grp])) {
                                $tags[] = '<span class="ui horizontal label">' . $groups[$group->grp] . '</span>';
                            }
                        }
                        return new HTML(implode('', $tags));
                    })
                    ->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "multipleselect",
                                [ 'name' => 'user_groups.grp[]' ],
                                [ 'label' => $this->module . '.filters.groups', 'values' => $groups ]
                            ))
                    )
            );

        return $table;
    }
    protected function getRow(Entity $entity, mixed $id): TableRow
    {
        $v = parent::getRow($entity, $id);
        $operations = $v->getOperations(true);
        $temp = [];
        if ($this->service->canImpersonate($entity)) {
            $temp['impersonate'] = (new Button('impersonate'))
                                        ->setLabel($this->module . '.operations.impersonate')
                                        ->setIcon('user')
                                        ->setClass('skip mini purple icon button')
                                        ->setAttr('href', $this->module . '/impersonate/' . $v->getAttr('id'));
        }
        $temp['update'] = $operations['update'];
        $temp['history'] = $operations['history']->show();
        $v->setOperations($temp);
        if ($v->getData()->disabled) {
            $v->addClass('error');
        }
        return $v;
    }

    protected function getForm(): Form
    {
        $layout = [
            'acc:open:' . $this->module . '.data',
            [ 'name', 'mail' ],
            [ 'disabled', 'tfa' ],
            'acc:' . $this->module . '.additional',
            [ 'data' ],
            'acc:' . $this->module . '.groups',
            [ 'main_grp' ],
            [ 'grps' ]
        ];
        $form = parent::getForm();
        $form
            ->removeField('usr')
            ->removeField('push')
            ->removeField('avatar')
            ->removeField('avatar_data');
        $form
            ->getField('disabled')
            ->setType('select')
            ->setOption('translate', true)
            ->setOption('values', ['yes', 'no']);
        $form
            ->getField('data')
            ->setOption('label', '')
            ->setType('json')
            ->setOption(
                'form',
                (new Form())
                    ->addField(new Field('text', ['name' => 'key'], ['label' => 'users.fields.key']))
                    ->addField(new Field('text', ['name' => 'value'], ['label' => 'users.fields.value']))
            );
        $form->getField('tfa')->setType('select')->setOption('translate', true)->setOption('values', ['no', 'yes']);
        $groups = $this->service->getAvailableGroups();

        if ($this->service->isMaster()) {
            $form
                ->addField(
                    new Field(
                        'checkboxes',
                        [ 'name' => 'grpsp' ],
                        [ 'label' => $this->module . '.columns.groupsp', 'grid' => 4, 'values' => $groups ]
                    )
                );
            $layout[] = ['grpsp'];
        }

        $orig = $this->service->userOrganizations();
        $orgs = Collection::from($orig)
            ->map(function (array $v) use ($orig) {
                return [
                    'id' => $v['org'],
                    'text' => $v['title'],
                    'parent' => (int)$v['pid'] && isset($orig[$v['pid']]) ? (int)$v['pid'] : '#',
                    'icon' => $v['rgt'] - $v['lft'] > 1 ? 'ui icon cubes' : 'ui icon cube'
                ];
            })
            ->values()
            ->toArray();
        if (count($orgs) > 1) {
            $form
                ->addField(
                    new Field(
                        'tree',
                        [ 'name' => 'org' ],
                        [
                            'label' => '', //$module . '.columns.organization',
                            'values' => $orgs,
                            'multiple' => true,
                            'plugins' => ['checkbox']
                        ]
                    )
                );
            $layout[] = $this->module . '.organization';
            $layout[] = ['org'];
        }

        $layout[] = 'acc:' . $this->module . '.authentication';
        $curr = count($layout);
        $methods = $this->service->getAuthenticationMethods();
        if (in_array('PasswordDatabase', $methods)) {
            $layout[] = [ 'auth_username', 'auth_password' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_username', 'autocomplete' => 'off' ],
                        [ 'label' => $this->module . '.columns.username' ]
                    )
                )
                ->addField(
                    new Field(
                        'password',
                        [
                            'name' => 'auth_password',
                            'autocomplete' => 'new-password',
                            'placeholder' => 'users.onlyentertochange'
                        ],
                        [ 'label' => $this->module . '.columns.password' ]
                    )
                );
        }
        if (in_array('Certificate', $methods)) {
            $layout[] = [ 'auth_certificate' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_certificate' ],
                        [ 'label' => $this->module . '.columns.auth_certificate' ]
                    )
                );
        }
        if (in_array('CertificateAdvanced', $methods)) {
            $layout[] = [ 'auth_certificate2' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_certificate2' ],
                        [ 'label' => $this->module . '.columns.auth_certificate2' ]
                    )
                );
        }
        if (in_array('LDAP', $methods)) {
            $layout[] = [ 'auth_ldap' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_ldap' ],
                        [ 'label' => $this->module . '.columns.auth_ldap' ]
                    )
                );
        }
        if (in_array('SMTP', $methods)) {
            $layout[] = [ 'auth_smtp' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_smtp' ],
                        [ 'label' => $this->module . '.columns.auth_smtp' ]
                    )
                );
        }
        if (count($layout) === $curr) {
            unset($layout[count($layout) - 1]);
        }

        $form
            ->addField(
                new Field(
                    'select',
                    [ 'name' => 'main_grp' ],
                    [ 'label' => $this->module . '.columns.main_grp', 'values' => $groups ]
                )
            )
            ->addField(
                new Field(
                    'checkboxes',
                    [ 'name' => 'grps' ],
                    [ 'label' => $this->module . '.columns.groups', 'grid' => 4, 'values' => $groups ]
                )
            );
        if ($this->service->hasCMS()) {
            $layout[] = 'acc:CMS';
            $langs = $this->service->getAvailableLangs();
            if (count($langs)) {
                $form->addField(
                    new Field(
                        'checkboxes',
                        [ 'name' => 'langs' ],
                        [ 'label' => $this->module . '.columns.langs', 'grid' => 4, 'values' => $langs ]
                    )
                );
                $layout[] = $this->module . '.langs';
                $layout[] = ['langs'];
            }
            $sites = $this->service->getAvailableSites();
            if (count($sites)) {
                $form->addField(
                    new Field(
                        'checkboxes',
                        [ 'name' => '_sites' ],
                        [ 'label' => $this->module . '.columns.sites', 'grid' => 4, 'values' => $sites ]
                    )
                );
                $layout[] = $this->module . '.sites';
                $layout[] = ['_sites'];
            }
        }
        return $form->setLayout($layout);
    }
    protected function getCreateForm(array $data = []): Form
    {
        $form = parent::getCreateForm($data);
        if ($form->hasField('auth_password')) {
            $form->getField('auth_password')->setAttr('placeholder', '');
        }
        return $form;
    }
    public function getImpersonate(): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
            if (!$this->service->canImpersonate($entity)) {
                throw new CRUDException();
            }
        } catch (CRUDException $e) {
            $this->session->set('error', $this->url->getSegment(0) . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0)))
            );
        }
        return $this->response->setBody(
            $this->views->render('users::impersonate', [
                'user' => $entity,
                'back' => $this->url->linkTo(
                    $this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0))
                )
            ])
        );
    }
    public function postImpersonate(JWT $token): Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
            if (!$this->service->canImpersonate($entity)) {
                throw new CRUDException();
            }
        } catch (CRUDException $e) {
            $this->session->set('error', $this->url->getSegment(0) . '.messages.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->url->getSegment(0) . '.index', $this->url->getSegment(0)))
            );
        }
        $token->setClaim('impersonate', $this->url->getSegment(2));
        return $this->response->withHeader('Location', $this->url->linkTo());
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
