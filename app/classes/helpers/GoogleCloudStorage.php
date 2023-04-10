<?php

declare(strict_types=1);

namespace helpers;

class GoogleCloudStorage
{
    const BASEURI = 'https://storage.googleapis.com';
    
    protected string $bucket;
    protected string $token;
    
    public static function fromFile(string $path, string $defaultBucket = ""): self
    {
        $auth = json_decode(file_get_contents($path), true);
        return self::fromKey($auth['client_email'], $auth['private_key'], $defaultBucket);
    }
    public static function fromKey(string $email, string $key, string $defaultBucket = ""): self
    {
        $token = new \vakata\jwt\JWT(
            [
                "iss" => $email,
                "exp" => time() + 1800,
                "iat" => time(),
                "scope" => implode(
                    ' ',
                    [
                        "https://www.googleapis.com/auth/iam",
                        "https://www.googleapis.com/auth/devstorage.full_control"
                    ]
                ),
                "sub" => $email,
            ],
            'RS256'
        );
        $token->sign($key);
        return new self($token->toString(), $defaultBucket);
    }
    public function __construct(string $token, string $defaultBucket = "")
    {
        $this->token = $token;
        $this->bucket = $defaultBucket;
    }
    public function uploadFile(string $path, ?string $name = null, ?string $bucket = null): void
    {
        $path = realpath($path);
        $name = $name ?? basename($path);
        $bucket = $bucket ?? $this->bucket;
        file_get_contents(
            self::BASEURI . '/upload/storage/v1/b/' . $bucket . '/o?uploadType=media&name=' . $name,
            false,
            stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => '' .
                        'Content-Type: ' . (mime_content_type($path) ?: 'application/octet-stream') . "\r\n" .
                        'Authorization: Bearer ' . $this->token . "\r\n",
                    'content' => file_get_contents($path)
                ]
            ])
        );
        $meta = [];
        if (preg_match('(\.js$)i', $path)) {
            $meta['contentType'] = 'application/javascript';
        }
        if (preg_match('(\.css$)i', $path)) {
            $meta['contentType'] = 'text/css';
        }
        if (preg_match('(\.html$)i', $path)) {
            $meta['cacheControl'] = 'no-cache, no-store, max-age=0';
        }
        if (count($meta)) {
            $this->setMetadata($name, $meta, $bucket);
        }
    }
    public function setMetadata(string $name, array $meta, ?string $bucket = null): void
    {
        $bucket = $bucket ?? $this->bucket;
        file_get_contents(
            self::BASEURI . '/storage/v1/b/' . $bucket . '/o/' . urlencode($name),
            false,
            stream_context_create([
                'http' => [
                    'method' => 'PATCH',
                    'header' => '' .
                        'Content-Type: application/json' . "\r\n" .
                        'Authorization: Bearer ' . $this->token . "\r\n",
                    'content' => json_encode($meta)
                ]
            ])
        );
    }
    public function getMetadata(string $name, ?string $bucket = null): array
    {
        $bucket = $bucket ?? $this->bucket;
        $data = file_get_contents(
            self::BASEURI . '/storage/v1/b/' . $bucket . '/o/' . urlencode($name),
            false,
            stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => '' .
                        'Authorization: Bearer ' . $this->token . "\r\n"
                ]
            ])
        ) ?: '';
        return json_decode($data, true) ?? [];
    }
    public function uploadDirectory(string $path, ?string $name = null, ?string $bucket = null)
    {
        $path = realpath($path);
        $name = $name ?? basename($path);
        $bucket = $bucket ?? $this->bucket;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $k => $object) {
            if ($object->isFile()) {
                $this->uploadFile($k, ltrim($name . str_replace('\\', '/', substr($k, strlen($path))), '/'), $bucket);
            }
        }
    }
    public function listBucket(?string $bucket = null): array
    {
        $bucket = $bucket ?? $this->bucket;
        $json = file_get_contents(
            self::BASEURI . '/storage/v1/b/' . $bucket . '/o',
            false,
            stream_context_create([
                'http' => [
                    'header' => 'Authorization: Bearer ' . $this->token
                ]
            ])
        );
        $json = json_decode($json, true);
        $temp = [];
        foreach ($json['items'] ?? [] as $o) {
            $temp[$o['id']] = $o['name'];
        }
        return $temp;
    }
    public function deleteFile(string $name, ?string $bucket = null): void
    {
        $bucket = $bucket ?? $this->bucket;
        file_get_contents(
            self::BASEURI . '/storage/v1/b/' . $bucket . '/o/' . urlencode($name),
            false,
            stream_context_create([
                'http' => [
                    'method' => 'DELETE',
                    'header' => 'Authorization: Bearer ' . $this->token
                ]
            ])
        );
    }
}
