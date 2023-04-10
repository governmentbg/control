<?php

declare(strict_types=1);

namespace helpers;

use RuntimeException;
use vakata\database\DBInterface;

class Migrations
{
    protected DBInterface $db;
    protected string $path;
    protected array $features;
    protected string $statusFile;

    public static function setup(): void
    {
        $bdir = realpath(__DIR__ . '/../../../') ?: throw new \RuntimeException();
        $ddir = $bdir . '/storage/database';
        $name = basename($bdir);

        set_time_limit(0);

        $appn = readline("Application name: (" . basename($bdir) . "): ");
        if (!$appn) {
            $appn = basename($bdir);
        }
        $dflt = strtoupper(preg_replace('([^a-z0-9_]+)ui', '_', $appn) ?: throw new RuntimeException());
        $appc = readline("Application name CLEAN (only latin chars, numbers and _): (" . $dflt . "): ");
        if (!$appc) {
            $appc = $dflt;
        }
        $appc = strtoupper(preg_replace('([^a-z0-9_]+)ui', '_', $appc) ?: throw new RuntimeException());

        $connection = '';
        echo 'DATABASE CONNECTION:' . "\r\n";
        do {
            $type = readline("Database engine: (MYSQL/postgre/oracle): ");
            if (!$type) {
                $type = 'mysql';
            }
            $type = strtolower($type);
        } while (!in_array($type, ['mysql','oracle','postgre']));
        if (is_file($ddir . '/' . $type . '/status')) {
            echo 'Can not install over existing database' . "\r\n";
            exit(1);
        }
        $user = readline("Username: (root): ");
        if (!$user) {
            $user = 'root';
        }
        $pass = readline("Password: ");
        $host = readline("Hostname: (127.0.0.1): ");
        if (!$host) {
            $host = '127.0.0.1';
        }
        $name = readline("Database name: (" . strtolower($appn) . "): ");
        if (!$name) {
            $name = strtolower($appn);
        }
        $cms = readline("CMS: (y/N): ");
        $cms = !$cms || strtolower($cms) !== 'y' ? 'false' : 'true';
        $forum = readline("FORUMS: (y/N): ");
        $forum = !$forum || strtolower($forum) !== 'y' ? 'false' : 'true';
        $help = readline("HELP: (y/N): ");
        $help = !$help || strtolower($help) !== 'y' ? 'false' : 'true';
        $messaging = readline("MESSAGING: (y/N): ");
        $messaging = !$messaging || strtolower($messaging) !== 'y' ? 'false' : 'true';

        $connection = $type . '://' . $user . ($pass ? ':' . $pass : '') . '@' . $host . '/' . $name;
        // update .env file
        $file = $bdir . '/.env';
        file_put_contents(
            $file,
            'APPNAME = "' . $appn . '"' . "\n" .
            preg_replace('(APPNAME.*?\n)', '', file_get_contents($file) ?: throw new \RuntimeException())
        );
        file_put_contents(
            $file,
            'APPNAME_CLEAN = "' . $appc . '"' . "\n" .
            preg_replace('(APPNAME_CLEAN.*?\n)', '', file_get_contents($file) ?: throw new \RuntimeException())
        );
        file_put_contents(
            $file,
            'DATABASE = "' . $connection . '"' . "\n" .
            preg_replace('(DATABASE.*?\n)', '', file_get_contents($file) ?: throw new \RuntimeException())
        );
        file_put_contents(
            $file,
            'CMS = ' . $cms . "\n" . preg_replace(
                '(CMS.*?\n)',
                '',
                file_get_contents($file) ?: throw new \RuntimeException()
            )
        );
        file_put_contents(
            $file,
            'HELP = ' . $help . "\n" . preg_replace(
                '(HELP.*?\n)',
                '',
                file_get_contents($file) ?: throw new \RuntimeException()
            )
        );
        file_put_contents(
            $file,
            'FORUM = ' . $forum . "\n" . preg_replace(
                '(FORUM.*?\n)',
                '',
                file_get_contents($file) ?: throw new \RuntimeException()
            )
        );
        file_put_contents(
            $file,
            'MESSAGING = ' . $messaging . "\n" .
            preg_replace('(MESSAGING.*?\n)', '', file_get_contents($file) ?: throw new \RuntimeException())
        );
    }

    public function __construct(
        DBInterface $db,
        string $path,
        array $features = [],
        string $statusFile = 'status'
    ) {
        $this->db = $db;
        $this->path = rtrim($path, '/') . DIRECTORY_SEPARATOR . $db->driverName() . DIRECTORY_SEPARATOR;
        if (!is_dir($this->path) || !is_dir($this->path . 'base')) {
            throw new \Exception('Unsupported database');
        }
        $this->features = $features;
        $this->statusFile = $statusFile;
    }

