<?php

declare(strict_types=1);

namespace modules\administration\uploads;

use DateTime;
use vakata\collection\Collection;
use helpers\html\Button as Button;
use helpers\html\Field;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use modules\common\crud\CRUDController;
use vakata\files\FileStorageInterface;
use vakata\database\schema\Entity;

class UploadsController extends CRUDController
{
    public function __construct(Request $request, Response $response, Views $views, UploadsService $service)
    {
        parent::__construct($request, $response, $views, $service);
    }

    protected function getTable(iterable $entities): Table
    {
        $table = parent::getTable($entities);
        $table->removeOperation('import');
        $table->removeOperation('export');
        $table
            ->removeColumn('id')
            ->removeColumn('hash')
            ->removeColumn('location')
            ->removeColumn('data')
            ->removeColumn('settings')
            ->removeOperation('create');
        $table
            ->getColumn('bytesize')
                ->addClass('right aligned')
                ->setMap(function (mixed $v) {
                    return $this->size((int)$v);
                });
        $table
            ->getColumn('uploaded')
                ->addClass('center aligned')
                ->setMap(function (mixed $v) {
                    return (($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v)) ?
                        $temp->format('d.m.Y H:i:s') : '');
                });
        Collection::from($table->getRows())->each(function (TableRow $v) {
            $operations = $v->getOperations();
            $operations['download'] = (new Button("download"))
                ->setLabel($this->module . '.operations.download')
                ->setIcon('download')
                ->setClass('skip blank mini purple icon button')
                ->setAttr('href', $this->module . '/download/' . $v->getAttr('id'));
            $operations = [
                'download' => $operations['download'],
                'read'     => $operations['read'],
                'update'   => $operations['update'],
                'delete'   => $operations['delete']
            ];
            $v->setOperations($operations);
        });
        return $table;
    }
    protected function size(int $bytes, int $decimals = 2): string
    {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = (int)floor((strlen((string)$bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . ($size[$factor] ?? '');
    }

    protected function getForm(): Form
    {
        $form = parent::getForm();
        $form->removeField('id');
        $form->removeField('data');
        $form->removeField('location');
        $form->getField('settings')->setType('textarea');
        return $form;
    }

    protected function getUpdateForm(?Entity $entity = null, array $data = []): Form
    {
        $form = parent::getUpdateForm($entity, $data);
        $form->addField(
            (new Field('file', ['name' => 'temp'], ['label' => 'uploads.newfile', 'multipart' => ['temp' => 1]]))
        );
        return $form;
    }

    protected function getReadForm(?Entity $entity = null): Form
    {
        return parent::getReadForm($entity)->removeField('temp');
    }

    public function getDownload(FileStorageInterface $files): Response
    {
        $file = $files->get($this->url->getSegment(2));
        $name = $file->name();
        return (new Response())
            ->withHeader('Location', $this->url->get('upload/' . $this->url->getSegment(2) . '/' . $name));
    }
    public function getCreate(): Response
    {
        throw new \Exception('Not allowed', 400);
    }
    public function postCreate(): Response
    {
        throw new \Exception('Not allowed', 400);
    }
}
