<?php

require_once __DIR__ . '/../../bootstrap.php';

use helpers\AppStatic as App;
use vakata\certificate\Certificate;
use vakata\http\Response;
use vakata\http\Emitter;
use vakata\jwt\JWT;

$req = App::req();
$path = preg_replace('(/cert/?.*$)', '/', $req->getUri()->getPath()) ?? '';

if (!isset($_SERVER['HTTPS']) || empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    (new Emitter())->emit(
        new Response(
            303,
            null,
            ['Location' => preg_replace('(^http:)', 'https:', trim((string)$req->getUri(), '/') . '/') ?? '/']
        )
    );
    die();
}

$cert = '';
$name = '';
if (isset($_SERVER['SSL_CLIENT_VERIFY']) && $_SERVER['SSL_CLIENT_VERIFY'] === 'SUCCESS') {
    if (isset($_SERVER['SSL_CLIENT_M_SERIAL'])) {
        $cert = strtoupper(ltrim(trim($_SERVER['SSL_CLIENT_M_SERIAL']), '0'));
        if (App::get('STORAGE_CERTIFICATES') && isset($_SERVER['SSL_CLIENT_CERT'])) {
            $name = $cert . '_' . md5($_SERVER['SSL_CLIENT_CERT']);
            if (!is_file(App::get('STORAGE_CERTIFICATES') . '/' . $name)) {
                file_put_contents(App::get('STORAGE_CERTIFICATES') . '/' . $name, $_SERVER['SSL_CLIENT_CERT']);
            }
        }
    }
}

if ($req->isCors() || $req->isAjax() || $req->getMethod() === 'OPTIONS') {
    (new Emitter())->emit(
        (new Response(200, null, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET',
            'Access-Control-Allow-Headers' => 'X-Requested-With'
        ]))
            ->setBody(
                $req->getMethod() === 'OPTIONS' ? '' : ($req->getQuery('full') ? $_SERVER['SSL_CLIENT_CERT'] : $cert)
            )
    );
    die();
}

try {
    $token = $req->getCookie(App::get('APPNAME_CLEAN') . '_TOKEN');
    if ($token && is_string($token)) {
        $token = JWT::fromString($token, App::get('ENCRYPTIONKEY'));
    } else {
        throw new Exception('No token');
    }
} catch (\Exception $e) {
    (new Emitter())->emit(new Response(303, null, ['Location' => $path]));
    die();
}
try {
    $data = Certificate::fromString($_SERVER['SSL_CLIENT_CERT']);
    $cert = $data->getSerialNumber() . ' / ' . $data->getAuthorityKeyIdentifier();
} catch (\Throwable $ignore) {
}

$token->setClaim('SSL_CLIENT_M_SERIAL', $cert);
if ($name) {
    $token->setClaim('SSL_CLIENT_M_SERIAL_FILE', $name);
}
$token = $token
    ->setExpiration(time() + App::get('SESSION_TIMEOUT'))
    ->sign(App::get('SIGNATUREKEY'))
    ->toString(App::get('ENCRYPTIONKEY'));
(new Emitter())->emit(
    (new Response(303, null, ['Location' => $path]))
        ->withCookie(
            App::get('APPNAME_CLEAN') . '_TOKEN',
            $token,
            'Path=' . $path . '; HttpOnly' .
                ($req->getUrl()->getScheme() === 'https' ? '; Secure' : '')
        )
);
