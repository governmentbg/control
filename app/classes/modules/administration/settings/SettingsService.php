<?php

declare(strict_types=1);

namespace modules\administration\settings;

use vakata\database\DBInterface;
use helpers\Jobs;
use vakata\config\Config;

class SettingsService
{
    protected DBInterface $db;
    protected Config $config;

    public function __construct(DBInterface $db, Config $config)
    {
        $this->db    = $db;
        $this->config = $config;
    }

    public function status(): array
    {
        return [
            'debug' => $this->config->get('DEBUG'),
            'maintenance' => $this->config->get('MAINTENANCE'),
            'csp' => $this->config->get('CSP'),
            'csrf' => $this->config->get('CSRF'),
            'cors' => $this->config->get('CORS'),
            'gzip' => $this->config->get('GZIP'),
            'https' => $this->config->get('FORCE_HTTPS'),
            'totp' => $this->config->get('FORCE_TFA'),
            'ids' => strlen($this->config->get('IDS')),
            'ratelimit' => $this->config->get('RATELIMIT_REQUESTS') > 0 && $this->config->get('RATELIMIT_SECONDS') > 0,
            'writable' => $this->writeable()
        ];
    }
    protected function writeable(): bool
    {
        $file = $this->config->get('CONFIGFILE');
        try {
            return strlen($file) && is_file($file) && is_writable($file);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function clearCache(): void
    {
        Jobs::cleancache();
    }
    public function updateDatabaseSchema(): void
    {
        Jobs::schema();
    }
    public function clearFiles(): void
    {
        // clear unused uploads
        Jobs::cleanfiles();

        // tmp clean
        Jobs::cleantmp();
    }

    protected function updateConfig(string $key, string $value): void
    {
        $file = $this->config->get('CONFIGFILE');
        if (strlen($file) && is_writable($file)) {
            $data = file_get_contents($file) ?: throw new \RuntimeException();
            if (preg_match('(\b' . preg_quote($key) . '\s*=.*?\r?\n)i', $data)) {
                $data = preg_replace(
                    '(\b' . preg_quote($key) . '\s*=.*?\r?\n)i',
                    $key . ' = ' . $value . "\n",
                    $data
                );
            } else {
                $data = trim($data) . "\n" . $key . ' = ' . $value . "\n";
            }
            file_put_contents($file, $data);
            try {
                Jobs::env();
            } catch (\Exception $ignore) {
            }
        }
    }
    public function setDebug(bool $value): void
    {
        $this->updateConfig('DEBUG', $value ? 'true' : 'false');
    }
    public function setMaintenance(bool $value): void
    {
        $this->updateConfig('MAINTENANCE', $value ? 'true' : 'false');
    }
    public function setGzip(bool $value): void
    {
        $this->updateConfig('GZIP', $value ? 'true' : 'false');
    }
    public function setCors(bool $value): void
    {
        $this->updateConfig('CORS', $value ? 'true' : 'false');
    }
    public function setCsrf(bool $value): void
    {
        $this->updateConfig('CSRF', $value ? 'true' : 'false');
    }
    public function setCsp(bool $value): void
    {
        $this->updateConfig('CSP', $value ? 'true' : 'false');
    }
    public function setIds(bool $value): void
    {
        $this->updateConfig('IDS', $value ? '0' : '');
    }
    public function setHttps(bool $value): void
    {
        $this->updateConfig('FORCE_HTTPS', $value ? 'true' : 'false');
    }
    public function setTotp(bool $value): void
    {
        $this->updateConfig('FORCE_TFA', $value ? 'true' : 'false');
    }
    public function setRatelimit(bool $value): void
    {
        $this->updateConfig('RATELIMIT_REQUESTS', $value ? '10' : '0');
        $this->updateConfig('RATELIMIT_SECONDS', $value ? '2' : '0');
    }

    protected static function adler32(string $path): string
    {
        $a = 1;
        $b = 0;
        $handle = fopen($path, 'r') ?: throw new \RuntimeException();
        while (!feof($handle)) {
            $data = fread($handle, 8192) ?: '';
            $temp = unpack('C*', $data, 0);
            if ($temp) {
                foreach ($temp as $i) {
                    $a = ($a + $i) % 65521;
                    $b = ($b + $a) % 65521;
                }
            }
        }
        fclose($handle);
        return str_pad(dechex(($b << 16) | $a), 8, "0", STR_PAD_LEFT);
    }
    public function listFiles(): array
    {
        $json = [];
        foreach (['app', 'public', 'scripts'] as $dir) {
            $path = realpath($this->config->get('BASEDIR') . '/' . basename($dir));
            if ($path === false) {
                continue;
            }
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO
                )
            );
            foreach ($files as $name => $object) {
                if ($object->isFile()) {
                    $json[$dir . str_replace('\\', '/', substr($name, strlen($path)))] = static::adler32($name);
                }
            }
        }
        foreach (['composer.lock', 'bootstrap.php'] as $file) {
            $json[$file] = static::adler32(
                realpath($this->config->get('BASEDIR') . '/' . $file) ?: throw new \RuntimeException()
            );
        }
        return $json;
    }
}
