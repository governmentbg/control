<?php

declare(strict_types=1);

namespace modules\tcontrol\servers;

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

class ServersController extends CRUDController
{
    private ServersService $service;

    public function __construct(Request $request, Response $response, Views $views, ServersService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
    }

    protected function getTable(iterable $entities) : Table
    {
        $table = parent::getTable($entities);
        $table->removeColumn('server');
        $table
            ->getColumn('enabled')
                ->setMap(function ($value) {
                    return (int) $value ? 'Да' : 'Не';
                });
        $table
            ->removeColumn('ip')
            ->removeColumn('key_setup')
            ->removeColumn('key_sik')
            ->removeColumn('monitor')
            ->removeColumn('key_real');
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
        //                 if (!$row['monitor']) {
        //                     return '';
        //                 }
        //                 $temp = json_decode($row['monitor'], true) ?? [];
        //                 return $temp['io'] ?? '';
        //             })
        //     );
        $table
            ->addColumn(
                (new TableColumn('streams'))
                    ->setSortable(false)
                    ->setMap(function (mixed $v, Entity $row): HTML {
                        if (!$row->monitor) {
                            return new HTML('');
                        }
                        $temp = json_decode($row->monitor, true) ?? [];
                        $cnt = 0;
                        foreach ($temp['rtmp_stats']['rtmp']['server']['application'] ?? [] as $app) {
                            $cnt += (int)($app['live']['nclients'] ?? 0);
                        }
                        return new HTML(
                            '<code>' .
                            '<i class="broadcast tower icon"></i> ' . ((string)$cnt) . '<br/>' .
                            '<i class="ethernet icon"></i> ' . ((string)$this->size((int)$temp['rtmp_stats']['rtmp']['bw_in'] ?? 0)) .
                            '</code>'
                        );
                    })
            );
        $table
            ->getOperation('export')
            ->show();

        return $table;
    }
    protected function size(int $bytes, int $decimals = 2): string
    {
        $size = array('b','kb','Mb','Gb','Tb','Pb','Eb','Zb','Yb');
        $factor = (int)floor((strlen((string)$bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . ($size[$factor] ?? '') . '/s';
    }
    protected function getRow(Entity $entity, mixed $id) : TableRow
    {
        $row = parent::getRow($entity, $id);
        $row->removeOperation('delete');
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
        $form->getField('enabled')
            ->setType('checkbox');
        $form->removeField('server');
        $form->removeField('monitor');
        $form->removeField('ip');

        return $form;
    }
    protected function getCreateForm(array $data = []) : Form
    {
        $form = parent::getCreateForm($data);

        return $form
            ->setLayout([
                [ 'host', 'inner_host', 'enabled' ]
            ]);
    }
    protected function getUpdateForm(?Entity $entity = null, array $data = []): Form
    {
        $form = parent::getUpdateForm($entity, $data);

        $form
            ->getField('key_setup')
            ->disable();
        $form
            ->getField('key_sik')
            ->disable();
        $form
            ->getField('key_real')
            ->disable();

        return $form
        ->setLayout([
            [ 'host', 'inner_host', 'enabled' ],
            [ 'key_setup', 'key_sik', 'key_real' ]
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
