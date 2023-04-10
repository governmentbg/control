<?php

declare(strict_types=1);

namespace middleware;

use vakata\database\DBInterface;
use Monolog\Logger as Log;
use vakata\http\Request;
use vakata\http\Response;
use Laminas\Diactoros\Response\Serializer as ResponseSerializer;
use Laminas\Diactoros\Request\Serializer as RequestSerializer;
use vakata\random\Generator;
use helpers\Exception;

class Logger
{
    protected Log $log;
    protected DBInterface $dbc;
    protected array $alwaysLog;
    protected array $privateRequests;
    protected ?string $storage;
    protected array $context;
    protected ?string $certificates;
    protected bool $debug;

    public function __construct(
        Log $log,
        DBInterface $dbc,
        array $alwaysLog = [],
        array $privateRequests = [],
        ?string $storage = null,
        array $context = [],
        ?string $certificates = null,
        bool $debug = false
    ) {
        $this->log = $log;
        $this->dbc = $dbc;
        $this->alwaysLog = $alwaysLog;
        foreach ($this->alwaysLog as $k => $v) {
            $this->alwaysLog[$k] = trim($v, '/');
        }
        $this->privateRequests = $privateRequests;
        foreach ($this->privateRequests as $k => $v) {
            $this->privateRequests[$k] = trim($v, '/');
        }
        $this->storage = $storage;
        $this->context = $context;
        $this->certificates = $certificates;
        $this->debug = $debug;
    }

