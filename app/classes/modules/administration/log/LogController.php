<?php

declare(strict_types=1);

namespace modules\administration\log;

use DateTime;
use helpers\html\Form;
use helpers\html\HTML as HTML;
use helpers\html\Table;
use helpers\html\TableRow;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use modules\common\crud\CRUDController;
use vakata\database\schema\Entity;

class LogController extends CRUDController
{
    private LogService $service;

    public static function permissions(): ?array
    {
        return [ 'log/viewraw' ];
    }

    public function __construct(Request $request, Response $response, Views $views, LogService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
    }

    protected function getTable(iterable $entities): Table
    {
        $table = parent::getTable($entities);
        $table->removeOperation('import');
        $table->removeOperation('export');
        $table
            ->removeColumn('id')
            ->removeColumn('request')
            ->removeColumn('response')
            ->removeColumn('context')
            ->removeColumn('usr')
            ->removeOperation('create');
        $table
            ->getColumn('created')
                ->addClass('left aligned')
                ->setMap(function (mixed $v) {
                    return new HTML(
                        '<i class="ui clock icon"></i> ' .
                        (($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                            $temp->format('d.m.Y H:i:s') : '')
                    );
                });
        $users = $this->service->users();
        $table
            ->getColumn('usr_name')
                ->setMap(function (mixed $v, Entity $row) use ($users) {
                    if (!$v) {
                        return '';
                    }
                    $user = $users[$row->usr] ?? null;
                    $qflt = '<i data-column="usr" ' .
                            'data-value="' .
                                htmlspecialchars((string)$row->usr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                            '" ' .
                            'class="quick-filter ui right floated filter icon"></i>';
                    if ($user && $user['avatar_data']) {
                        return new HTML(
                            $qflt . '<img class="ui avatar image" src="' . $user['avatar_data'] . '"> ' . $v
                        );
                    } else {
                        return new HTML($qflt . '<i class="ui user icon"></i> ' . $v);
                    }
                })
                ->setFilter(
                    (new \helpers\html\Form())
                        ->addField(
                            new \helpers\html\Field(
                                'select',
                                [ 'name' => 'usr' ],
                                [
                                    'label' => 'log.columns.usr',
                                    'values' => array_map(function (array $v): string {
                                        return $v['name'];
                                    }, $users)
                                ]
                            )
                        )
                );
        $table
            ->getColumn('ip')
                ->setMap(function (mixed $v, Entity $row) {
                    $req = explode('User-Agent:', $row->request, 2);
                    $mob = false;
                    if (count($req) > 1) {
                        $req = explode("\n", $req[1], 2)[0];
                        // @codingStandardsIgnoreLine
                        if (preg_match('/Mobile|iP(hone|od|ad)|Android|BlackBerry|IEMobile|Kindle|NetFront|Silk-Accelerated|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune/', $req)) {
                            $mob = true;
                        }
                    }
                    return new HTML('<i class="ui ' . ($mob ? 'tablet' : 'computer') . ' icon"></i> ' . $v);
                });
        $table
            ->getColumn('lvl')
                ->addClass('center aligned')
                ->setMap(function (mixed $v) {
                    switch ($v) {
                        case 'alert':
                        case 'critical':
                        case 'error':
                        case 'emergency':
                            $v = '' .
                                '<div class="ui medium red horizontal label">' .
                                    '<i class="ui exclamation icon"></i>'  . $v .
                                '</div>';
                            break;
                        case 'warning':
                        case 'notice':
                            $v = '' .
                                '<div class="ui orange horizontal label">' .
                                    '<i class="ui warning icon"></i>'  . $v .
                                '</div>';
                            break;
                        case 'info':
                        case 'debug':
                            $v = '' .
                                '<div class="ui teal horizontal label">' .
                                    '<i class="ui pencil icon"></i>'  . $v .
                                '</div>';
                            break;
                        default:
                            $v = '';
                            break;
                    }
                    return new HTML($v);
                });
        $table
            ->getColumn('message')
                ->setMap(function (mixed $v) {
                    $msg = (string)$v;
                    $value = [];
                    if (strpos($msg, '[IDS]') !== false) {
                        $value[] = '<div class="ui red horizontal label">IDS</div>';
                    }
                    if (strpos($msg, '[POST]') !== false) {
                        $value[] = '<div class="ui orange horizontal label">POST</div>';
                    }
                    if (strpos($msg, '[DELETE]') !== false) {
                        $value[] = '<div class="ui orange horizontal label">DELETE</div>';
                    }
                    if (strpos($msg, '[PUT]') !== false) {
                        $value[] = '<div class="ui orange horizontal label">PUT</div>';
                    }
                    if (strpos($msg, '[OPTIONS]') !== false) {
                        $value[] = '<div class="ui green horizontal label">OPTIONS</div>';
                    }
                    if (strpos($msg, '[HEAD]') !== false) {
                        $value[] = '<div class="ui green horizontal label">HEAD</div>';
                    }
                    if (strpos($msg, '[GET]') !== false) {
                        $value[] = '<div class="ui green horizontal label">GET</div>';
                    }
                    if (strpos($msg, '[CSP]') !== false) {
                        $value[] = '<div class="ui yellow horizontal label">CSP</div>';
                    }
                    if (strpos($msg, '[ECT]') !== false) {
                        $value[] = '<div class="ui yellow horizontal label">ECT</div>';
                    }
                    if (strpos($msg, '[XSS]') !== false) {
                        $value[] = '<div class="ui yellow horizontal label">XSS</div>';
                    }
                    if (strpos($msg, '[CSRF]') !== false) {
                        $value[] = '<div class="ui purple horizontal label">CSRF</div>';
                    }
                    preg_replace_callback('(\[(\d+)\])', function (array $matches) use (&$value) {
                        $value[] = '' .
                            '<div class="ui blue horizontal label">' .
                                (int) $matches[1] .
                            '</div>';
                        return '';
                    }, $msg);
                    $matches = [];
                    if (preg_match('( (/[^ ]*))ui', $msg, $matches)) {
                        $value[] = '<div class="ui horizontal label">' . htmlspecialchars($matches[1]) . '</div>';
                    }
                    $msg = trim(preg_replace(['(\[[^\]]+\])', '( (/[^ ]*))ui'], '', $msg) ?? '');
                    if (strlen($msg) > 50) {
                        $msg = mb_substr($msg, 0, 47) . ' &hellip;';
                    }
                    $value[] = $msg;
                    return new HTML(implode(' ', $value));
                });
        return $table;
    }
    protected function getRow(mixed $entity, mixed $id): TableRow
    {
        $v = parent::getRow($entity, $id);
        $operations = $v->getOperations();
        $operations = [
            'read' => $operations['read']
        ];
        $v->setOperations($operations);
        if ($v->getData()->lvl === 'warning') {
            $v->addClass('warning');
        }
        if (in_array($v->getData()->lvl, ['error', 'alert', 'critical', 'emergency'])) {
            $v->addClass('error');
        }
        return $v;
    }

    protected function getForm(): Form
    {
        $layout = [
            [ 'created', 'lvl', 'usr_name', 'ip' ],
            [ 'message' ],
            [ 'context' ]
        ];
        $form = parent::getForm();
        $form
            ->removeField('id')
            ->removeField('usr');
        $form->getField('message')->setType('textarea');
        $form->getField('context')->setType('textarea');
        $form->getField('lvl')->setOption(
            'values',
            [
                'emergency' => 'emergency',
                'alert' => 'alert',
                'critical' => 'critical',
                'error' => 'error',
                'warning' => 'warning',
                'notice' => 'notice',
                'info' => 'info',
                'debug' => 'debug'
            ]
        );
        if ($form->hasField('request')) {
            $form->getField('request')->setType('textarea');
            $layout[] = ['request'];
        }
        if ($form->hasField('response')) {
            $form->getField('response')->setType('textarea');
            $layout[] = ['response'];
        }
        return $form->setLayout($layout);
    }

    public function getCreate(): Response
    {
        throw new \Exception('Not implemented', 404);
    }
    public function postCreate(): Response
    {
        throw new \Exception('Not implemented', 404);
    }
    public function getUpdate(): Response
    {
        throw new \Exception('Not implemented', 404);
    }
    public function postUpdate(): Response
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
