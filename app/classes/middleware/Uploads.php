<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\files\FileStorage;
use vakata\image\Image;
use vakata\image\ImageException;
use vakata\spreadsheet\Reader as SpreadsheetReader;
use Laminas\Diactoros\Stream;
use vakata\files\File;
use vakata\files\FileStorageInterface;

class Uploads
{
    protected FileStorageInterface $files;
    protected string $path;
    protected string $temp;
    protected string $server;
    protected string $sendfile;
    protected int $maximagesize;
    protected FileStorageInterface $tempfiles;

    public function __construct(
        FileStorageInterface $files,
        string $path = 'upload',
        string $temp = null,
        string $sendfile = '',
        int $maximagesize = 0
    ) {
        $this->files = $files;
        $this->path = $path;
        $this->temp = rtrim($temp ?? sys_get_temp_dir(), '/\\');
        $this->sendfile = rtrim($sendfile, '/\\');
        $this->maximagesize = $maximagesize;

        $this->server = '';
        if ($this->sendfile) {
            $temp = explode(':', $this->sendfile, 2);
            $this->server = $temp[0];
            $this->sendfile = $temp[1] ?? '';
            if (!in_array($this->server, ['apache','nginx'])) {
                $this->sendfile = '';
            }
        }

        if (!is_dir($this->temp . '/uploads/')) {
            mkdir($this->temp . '/uploads/', 0777);
        }
        $this->tempfiles = new FileStorage($this->temp, 'uploads', $this->temp);

        if ($this->sendfile) {
            $this->temp = $this->sendfile;
        }
    }
    public function __invoke(Request $req, callable $next): Response
    {
        $url   = $req->getUrl();
        $user  = $req->getAttribute('user');

        $slug = $this->path;
        if ($url->getSegment(0) === $slug) {
            if (($req->getMethod() === 'GET' || $req->getMethod() === 'HEAD') && (int)$url->getSegment(1)) {
                @session_write_close();
                $file = $this->files->get($url->getSegment(1));
                if ($req->getQuery('info')) {
                    $url = $url->linkTo($slug . '/' . $file->id() . '/' . $file->name());
                    $thumb = $url . '?w=128&h=128';
                    $sample = null;
                    if ($req->getQuery('sample')) {
                        try {
                            $name = $file->name();
                            $extension = strtolower(substr($name, (int)strrpos($name, '.') + 1));
                            $sample = null;
                            switch ($extension) {
                                case 'xls':
                                case 'xlsx':
                                case 'csv':
                                    $lines = (int)$req->getQuery('sample') ?
                                        min((int)$req->getQuery('sample'), 50) :
                                        50;
                                    $sample = [];
                                    foreach (
                                        (
                                            new SpreadsheetReader(
                                                $file->path() ?? throw new \RuntimeException(),
                                                $extension
                                            )
                                        ) as $line
                                    ) {
                                        $sample[] = $line;
                                        if (--$lines === 0) {
                                            break;
                                        }
                                    }
                                    break;
                                default:
                                    break;
                            }
                        } catch (\Exception $e) {
                            $sample = $e->getMessage();
                        }
                        $sample = $sample;
                    }
                    return new Response(
                        200,
                        json_encode([
                            'id'       => $file->id(),
                            'name'     => $file->name(),
                            'hash'     => $file->hash(),
                            'size'     => $file->size(),
                            'uploaded' => $file->uploaded(),
                            'settings' => $file->settings(),
                            'url'      => $url,
                            'thumb'    => $thumb,
                            'sample'   => $sample,
                        ]) ?: throw new \RuntimeException(),
                        [ 'Content-Type' => 'application/json' ]
                    );
                }
                if ($file->name() !== $url->getSegment(2)) {
                    throw new \Exception('File not found', 404);
                }
                $replace = null;
                if (($req->getQueryParams()['w'] ?? 0) || ($req->getQueryParams()['h'] ?? 0)) {
                    $rname = $file->id() . '_' .
                            $req->getQuery('w', '0', 'int') . 'x' . $req->getQuery('h', '0', 'int');
                    if (is_file($this->temp . '/' . $rname)) {
                        $replace = $this->temp . '/' . $rname;
                    } else {
                        try {
                            $replace = Image::fromPath($file->path() ?? throw new \RuntimeException())
                                ->crop(
                                    min(4096, (int)($req->getQueryParams()['w'] ?? 0)),
                                    min(4096, (int)($req->getQueryParams()['h'] ?? 0)),
                                    isset($file->settings()['thumbnail']) ?
                                        $file->settings()['thumbnail'] : []
                                )
                                ->toString();
                            file_put_contents($this->temp . '/' . $rname, $replace);
                            $replace = $this->temp . '/' . $rname;
                        } catch (ImageException $ignore) {
                        }
                    }
                }
                $name = $file->name();
                $extension = substr($name, (int)strrpos($name, '.') + 1);
                $disposition = in_array(
                    strtolower($extension),
                    ['txt','png','jpg','gif','jpeg','html','htm','mp3','mp4']
                ) ? 'inline' : 'attachment';
                $res = (new Response(200, null, [
                    'Last-Modified' => gmdate(
                        'D, d M Y H:i:s',
                        filemtime($file->path() ?? throw new \RuntimeException()) ?: throw new \RuntimeException()
                    ) . ' GMT',
                    'ETag' => $replace ? md5($replace) : $file->hash(),
                    // counter PHP session cache limiter
                    'Cache-Control' => 'private',
                    'Pragma' => 'private',
                    'Expires' => gmdate('D, d M Y H:i:s', time() + 24 * 3600) . ' GMT',
                    'Content-Disposition' => $disposition . '; ' .
                            'filename="' . preg_replace('([^a-z0-9.-]+)i', '_', $name) . '"; ' .
                            'filename*=UTF-8\'\'' . rawurlencode($name) . '; ' .
                            'size=' . ($replace ? strlen($replace) : (string)$file->size())
                ]));
                $res = $res->setContentTypeByExtension($extension);
                if ($replace) {
                    $rname = $file->id() . '_' .
                            $req->getQuery('w', '0', 'int') . 'x' . $req->getQuery('h', '0', 'int');
                    if ($this->sendfile) {
                        if ($this->server === 'apache') {
                            return $res
                                ->withHeader('X-Sendfile', $this->sendfile . '/' . $rname);
                        }
                        return $res
                            ->withHeader('X-Accel-Redirect', '/' . basename($this->sendfile) . '/' . $rname);
                    }
                    $replace = file_get_contents($replace) ?: throw new \RuntimeException();
                    return $res
                        ->setBody($replace)
                        ->withHeader('Content-Length', (string)strlen($replace));
                }
                $res = $res
                    ->withHeader('Accept-Ranges', 'bytes')
                    ->withHeader('Content-Length', (string)$file->size());
                if ($this->sendfile) {
                    if (!is_file($this->sendfile . '/' . $file->id())) {
                        copy($file->path() ?: throw new \RuntimeException(), $this->sendfile . '/' . $file->id());
                    }
                    if ($this->server === 'apache') {
                        return $res
                            ->withHeader('X-Sendfile', $this->sendfile . '/' . $file->id());
                    }
                    return $res
                        ->withHeader('X-Accel-Redirect', '/' . basename($this->sendfile) . '/' . $file->id());
                }
                $range = $req->getHeaderLine('range');
                if (!empty($range)) {
                    try {
                        if (!preg_match('@^bytes=\d*-\d*(,\d*-\d*)*$@', $range)) {
                            throw new \Exception('Invalid range');
                        }
                        $range = current(explode(',', substr($range, 6)));
                        list($seekBeg, $seekEnd) = explode('-', $range, 2);
                        $seekBeg = max((int)$seekBeg, 0);
                        $seekEnd = !(int)$seekEnd ? ($file->size() - 1) : min((int)$seekEnd, ($file->size() - 1));
                        if ($seekBeg > $seekEnd) {
                            throw new \Exception('Invalid range');
                        }
                        $res = $res
                            ->withHeader(
                                'Content-Range',
                                'bytes ' . $seekBeg . '-' . $seekEnd . '/' . $file->size()
                            )
                            ->withHeader('Content-Length', (string)($seekEnd - $seekBeg + 1))
                            ->withStatus(206);
                    } catch (\Exception $e) {
                        return (new Response(416))
                            ->withHeader('Content-Range', 'bytes */' . $file->size());
                    }
                }
                $res = $res->withBody(new Stream($file->content()));
                return $res;
            }
            if ($req->getMethod() === 'POST') {
                $post = (array)($req->getParsedBody() ?? []);
                $res = (new Response());
                if (isset($post['thumbnail']) && isset($post['id']) && $post['id']) {
                    $file = $this->files->get($post['id']);
                    $settings = $file->settings();
                    $temp = json_decode($post['thumbnail'], true);
                    if (is_array($temp)) {
                        $settings['thumbnail'] = [
                            'x' => (int)$temp['x'],
                            'y' => (int)$temp['y'],
                            'w' => (int)$temp['w'],
                            'h' => (int)$temp['h']
                        ];
                    }
                    $file = $this->files->fromPSRRequest($req, 'image', $user ? $user->getID() : null);
                } elseif (isset($post['settings']) && isset($post['id']) && $post['id']) {
                    $file = $this->files->get($post['id']);
                    $file->setSettings(json_decode($post['settings'], true));
                    $file = $this->files->set($file);
                } else {
                    if ($req->getPost('chunk', 0, 'int') > 0) {
                        // chunk requests are not logged and as such - do not count towards the rate limit
                        $res = $res->withHeader('X-Log', 'no');
                    }
                    if ($req->getPost('temp')) {
                        $file = $this->tempfiles->fromPSRRequest($req, 'file', $user ? $user->getID() : null);
                        return $res
                            ->setBody(json_encode([
                                'id'       => $file->id(),
                                'name'     => $file->name(),
                                'hash'     => $file->hash(),
                                'size'     => $file->size(),
                                'uploaded' => $file->uploaded(),
                                'settings' => $file->settings(),
                                'url'      => '',
                                'thumb'    => ''
                            ]) ?: throw new \RuntimeException())
                            ->withHeader('Content-Type', 'application/json');
                    }
                    $file = $this->files->fromPSRRequest($req, 'file', $user ? $user->getID() : null);
                    if (
                        $this->maximagesize &&
                        $file->isComplete() &&
                        ($pth = $file->path()) &&
                        in_array($file->ext(), [ 'jpg', 'jpeg', 'bitmap', 'bmp', 'png', 'gif', 'webp' ])
                    ) {
                        try {
                            file_put_contents(
                                $pth,
                                Image::fromPath($pth)
                                    ->resizeLongEdge($this->maximagesize, false)
                                    ->toString()
                            );
                            $file = $this->files->set(new File(
                                $file->id(),
                                $file->name(),
                                md5_file($pth) ?: '',
                                $file->uploaded(),
                                filesize($pth) ?: 0,
                                $file->settings(),
                                true,
                                $file->path()
                            ));
                        } catch (\Exception $ignore) {
                        }
                    }
                }
                $url = $url->linkTo($slug . '/' . $file->id() . '/' . $file->name());
                $thumb = $url . '?w=128&h=128';
                return $res
                    ->setBody(json_encode([
                        'id'       => $file->id(),
                        'name'     => $file->name(),
                        'hash'     => $file->hash(),
                        'size'     => $file->size(),
                        'uploaded' => $file->uploaded(),
                        'settings' => $file->settings(),
                        'url'      => $url,
                        'thumb'    => $thumb
                    ]) ?: throw new \RuntimeException())
                    ->withHeader('Content-Type', 'application/json');
            }
        }
        return $next($req);
    }
}