    protected function execute(string $sql): void
    {
        $sql = str_replace("\r", '', $sql);
        $sql = preg_replace('(\n+)', "\n", $sql) ?: throw new \RuntimeException();
        $sql = explode(";\n", $sql . "\n");
        foreach (array_filter(array_map("trim", $sql)) as $q) {
            $q = preg_replace('(--.*\n)', '', $q) ?: throw new \RuntimeException();
            $this->db->query($q);
        }
    }
    protected function install(string $migration): void
    {
        $schema = $this->path . $migration . DIRECTORY_SEPARATOR . 'schema.sql';
        if (is_file($schema)) {
            $this->execute(file_get_contents($schema) ?: throw new \RuntimeException());
        }
        $data = $this->path . $migration . DIRECTORY_SEPARATOR . 'data.sql';
        if (is_file($data)) {
            $sql = file_get_contents($data) ?: throw new \RuntimeException();
            $this->execute($sql);
        }
    }
    protected function uninstall(string $migration): void
    {
        $migration = $this->path . $migration . DIRECTORY_SEPARATOR . 'uninstall.sql';
        if (is_file($migration)) {
            $this->execute(file_get_contents($migration) ?: throw new \RuntimeException());
        }
    }
    protected function status(): array
    {
        $migrations = is_file($this->path . $this->statusFile) ?
            array_filter(
                file(
                    $this->path . $this->statusFile,
                    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
                ) ?: throw new \RuntimeException()
            ) :
            [];
        return $migrations;
    }
    protected function applied(string $migration): void
    {
        $status = array_unique(array_merge($this->status(), [$migration]));
        file_put_contents($this->path . $this->statusFile, implode("\n", $status));
    }
    protected function removed(string $migration): void
    {
        $status = array_diff($this->status(), [$migration]);
        file_put_contents($this->path . $this->statusFile, implode("\n", $status));
    }
    protected function collect(): array
    {
        $migrations = [];
        foreach (scandir($this->path . 'base/_core/') ?: [] as $migration) {
            if (
                !in_array($migration, ['.', '..']) &&
                is_dir($this->path . 'base/_core/' . $migration)
            ) {
                $migrations[] = 'base/_core/' . $migration;
            }
        }
        foreach (scandir($this->path . 'base') ?: [] as $item) {
            if (
                !in_array($item, ['.', '..']) &&
                is_dir($this->path . 'base/' . $item) &&
                (isset($this->features[strtoupper($item)]) && $this->features[strtoupper($item)])
            ) {
                foreach (scandir($this->path . 'base/' . $item) ?: [] as $migration) {
                    if (
                        !in_array($migration, ['.', '..']) &&
                        is_dir($this->path . 'base/' . $item . '/' . $migration)
                    ) {
                        $migrations[] = 'base/' . $item . '/' . $migration;
                    }
                }
            }
        }
        foreach (scandir($this->path . 'app/_core/') ?: [] as $migration) {
            if (
                !in_array($migration, ['.', '..']) &&
                is_dir($this->path . 'app/_core/' . $migration)
            ) {
                $migrations[] = 'app/_core/' . $migration;
            }
        }
        foreach (scandir($this->path . 'app') ?: [] as $item) {
            if (
                !in_array($item, ['.', '..']) &&
                is_dir($this->path . 'app/' . $item) &&
                (isset($this->features[strtoupper($item)]) && $this->features[strtoupper($item)])
            ) {
                foreach (scandir($this->path . 'app/' . $item) ?: [] as $migration) {
                    if (
                        !in_array($migration, ['.', '..']) &&
                        is_dir($this->path . 'app/' . $item . '/' . $migration)
                    ) {
                        $migrations[] = 'app/' . $item . '/' . $migration;
                    }
                }
            }
        }
        return $migrations;
    }
    protected function removable(string $migration): bool
    {
        return is_file($this->path . $migration . DIRECTORY_SEPARATOR . 'uninstall.sql');
    }
    public function current(): array
    {
        return $this->status();
    }
    public function waiting(): array
    {
        return array_diff($this->collect(), $this->status());
    }
    public function reset(): self
    {
        $status = $this->status();
        $migrations = $this->collect();
        foreach (array_reverse($migrations) as $migration) {
            if (in_array($migration, $status)) {
                $this->uninstall($migration);
            }
        }
        foreach ($migrations as $migration) {
            $parts = explode('/', $migration);
            if ($parts[0] === 'base') {
                if ($this->removable($migration)) {
                    if (
                        $parts[1] === '_core' ||
                        (isset($this->features[strtoupper($parts[1])]) && $this->features[strtoupper($parts[1])])
                    ) {
                        $this->install($migration);
                        $this->applied($migration);
                    }
                }
            }
        }
        return $this;
    }
    public function up(): self
    {
        $status = $this->status();
        foreach ($this->collect() as $migration) {
            if (!in_array($migration, $status)) {
                $this->install($migration);
                $this->applied($migration);
            }
        }
        return $this;
    }
    public function down(): self
    {
        $status = $this->status();
        foreach (array_reverse($this->collect()) as $migration) {
            if (in_array($migration, $status)) {
                $this->uninstall($migration);
                $this->removed($migration);
            }
        }
        return $this;
    }
    public function to(array $desired): self
    {
        $status = $this->status();
        foreach (array_reverse($this->collect()) as $migration) {
            if (in_array($migration, $status) && !in_array($migration, $desired)) {
                $this->uninstall($migration);
                $this->removed($migration);
            }
        }
        foreach ($this->collect() as $migration) {
            if (in_array($migration, $desired) && !in_array($migration, $status)) {
                $this->install($migration);
                $this->applied($migration);
            }
        }
        return $this;
    }
}
