<?php
declare(strict_types=1);

namespace modules\tcontrol\devices;

use helpers\AppStatic;
use helpers\html\Button;
use helpers\html\Field;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use League\Plates\Engine as Views;
use helpers\html\Form;
use helpers\html\Table;
use helpers\html\TableRow;
use modules\common\crud\CRUDController;
use modules\common\crud\CRUDException;
use TCPDF;
use vakata\database\schema\Entity;

class DevicesController extends CRUDController
{
    private DevicesService $service;

    public function __construct(Request $request, Response $response, Views $views, DevicesService $service)
    {
        parent::__construct($request, $response, $views, $service);
        $this->service = $service;
    }

    protected function getTable(iterable $entities) : Table
    {
        $table = parent::getTable($entities);
        $table
            ->getColumn('registered')
                ->setMap(function ($value) {
                    return $value ? date('d.m.Y H:i:s', strtotime($value)) : '';
                });
        $table->addOperation(
            (new Button("pdf"))
                ->setLabel($this->module . '.operations.pdf')
                ->setIcon('file')
                ->setClass('purple icon labeled button')
                ->setAttr('href', $this->module . '/pdfs')
        );

        return $table;
    }
    protected function getRow(Entity $entity, mixed $id) : TableRow
    {
        $row = parent::getRow($entity, $id);
        $row->removeOperation('update');
        $row->addOperation(
            (new Button("pdf"))
                ->setLabel($this->module . '.operations.pdf')
                ->setIcon('file outline')
                ->setClass('mini purple icon button')
                ->setAttr('href', $this->module . '/pdf/' . $entity->udi)
        );

        return $row;
    }
    protected function getCreateForm(array $data = []) : Form
    {
        $form = new Form();
        $form->addField(
            new Field(
                'text',
                [ 'name'    => 'device' ],
                [ 'label'   => $this->module . '.columns.device' ]
            )
        );

        return $form
            ->populate($data)
            ->validate($this->service->getValidator(true));
    }
    protected function getReadForm(?Entity $entity = null): Form
    {
        $form = parent::getReadForm($entity);
        $form->addField(
            new Field(
                'maps',
                [ 'name' => 'map' ],
                [ 'label' => $this->module . '.columns.map' ]
            )
        );

        return $form
            ->setLayout([
                [ 'udi', 'install_key', 'registered' ],
                [ 'map' ]
            ])
            ->populate([ 'map' => json_encode($this->service->getDevicePoints($entity)) ]);
    }
    public function getUpdate() : never
    {
        throw new CRUDException('Not allowed');
    }
    public function postUpdate() : never
    {
        throw new CRUDException('Not allowed');
    }
    public function getPdf() : Response
    {
        try {
            $entity = $this->service->read($this->url->getSegment(2));
        } catch (CRUDException $e) {
            return new Response(404);
        }

        $pdf = new TCPDF('P', 'mm', [ 48, 105 ]);
        $pdf->AddFont('dejavusans', '', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setAutoPageBreak(false);
        $pdf->setTextColor(0, 0, 0, 100);
        $pdf->setDrawColor(0, 0, 0, 100);

        $pdf->AddPage();
        $pdf->setMargins(0, 0, 0);
        $pdf->SetLineStyle([ 'width' => 0.1, 'color' => [ 255, 255, 255 ] ]);
        $pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight());
        $MDM = [
            'android.app.extra.PROVISIONING_DEVICE_ADMIN_COMPONENT_NAME'            => 'com.hmdm.launcher/com.hmdm.launcher.AdminReceiver',
            'android.app.extra.PROVISIONING_DEVICE_ADMIN_PACKAGE_DOWNLOAD_LOCATION' => AppStatic::get('MDM') . '/files/a.apk',
            'android.app.extra.PROVISIONING_DEVICE_ADMIN_PACKAGE_CHECKSUM'          => AppStatic::get('MDM_CHECKSUM'),
            'android.app.extra.PROVISIONING_WIFI_SSID'                              => AppStatic::get('WIFI_SSID'),
            'android.app.extra.PROVISIONING_WIFI_SECURITY_TYPE'                     => 'WPA',
            'android.app.extra.PROVISIONING_WIFI_PASSWORD'                          => AppStatic::get('WIFI_PASS'),
            'android.app.extra.PROVISIONING_ADMIN_EXTRAS_BUNDLE'                    => [
                'com.hmdm.DEVICE_ID'    => (string) $entity->udi,
                'com.hmdm.BASE_URL'     => AppStatic::get('MDM')
            ]
        ];
        $pdf->write2DBarcode(
            json_encode($MDM, JSON_UNESCAPED_SLASHES),
            'QRCODE,M',
            4.35,
            4.35,
            39.16,
            39.16,
            [
                'fgcolor' => [ 0, 0, 0, 100 ]
            ]
        );
        $pdf->SetFont('dejavusans', '', 6, '', true);
        $pdf->setXY(0, 44);
        $pdf->Cell(48, 6, 'КОД ЗА ИНСТАЛАЦИЯ', 0, 0, 'C');

        $pdf->write2DBarcode(
            json_encode(
                [
                    'mode'  => 'test-setup',
                    'udi'   => (string) $entity->udi,
                    'key'   => $entity->install_key
                ],
                JSON_UNESCAPED_SLASHES
            ),
            'QRCODE',
            11.11,
            49.87,
            25.69,
            25.69,
            [
                'fgcolor' => [ 0, 0, 0, 100 ]
            ]
        );
        $pdf->SetFont('dejavusans', 'B', 10.5, '', true);
        $pdf->setXY(0, 75);
        $pdf->Cell(48, 10.5, (string) $entity->udi, 0, 0, 'C');

        $pdf->write1DBarcode(
            (string) $entity->udi,
            'C39',
            2,
            87,
            44,
            12,
            null,
            [
                'fgcolor' => [ 0, 0, 0, 100 ]
            ]
        );
        $pdf->setXY(0, 96.5);
        $pdf->Cell(48, 10.5, (string) $entity->udi, 0, 0, 'C');

        return $this->response
            ->setContentTypeByExtension('pdf')
            ->withAddedHeader('Content-Disposition', 'inline')
            ->setBody($pdf->Output('Devices.pdf', 'S'));
    }
    public function getPdfs() : Response
    {
        $file = AppStatic::get('STORAGE_DEVICE_PDF') . '/Devices.pdf';

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
}
