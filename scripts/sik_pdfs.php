#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use helpers\AppStatic as App;

$db = App::db(false);
$getQRPayloadData = function(array $sik, string $mode, string $key) : string
{
    $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
    $payload = [
        'mode'  => $mode,
        'sik'   => $sik['num'],
        'key'   => $mode === 'test-sik' ? $sik['test_key'] : $sik['prod_key']
    ];
    $encrypted = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(json_encode($payload), '', $nonce, $key);

    return base64_encode($nonce . $encrypted);
};

$saturday = new TCPDF();
$saturday->setPrintHeader(false);
$saturday->setPrintFooter(false);
$saturday->setAutoPageBreak(false);
$saturday->setTextColor(0, 0, 0, 100);
$saturday->setDrawColor(0, 0, 0, 100);
$saturday->setMargins(0, 0, 0, true);

$sunday = new TCPDF();
$sunday->setPrintHeader(false);
$sunday->setPrintFooter(false);
$sunday->setAutoPageBreak(false);
$sunday->setTextColor(0, 0, 0, 100);
$sunday->setDrawColor(0, 0, 0, 100);
$sunday->setMargins(0, 0, 0, true);

$election = $db->one('SELECT election, keyenc FROM elections WHERE enabled = 1 ORDER BY election DESC LIMIT 1');
$election['keyenc'] = base64_decode($election['keyenc']);

foreach (
    $db->all(
        'SELECT num, test_key, prod_key, mik, address FROM siks WHERE election = ? ORDER BY num ASC',
        [ $election['election'] ]
    ) as $sik) {
        $saturday->AddPage();
        $saturday->Rect(0, 0, $saturday->getPageWidth(), $saturday->getPageHeight());
        $saturday->SetFont('sofiasansb', '', 12, '', true);
        $saturday->setXY(115.9, 39.198 - 2.88);
        $saturday->Cell(81.767, 10, 'ЗА СЪБОТА - ТЕСТ В СЕКЦИЯТА', 0, 0, 'L');
        $saturday->setXY(115.9, 44.49 - 2.88);
        $saturday->Cell(74.8, 10, 'СИК № ' . $sik['num'], 0, 0, 'L');
        $saturday->SetFont('sofiasans', '', 11, '', true);
        $saturday->setXY(115.9, 49.781 - 2.22 + 1.74);
        $saturday->MultiCell(74.8, 10, 'РИК ' . sprintf('%02d', $sik['mik']) . ', ' . $sik['address'], 0, 'L');
        $saturday->write2DBarcode(
            $getQRPayloadData($sik, 'test-sik', $election['keyenc']),
            'QRCODE,M',
            164.38,
            252.04,
            31.3,
            31.3,
            [
                'fgcolor' => [ 0, 0, 0, 100 ]
            ]
        );

        $sunday->AddPage();
        $sunday->Rect(0, 0, $sunday->getPageWidth(), $sunday->getPageHeight());
        $sunday->SetFont('sofiasansb', '', 12, '', true);
        $sunday->setXY(115.9, 39.198 - 2.88);
        $sunday->Cell(81.767, 10, 'ЗА НЕДЕЛЯ - СЛЕД КРАЯ НА ГЛАСУВАНЕТО', 0, 0, 'L');
        $sunday->setXY(115.9, 44.49 - 2.88);
        $sunday->Cell(74.8, 10, 'СИК № ' . $sik['num'], 0, 0, 'L');
        $sunday->SetFont('sofiasans', '', 11, '', true);
        $sunday->setXY(115.9, 49.781 - 2.22 + 1.74);
        $sunday->MultiCell(74.8, 10, 'РИК ' . sprintf('%02d', $sik['mik']) . ', ' . $sik['address'], 0, 'L');
        $sunday->write2DBarcode(
            $getQRPayloadData($sik, 'real', $election['keyenc']),
            'QRCODE,M',
            164.38,
            252.04,
            31.3,
            31.3,
            [
                'fgcolor' => [ 0, 0, 0, 100 ]
            ]
        );
}


$saturday->Output(App::get('STORAGE_SIK_PDF') . '/Siks_saturday.pdf', 'F');
$sunday->Output(App::get('STORAGE_SIK_PDF') . '/Siks_sunday.pdf', 'F');