    public function __invoke(Request $req, callable $next): Response
    {
        $lastNotice = '';
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use (&$lastNotice) {
            // do not touch errors where @ is used or that are not marked for reporting
            if ($errno === 0 || !($errno & error_reporting())) {
                return true;
            }
            // do not through, only log "lightweight" errors
            if (in_array($errno, [ E_NOTICE, E_DEPRECATED, E_STRICT, E_USER_NOTICE, E_USER_DEPRECATED ])) {
                $lastNotice = 'PHP Notice: ' . $errstr .
                    ($errfile && $errline ? ' in ' . $errfile . ' on line ' . $errline : '');
                $this->log->notice($lastNotice);
                return true;
            }
            // throw exception for all others
            throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
        });
        try {
            $time = microtime(true);
            $uuid = Generator::uuid();
            $res = $next($req)
                ->withHeader('X-Request-UUID', $uuid);
            $done = microtime(true) - $time;
            $res = $res->withHeader('X-Request-Time', sprintf('%01.5f', $done));
            if (!$res->hasHeader('X-Log') && $done >= 5) {
                $res = $res->withHeader('X-Log', 'Processing slow');
            }
        } catch (\Throwable $e) {
            $user = '';
            $username = '';
            if ($e instanceof Exception) {
                $user = (string)$e->get('userID', '');
                $username = (string)$e->get('userName', '');
                $p = $e->getPrevious();
                if ($p !== null) {
                    $e = $p;
                }
            }
            $message = '' .
                ((int)$e->getCode() ? ' ' . $e->getCode() . ' -' : '') . ' ' .
                $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            $severity = $e instanceof \ErrorException ? $e->getSeverity() : E_ERROR;
            switch ($severity) {
                case E_ERROR:
                case E_RECOVERABLE_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                case E_PARSE:
                    $this->log->error($message);
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                    $this->log->warning($message);
                    break;
                case E_NOTICE:
                case E_USER_NOTICE:
                    $this->log->notice($message);
                    break;
                case E_STRICT:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    $this->log->info($message);
                    break;
                default:
                    $this->log->critical($message);
                    break;
            }
            $code = $e->getCode() >= 200 && $e->getCode() <= 503 ? $e->getCode() : 500;
            $html = '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Error ' . htmlspecialchars((string)$code) . '</title>
                <style>
                    body { font-family: "Helvetica Neue", Arial, sans-serif; text-align:center;
                        font-size:1rem; background-color: #900000; color:white; }
                    h1 { margin:0 0 2rem 0; padding:0; font-size:8rem; }
                    h2 { margin:0 0 2rem 0; padding:0; font-size:2.6rem; }
                    h3 { display:none; margin:4rem 0 2rem 0; padding:0; font-size:1.4rem; }
                    a { background:#e98724; color:white; display:inline-block; padding:1rem 2rem;
                        text-decoration:none; border-radius:5px; }
                    p { position: fixed; bottom:0; left:0; right:0; }
                    pre { text-align:left; margin:2rem; }
                </style>
            </head>
            <body>
                ' .
                ($this->debug ?
                    '<h1>' . htmlspecialchars((string)$code) . '</h1>
                     <h2>' . htmlspecialchars($e->getMessage()) . '</h2>
                     <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>
                     <h3><a href="javascript:window.history.back();">&larr;</a></h3>
                     <p><code>' . htmlspecialchars($uuid ?? '') . '</code></p>' :
                    '<h2><br /><br />Try again later</h2>
                     <h3><a href="javascript:window.history.back();">&larr;</a></h3>
                     <p><code>' . htmlspecialchars($uuid ?? '') . '</code></p>'
                ) .
            '</body>
            </html>';
            $done = isset($time) && $time ? sprintf('%01.5f', microtime(true) - $time) : '';
            $res = new Response(
                (int)$code,
                $html,
                [
                    'X-Log' => $e->getMessage(),
                    'X-Request-UUID' => $uuid ?? '',
                    'X-Request-Time' => $done,
                    'X-User' => $user,
                    'X-Username' => $username
                ]
            );
        }
        if ($res->hasHeader('X-Log') && $res->getHeaderLine('X-Log') === 'no') {
            return $res
                ->withoutHeader('X-Log')
                ->withoutHeader('X-Client-IP')
                ->withoutHeader('X-Request-Time')
                ->withoutHeader('X-User')
                ->withoutHeader('X-Username');
        }

        $msg = '[' . $req->getMethod() . '] ' . '/' . trim($req->getUrl()->getRealPath(), '/');
        $lvl = 'debug';

        if ($lastNotice !== '' && !$res->hasHeader('X-Log')) {
            $res = $res->withHeader('X-Log', $lastNotice);
        }

        if ($res->hasHeader('X-Log')) {
            $log = $res->getHeaderLine('X-Log');
            $matches = [];
            preg_match_all('(IDS|CSRF|CSP|ECT|XSS|RATE)', $log, $matches);
            if (isset($matches[0]) && count($matches[0])) {
                foreach ($matches[0] as $keyword) {
                    $msg = '[' . $keyword . '] ' . $msg;
                }
            }
            $log = trim(preg_replace(['(IDS|CSRF|CSP|ECT|XSS|RATE|CORS)', '(\s+)'], ['', ' '], $log));
            $msg .= ' ' . $log;
        }
        if ($res->getStatusCode() >= 500) { // server errors
            $msg = '[' . $res->getStatusCode() . '] ' . $msg;
            $lvl = 'error';
        } elseif ($res->getStatusCode() >= 400) { // user errors
            $msg = '[' . $res->getStatusCode() . '] ' . $msg;
            $lvl = 'warning';
            /* @phan-suppress-next-line PhanImpossibleCondition */
        } elseif (strlen($lastNotice)) {
            $lvl = 'notice';
        } elseif ($res->hasHeader('X-Log')) {
            $lvl = 'info';
        } elseif (in_array(trim($req->getUrl()->getRealPath(), '/'), $this->alwaysLog)) {
            $lvl = 'info';
        } elseif (in_array($req->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) { // changes to the state
            $lvl = 'info';
        } else { // everything else
            $lvl = 'debug';
        }

        if ($lvl !== 'debug') {
            $isRequestPrivate  = $req->getMethod() === 'POST' &&
                in_array($req->getUrl()->getRealPath(), $this->privateRequests);
            $isResponsePrivate = $res->hasHeader('X-Private');

            $rs = ResponseSerializer::toString($res) . "\r\n";
            $rs = preg_replace('(Set-Cookie\:[^\n]*\n)i', 'Set-Cookie: *** PRIVATE ***' . "\r\n", $rs) ?? '';
            $rq = explode("\r\n\r\n", RequestSerializer::toString($req))[0];
            if (count($req->getPost())) {
                $rq .= "\r\n\r\n" .
                json_encode(
                    $req->getPost(),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                ) .
                "\r\n";
            }
            $rq = preg_replace(
                '(Cookie\:[^\n]*\n)i',
                'Cookie: *** PRIVATE ***' . "\r\n",
                $rq
            ) ?: throw new \RuntimeException();
            $rq = preg_replace(
                '(Authorization\:[^\n]*\n)i',
                'Authorization: *** PRIVATE ***' . "\r\n",
                $rq
            ) ?: throw new \RuntimeException();
            if ($isRequestPrivate) {
                $temp = explode("\r\n\r\n", $rq);
                $rq = $temp[0];
                if (isset($temp[1]) && !empty($temp[1])) {
                    $rq .= "\r\n\r\n" . "*** PRIVATE ***";
                }
            }
            $time = time();
            // filesystem storage
            if ($this->storage !== 'DATABASE') {
                $temp = explode("\r\n\r\n", $rq);
                $rq = $temp[0] . "\r\n\r\n" . "*** SKIPPED ***";
                $uuid = $res->getHeaderLine('X-Request-UUID');
                if (isset($this->storage) && strlen($this->storage) && isset($temp[1]) && !empty($temp[1]) && $uuid) {
                    $path = rtrim($this->storage, '/') .
                        '/' . date('Y', $time) . '/' . date('m', $time) .  '/' . date('d', $time);
                    if (!is_dir($path)) {
                        @mkdir($path, 0777, true);
                    }
                    file_put_contents($path . '/' . $uuid . '.req', $temp[1]);
                }
                $temp = explode("\r\n\r\n", $rs);
                $rs = $temp[0] . "\r\n\r\n" . "*** SKIPPED ***";
                if (
                    isset($this->storage) &&
                    strlen($this->storage) &&
                    isset($temp[1]) &&
                    !empty($temp[1]) &&
                    $uuid &&
                    !$isResponsePrivate
                ) {
                    $path = rtrim($this->storage, '/') .
                        '/' . date('Y', $time) . '/' . date('m', $time) .  '/' . date('d', $time);
                    if (!is_dir($path)) {
                        @mkdir($path, 0777, true);
                    }
                    file_put_contents($path . '/' . $uuid . '.res', $temp[1]);
                }
            }
            $temp = $res->getHeader('X-Context');
            $context = [];
            $cnt = 0;
            foreach ($temp as $line) {
                if (preg_match('(^[A-Z_-]+: )', $line)) {
                    $line = explode(': ', $line, 2);
                    $context[$line[0]] = $line[1];
                } else {
                    $context['context_' . ($cnt++)] = $line;
                }
            }
            $context = array_merge($this->context, $context);
            if ($req->hasCertificate()) {
                $context['SSL_CLIENT_M_SERIAL'] = $req->getCertificateNumber();
                if ($this->certificates && ($cert = $req->getCertificate())) {
                    $file = $req->getCertificateNumber() . '_' . md5($cert);
                    if (!is_file($this->certificates . '/' . $file)) {
                        file_put_contents($this->certificates . '/' . $file, $cert);
                    }
                    $context['SSL_CLIENT_M_SERIAL_FILE'] = $file;
                }
            }
            $this->dbc->table('log')->insert([
                'created' => date('Y-m-d H:i:s', $time),
                'lvl' => $lvl,
                'message' => $msg,
                'context' => json_encode(
                    $context,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                ),
                'request' => $rq,
                'response' => $isResponsePrivate ? '*** PRIVATE ***' : $rs,
                'ip' => (string)ClientIP::ip(),
                'usr' => $res->getHeaderLine('X-User') ? $res->getHeaderLine('X-User') : null,
                'usr_name' => $res->getHeaderLine('X-Username') ? $res->getHeaderLine('X-Username') : null
            ]);
        }
        return $res
            ->withoutHeader('X-Context')
            ->withoutHeader('X-Log')
            ->withoutHeader('X-Client-IP')
            ->withoutHeader('X-Request-Time')
            ->withoutHeader('X-User')
            ->withoutHeader('X-Username');
    }
}
