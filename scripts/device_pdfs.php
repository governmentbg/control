#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

$db = App::db(false);

$pdf = new TCPDF('P', 'mm', [ 48, 105 ]);
$pdf->AddFont('dejavusans', '', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->setAutoPageBreak(false);
$pdf->setTextColor(0, 0, 0, 100);
$pdf->setDrawColor(0, 0, 0, 100);

foreach ($db->all('SELECT udi, install_key FROM devices ORDER BY udi ASC') as $device) {
    $pdf->AddPage();
    $pdf->setMargins(0, 0, 0);
    $pdf->SetLineStyle([ 'width' => 0.1, 'color' => [ 255, 255, 255 ] ]);
    $pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight());
    $MDM = [
        'android.app.extra.PROVISIONING_DEVICE_ADMIN_COMPONENT_NAME'            => 'com.hmdm.launcher/com.hmdm.launcher.AdminReceiver',
        'android.app.extra.PROVISIONING_DEVICE_ADMIN_PACKAGE_DOWNLOAD_LOCATION' => App::get('MDM') . '/files/a.apk',
        'android.app.extra.PROVISIONING_DEVICE_ADMIN_PACKAGE_CHECKSUM'          => App::get('MDM_CHECKSUM'),
        'android.app.extra.PROVISIONING_WIFI_SSID'                              => App::get('WIFI_SSID'),
        'android.app.extra.PROVISIONING_WIFI_SECURITY_TYPE'                     => 'WPA',
        'android.app.extra.PROVISIONING_WIFI_PASSWORD'                          => App::get('WIFI_PASS'),
        'android.app.extra.PROVISIONING_ADMIN_EXTRAS_BUNDLE'                    => [
            'com.hmdm.DEVICE_ID'    => (string) $device['udi'],
            'com.hmdm.BASE_URL'     => App::get('MDM')
        ]
    ];
    // $pdf->Line(0, 84, 105, 84, [ 'width' => 0.5, 'color' => [ 255, 0, 0 ]]);
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
                'udi'   => (string) $device['udi'],
                'key'   => $device['install_key']
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
    $pdf->Cell(48, 10.5, (string) $device['udi'], 0, 0, 'C');

    $pdf->write1DBarcode(
        (string) $device['udi'],
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
    $pdf->Cell(48, 10.5, (string) $device['udi'], 0, 0, 'C');
}


$pdf->Output(App::get('STORAGE_DEVICE_PDF') . '/Devices.pdf', 'F');
