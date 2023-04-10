<?php

declare(strict_types=1);

namespace modules\tcontrol\siks;

use helpers\AppStatic;
use helpers\html\Button;
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
use TCPDF;
use vakata\database\schema\Entity;

class SiksController extends CRUDController
{
    private SiksService $service;

    public function __construct(Request $request, Response $response, Views $views, SiksService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
        $this->views->addFolder($this->module, __DIR__ . '/views');
    }
    protected function getTable(iterable $entities) : Table
    {
        $table = parent::getTable($entities);
        $table
            ->removeColumn('sik')
            ->removeColumn('election')
            ->removeColumn('test_key')
            ->removeColumn('prod_key')
            ->removeColumn('insite');
            //->removeColumn('address');
        $table->getOperation('export')->show();

        $miks = $this->service->getMiks();
        $table
            ->getColumn('mik')
                ->setFilter(
                    (new Form())
                        ->addField(
                            new Field(
                                'multipleselect',
                                [ 'name' => 'mik[]' ],
                                [ 'label' => $this->module . '.filters.mik', 'values' => $miks ]
                            )
                        )
                )
                ->setMap(function ($value) use ($miks) {
                    return $miks[$value] ?? '';
                });
        $table
            ->getColumn('video')
                ->setMap(function ($value) {
                    return (int) $value ? 'Да' : 'Не';
                })
                ->setFilter(
                    (new Form())
                        ->addField(
                            new Field(
                                'select',
                                [ 'name' => 'video' ],
                                [ 'label' => $this->module . '.filters.video', 'values' => [ 'Не', 'Да' ] ]
                            )
                        )
                );
        $table->getOperation('import')
            ->show();
        $table->addOperation(
            (new Button("pdf_saturday"))
                ->setLabel($this->module . '.operations.pdf_saturday')
                ->setIcon('file')
                ->setClass('purple icon labeled button')
                ->setAttr('href', $this->module . '/pdfssaturday')
        );
        $table->addOperation(
            (new Button("pdf_sunday"))
                ->setLabel($this->module . '.operations.pdf_sunday')
                ->setIcon('file')
                ->setClass('purple icon labeled button')
                ->setAttr('href', $this->module . '/pdfssunday')
        );
        $table->addColumn(
            (new TableColumn('has_test'))
                ->setMap(function (mixed $value, Entity $row) : HTML {
                    return new HTML(
                        $this->service->hasTestStream((int) $row->sik) ?
                            '<i class="icon check green"></i>' :
                            '<i class="icon remove red"></i>'
                        );
                })
                ->setFilter(
                    (new Form())
                        ->addField(
                            new Field(
                                'select',
                                [ 'name' => 'has_test' ],
                                [ 'label' => $this->module . '.filters.has_test', 'values' => [ 'Не', 'Да' ] ]
                            )
                        )
                )
        );
        $table->addColumn(
            (new TableColumn('has_real'))
                ->setMap(function (mixed $value, Entity $row) : HTML {
                    return new HTML(
                        $this->service->hasRealStream((int) $row->sik) ?
                            '<i class="icon check green"></i>' :
                            '<i class="icon remove red"></i>'
                        );
                })
                ->setFilter(
                    (new Form())
                        ->addField(
                            new Field(
                                'select',
                                [ 'name' => 'has_real' ],
                                [ 'label' => $this->module . '.filters.has_real', 'values' => [ 'Не', 'Да' ] ]
                            )
                        )
                )
        );
        $table->addColumn(
            (new TableColumn('real'))
                ->setMap(function (mixed $value, Entity $row) : HTML {
                    return new HTML(
                        $this->service->realStreamNow((int) $row->sik) ?
                            '<i class="play icon blue circle large video-play"></i>' :
                            '<i class="icon remove red"></i>'
                    );
                })
                ->setFilter(
                    (new Form())
                        ->addField(
                            new Field(
                                'select',
                                [ 'name' => 'real' ],
                                [ 'label' => $this->module . '.filters.real', 'values' => [ 'Не', 'Да' ] ]
                            )
                        )
                )
        );

        return $table;
    }
    protected function getRow(Entity $entity, mixed $id) : TableRow
    {
        $row = parent::getRow($entity, $id);
        $row->addOperation(
            (new Button("pdf"))
                ->setLabel($this->module . '.operations.pdf')
                ->setIcon('file outline')
                ->setClass('mini purple icon button')
                ->setAttr('href', $this->module . '/pdf/' . $entity->sik)
        );

        return $row;
    }
    protected function getForm() : Form
    {
        $form = parent::getForm();
        $form->removeField('sik');
        $form->removeField('election');
        $form->getField('mik')
            ->setType('select')
            ->setOption('values', $this->service->getMiks());
        $form->getField('video')
            ->setType('checkbox');
        $form->getField('insite')
            ->setType('checkbox');
        return $form;
    }
    protected function getCreateForm(array $data = []) : Form
    {
        $form = parent::getCreateForm($data);
        $form->removeField('test_key');
        $form->removeField('prod_key');

        return $form
            ->setLayout([
                [ 'num', 'mik' ],
                [ 'video', 'insite' ],
                [ 'address' ]
            ]);
    }
    protected function getUpdateForm(?Entity $entity = null, array $data = []) : Form
    {
        $form = parent::getUpdateForm($entity, $data);
        $form->getField('test_key')
            ->disable();
        $form->getField('prod_key')
            ->disable();
        $form->addField(
            new Field(
                'maps',
                [ 'name' => 'map' ],
                [ 'label' => $this->module . '.columns.map' ]
            )
        );
        $form->addField(
            new Field(
                'videolist',
                [ 'name' => 'records' ],
                [ 'label' => $this->module . '.columns.records' ]
            )
        );

        return $form
            ->setLayout([
                [ 'num', 'mik' ], 
                [ 'video', 'insite' ],
                [ 'address' ],
                [ 'test_key', 'prod_key' ],
                [ 'map' ],
                [ 'records' ]
            ])
            ->populate($entity);
    }
    public function getPdf() : Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            return new Response(404);
        }

        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setAutoPageBreak(false);
        $pdf->setTextColor(0, 0, 0, 100);
        $pdf->setDrawColor(0, 0, 0, 100);
        $pdf->setMargins(0, 0, 0, true);
        $pdf->SetLineStyle([ 'width' => 0.1, 'color' => [ 255, 255, 255 ] ]);


        $pdf->AddPage();
        $pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight());
        $pdf->SetFont('sofiasansb', '', 12, '', true);
        $pdf->setXY(115.9, 39.198 - 2.88);
        $pdf->Cell(81.767, 10, 'ЗА СЪБОТА - ТЕСТ В СЕКЦИЯТА', 0, 0, 'L');
        $pdf->setXY(115.9, 44.49 - 2.88);
        $pdf->Cell(74.8, 10, 'СИК № ' . $entity->num, 0, 0, 'L');
        $pdf->SetFont('sofiasans', '', 11, '', true);
        $pdf->setXY(115.9, 49.781 - 2.22 + 1.74);
        $pdf->MultiCell(74.8, 10, 'РИК ' . sprintf('%02d', $entity->mik) . ', ' . $entity->address, 0, 'L');
        $pdf->write2DBarcode(
            $this->service->getQRPayloadData($entity, 'test-sik'),
            'QRCODE,M',
            164.38,
            252.04,
            31.3,
            31.3,
            [
                'fgcolor' => [ 0, 0, 0, 100 ]
            ]
        );

        $pdf->AddPage();
        $pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight());
        $pdf->SetFont('sofiasansb', '', 12, '', true);
        $pdf->setXY(115.9, 39.198 - 2.88);
        $pdf->Cell(81.767, 10, 'ЗА НЕДЕЛЯ - СЛЕД КРАЯ НА ГЛАСУВАНЕТО', 0, 0, 'L');
        $pdf->setXY(115.9, 44.49 - 2.88);
        $pdf->Cell(74.8, 10, 'СИК № ' . $entity->num, 0, 0, 'L');
        $pdf->SetFont('sofiasans', '', 11, '', true);
        $pdf->setXY(115.9, 49.781 - 2.22 + 1.74);
        $pdf->MultiCell(74.8, 10, 'РИК ' . sprintf('%02d', $entity->mik) . ', ' . $entity->address, 0, 'L');
        $pdf->write2DBarcode(
            $this->service->getQRPayloadData($entity, 'real'),
            'QRCODE,M',
            164.38,
            252.04,
            31.3,
            31.3,
            [
                'fgcolor' => [ 0, 0, 0, 100 ]
            ]
        );

        return $this->response
            ->setContentTypeByExtension('pdf')
            ->withAddedHeader('Content-Disposition', 'inline')
            ->setBody($pdf->Output('Sik.pdf', 'S'));
    }
    public function getPdfsSaturday() : Response
    {
        $file = AppStatic::get('STORAGE_SIK_PDF') . '/Siks_saturday.pdf';

        if (!is_file($file) || !is_readable($file)) {
            $this->session->set('error', $this->module . '.messages.pdf.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
            );
        }

        return $this->response
            ->setContentTypeByExtension('pdf')
            ->withCallback(
                function () use ($file) {
                    $from = fopen($file, 'r');
                    $to = fopen('php://output', 'w');

                    while ($data = fread($from, 4096)) {
                        fwrite($to, $data);
                    }
                }
            );
    }
    public function getPdfsSunday() : Response
    {
        $file = AppStatic::get('STORAGE_SIK_PDF') . '/Siks_sunday.pdf';

        if (!is_file($file) || !is_readable($file)) {
            $this->session->set('error', $this->module . '.messages.pdf.notfound');
            return $this->response->withHeader(
                'Location',
                $this->url->linkTo($this->session->get($this->module . '.index', $this->module))
            );
        }

        return $this->response
            ->setContentTypeByExtension('pdf')
            ->withCallback(
                function () use ($file) {
                    $from = fopen($file, 'r');
                    $to = fopen('php://output', 'w');

                    while ($data = fread($from, 4096)) {
                        fwrite($to, $data);
                    }
                }
            );
    }
    public function getStream() : Response
    {
        if (!$this->request->getQuery('sik')) {
            return new Response(400);
        }
        try {
            $url = $this->service->getStreamUrl((int) $this->request->getQuery('sik'));
        } catch (\Throwable $e) {
            return new Response(500);
        }

        return (new Response())
            ->setContentTypeByExtension('json')
            ->setBody(json_encode([ 'url' => $url ]));
    }
}
