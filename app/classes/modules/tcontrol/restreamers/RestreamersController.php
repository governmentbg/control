<?php

declare(strict_types=1);

namespace modules\tcontrol\restreamers;

use helpers\html\Field;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use helpers\html\Form;
use helpers\html\HTML;
use helpers\html\Table;
use helpers\html\TableColumn;
use helpers\html\TableRow;
use modules\common\crud\CRUDController;
use modules\common\crud\CRUDException;
use vakata\database\schema\Entity;

class RestreamersController extends CRUDController
{
    private RestreamersService $service;

    public function __construct(Request $request, Response $response, Views $views, RestreamersService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
    }
    protected function getTable(iterable $entities) : Table
    {
        $table = parent::getTable($entities);
        $table->removeColumn('restreamer');
        $table->removeColumn('ip');
        $table->removeColumn('monitor');
        $table
            ->getColumn('enabled')
                ->setMap(function ($value) {
                    return (int) $value ? 'Да' : 'Не';
                });
        $table->addColumn(
            (new TableColumn('miks'))
                ->setMap(function ($value, $row) {
                    $tags = [];
                    $i = 0;
                    foreach ($row->miks() as $mik) {
                        if ($i && $i % 4 === 0) {
                            $tags[] = '<br />';
                        }
                        $i ++;
                        $tags[] = '<span class="ui horizontal label margin-label">' . $mik->name . '</span>';
                    }
                    return new HTML(implode(' ', $tags));
                })
        );
        $table
            ->addColumn(
                (new TableColumn('load'))
                    ->setSortable(false)
                    ->setMap(function (mixed $v, Entity $row): HTML {
                        if (!$row->monitor) {
                            return new HTML('');
                        }
                        $temp = json_decode($row->monitor, true) ?? [];
                        $disk = 0;
                        foreach ($temp['disk_usage'] as $k => $v) {
                            if (strpos($k, 'data') || strpos($k, 'recordings')) {
                                $disk = $v;
                                break;
                            }
                        }
                        return new HTML(
                            '<code>' . 
                            '<i class="microchip icon"></i> ' . ($temp['cpu_percent'][1] ?? 0) . '% <br />' .
                            '<i class="ethernet icon"></i> I: ' . ($temp['perf']['net_recv'] . ' / O: ' .  $temp['perf']['net_sent']) . ' <br />' .
                            '<i class="hdd icon"></i> R: ' . ($temp['perf']['io_read'] . ' / W: ' .  $temp['perf']['io_write']) . ' / ' . 
                            $disk . '% </code>'
                        );
                    })
            );
        // $table
        //     ->addColumn(
        //         (new TableColumn('io'))
        //             ->setSortable(false)
        //             ->setMap(function (mixed $v, Entity $row): string {
        //                 if (!$row->monitor) {
        //                     return '';
        //                 }
        //                 $temp = json_decode($row->monitor, true) ?? [];
        //                 return $temp['io'] ?? '';
        //             })
        //     );

        return $table;
    }
    protected function getRow(Entity $entity, mixed $id): TableRow
    {
        $row = parent::getRow($entity, $id);
        if ($entity->monitor) {
            $temp = json_decode($entity->monitor, true) ?? [];
            $load = $temp['cpu_percent'][1] ?? 0;
            if (time() - $temp['timestamp'] > 10 * 60) {
                $row->addClass('purple');
            } elseif ((int)$load > 80) {
                $row->addClass('error');
            } elseif ((int)$load > 50) {
                $row->addClass('warning');
            }
        } else {
            if ($entity->enabled) {
                $row->addClass('purple');
            }
        }
        return $row;
    }
    protected function getForm() : Form
    {
        $form = parent::getForm();
        $form->removeField('restreamer');
        $form->removeField('ip');
        $form->removeField('monitor');
        $form->getField('enabled')
            ->setType('checkbox');
        $form->addField(
            new Field(
                'checkboxes',
                [ 'name'    => '_servers' ],
                [
                    'label'     => $this->module . '.columns._servers',
                    'values'    => $this->service->getServers(),
                    'grid'      => 4
                ]
            )
        );
        $form->addField(
            new Field(
                'checkboxes',
                [ 'name'    => '_miks' ],
                [
                    'label'     => $this->module . '.columns._miks',
                    'values'    => $this->service->getMiks(),
                    'grid'      => 4
                ]
            )
        );

        return $form
            ->setLayout([
                [ 'host', 'inner_host', 'enabled' ],
                [ '_servers' ],
                [ '_miks' ]
            ]);
    }
    protected function getUpdateForm(?Entity $entity = null, array $data = []) : Form
    {
        $form = parent::getUpdateForm();

        return $form
            ->populate($entity)
            ->populate($data);
    }
}
