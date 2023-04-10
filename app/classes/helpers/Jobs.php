<?php

declare(strict_types=1);

namespace helpers;

use helpers\AppStatic as App;

class Jobs
{
    public static function cleanfiles(): void
    {
        $process = function (array $ids): array {
            $real = [];
            foreach ($ids as $id) {
                foreach (explode(',', (string)$id) as $i) {
                    if ((int)$i) {
                        $real[(int)$i] = (int)$i;
                    }
                }
            }
            return array_filter(array_unique($real));
        };

        $dbc = App::db();
        $schema = $dbc->parseSchema()->getSchema();

        $used = [];
        // users have a custom name
        $used = array_merge($used, $process($dbc->all("SELECT avatar FROM users")));
        // collect all relations
        foreach ($schema as $table) {
            if ($table['name'] === 'uploads') {
                foreach ($table['relations'] as $relation) {
                    if ($relation['pivot']) {
                        $used = array_merge(
                            $used,
                            $process($dbc->all("SELECT " . $relation['keymap']['id'] . " FROM " . $relation['pivot']))
                        );
                    } else {
                        $used = array_merge(
                            $used,
                            $process($dbc->all("SELECT " . $relation['keymap']['id'] . " FROM " . $relation['table']))
                        );
                    }
                }
            }
        }
        // gather all other possible columns
        foreach ($schema as $table) {
            foreach ($table['columns'] as $column) {
                if (in_array($column['name'], ['image', 'images', 'file', 'files', 'upload', 'uploads'])) {
                    $used = array_merge(
                        $used,
                        $process($dbc->all("SELECT " . $column['name'] . " FROM " . $table['name']))
                    );
                }
            }
        }
        // search text fields (for links)
        foreach ($schema as $table) {
            foreach ($table['columns'] as $column) {
                if (strpos($column['type'], 'text') !== false || strpos($column['type'], 'varchar(') === 0) {
                    foreach (
                        $dbc->all(
                            "SELECT " . $column['name'] . "
                             FROM " . $table['name'] . "
                             WHERE " . $column['name'] . " LIKE '%upload\\\\\\\\/%' " .
                            " OR " . $column['name'] . " LIKE '%upload/%'"
                        ) as $row
                    ) {
                        $data = array_filter(array_map(function ($v) {
                            return (int)$v;
                        }, array_slice(preg_split('(upload\\\?/)', $row) ?: throw new \RuntimeException(), 1)));
                        $used = array_merge($used, $data);
                    }
                }
            }
        }

        $used = array_filter(array_unique($used));
        // actual clean
        $statement = $dbc->prepare("DELETE FROM uploads WHERE id = ? AND uploaded < ?");
        $uploaded = date('Y-m-d H:i:s', strtotime('-24 hours'));
        foreach ($dbc->all("SELECT id, location FROM uploads", [], "id", true) as $id => $location) {
            if (!in_array($id, $used)) {
                if (
                    $statement->execute([$id, $uploaded])->affected() > 0 &&
                    is_file(App::get('STORAGE_UPLOADS') . '/' . $location)
                ) {
                    @unlink(App::get('STORAGE_UPLOADS') . '/' . $location);
                }
            }
        }
    }

    public static function cleantmp(): void
    {
        $threshold = strtotime('-48 hours');
        $files = scandir(App::get('STORAGE_TMP'));
        if (!$files) {
            $files = [];
        }
        foreach ($files as $file) {
            if (
                is_file(App::get('STORAGE_TMP') . '/' . $file) &&
                filemtime(App::get('STORAGE_TMP') . '/' . $file) < $threshold &&
                $file !== '.gitignore'
            ) {
                @unlink(App::get('STORAGE_TMP') . '/' . $file);
            }
        }
        if (App::get('SENDFILE')) {
            $dir = explode(':', App::get('SENDFILE'), 2);
            $dir = $dir[1] ?? '';
            $dir = rtrim($dir, '/\\');
            $files = is_dir($dir) ? scandir($dir) : [];
            if (!$files) {
                $files = [];
            }
            foreach ($files as $file) {
                if (
                    is_file($dir . '/' . $file) &&
                    filemtime($dir . '/' . $file) < $threshold &&
                    $file !== '.gitignore'
                ) {
                    @unlink($dir . '/' . $file);
                }
            }
        }
    }

    public static function permissions(): void
    {
        // storage dirs
        foreach (
            [
                'STORAGE_UPLOADS',
                'STORAGE_CACHE',
                'STORAGE_SESSION',
                'STORAGE_LOG',
                'STORAGE_TMP',
                'STORAGE_INTL',
                'STORAGE_DATABASE',
                'STORAGE_MAIL',
                'STORAGE_REQ',
                'STORAGE_CERTIFICATES',
                'STORAGE_KEYS'
            ] as $dir
        ) {
            $dir = App::get($dir);
            if (is_dir($dir) && !is_writable($dir)) {
                @chmod($dir, 0777);
            }
        }

        // cronjobs
        $jobdir = App::get('BASEDIR') . '/scripts/';
        $files = scandir($jobdir);
        if (!$files) {
            $files = [];
        }
        foreach ($files as $item) {
            if (is_file($jobdir . '/' . $item) && strpos($item, '.php') !== false) {
                @chmod($jobdir . '/' . $item, 0755);
            }
        }
    }

    public static function schema(): void
    {
        App::schema(true);
    }

    public static function migrations(): void
    {
        App::migrations()->up();
        App::schema(true);
    }

    public static function cleancache(): void
    {
        $cache = App::cache();
        $cache->clear();
        $cache->clear('umd');
        $cache->clear(App::get('APPNAME_CLEAN'));
    }

    public static function encrypt(): void
    {
        if (!App::get('PASSWORDKEY')) {
            if (!is_writeable(App::get('BASEDIR') . '/.env')) {
                throw new \Exception('Config file not writeable - input PASSWORDKEY manually.');
            }
            $pkey = \vakata\random\Generator::string(32);
            file_put_contents(
                App::get('BASEDIR') . '/.env',
                preg_replace(
                    '(PASSWORDKEY.*?\n)ui',
                    '',
                    file_get_contents(App::get('BASEDIR') . '/.env') ?: throw new \RuntimeException()
                ) . "\n" . 'PASSWORDKEY = "' . $pkey . '"' . "\n"
            );
        } else {
            $pkey = App::get('PASSWORDKEY');
        }

        $dbc = App::db();
        foreach (
            $dbc->get(
                "SELECT id, data FROM user_providers WHERE provider = ?",
                ['PasswordDatabase'],
                'id',
                true
            ) as $user => $hash
        ) {
            $parts = explode("\n", $hash);
            if (count($parts) !== 3) {
                $iv = openssl_random_pseudo_bytes(12) ?: throw new \RuntimeException();
                $tag = openssl_random_pseudo_bytes(16) ?: throw new \RuntimeException();
                $cipher = openssl_encrypt($hash, 'aes-256-gcm', $pkey, 0, $iv, $tag) ?: throw new \RuntimeException();
                $passwd = base64_encode($iv) . "\n" . base64_encode($tag) . "\n" . $cipher;
                $dbc->query(
                    "UPDATE user_providers SET data = ? WHERE provider = ? AND id = ?",
                    [ $passwd, 'PasswordDatabase', $user ]
                );
            }
        }
        static::env();
    }
    public static function decrypt(): void
    {
        if (!App::get('PASSWORDKEY')) {
            throw new \Exception('No decryption key.');
        }

        $dbc = App::db();
        foreach (
            $dbc->get(
                "SELECT id, data FROM user_providers WHERE provider = ?",
                ['PasswordDatabase'],
                'id',
                true
            ) as $user => $hash
        ) {
            $parts = explode("\n", $hash);
            if (count($parts) === 3) {
                $iv = base64_decode($parts[0]);
                $tag = base64_decode($parts[1]);
                $passwd = openssl_decrypt($parts[2], 'aes-256-gcm', App::get('PASSWORDKEY'), 0, $iv, $tag);
                if ($passwd !== false) {
                    $dbc->query(
                        "UPDATE user_providers SET data = ? WHERE provider = ? AND id = ?",
                        [ $passwd, 'PasswordDatabase', $user ]
                    );
                }
            }
        }

        if (!is_writeable(App::get('BASEDIR') . '/.env')) {
            throw new \Exception('Config file not writeable - remove PASSWORDKEY manually.');
        }

        file_put_contents(
            App::get('BASEDIR') . '/.env',
            preg_replace(
                '(PASSWORDKEY.*?\n)ui',
                '',
                file_get_contents(App::get('BASEDIR') . '/.env') ?: throw new \RuntimeException()
            )
        );
        static::env();
    }

    public static function env(string $file = '.env'): void
    {
        $file = App::get('BASEDIR') . '/' . basename($file);
        if (is_file($file)) {
            $config = new \vakata\config\Config();
            $config->fromFile($file);
            $data = $config->toArray();
            if (!isset($data['CONFIGFILE'])) {
                $data['CONFIGFILE'] = $file;
            }
            file_put_contents(
                App::get('BASEDIR') . '/.env.php',
                '<?php' . "\n" . '// This file is autogenerated, do not edit manually!' . "\n" .
                (!$config->get('ENVCACHE', false) ? 'return null;' : 'return ' . var_export($data, true) . ';' . "\n")
            );
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        }
    }

    public static function langs(): void
    {
        $dir = App::get('STORAGE_INTL');
        $files = scandir($dir);
        if (!$files) {
            $files = [];
        }
        foreach ($files as $file) {
            if (is_file($dir . '/' . $file) && preg_match('(\.json$)i', $file)) {
                $data = @json_decode(
                    file_get_contents($dir . '/' . $file) ?: throw new \RuntimeException(),
                    true
                );
                if ($data) {
                    file_put_contents(
                        $dir . '/' . $file . '.php',
                        '<?php return ' . var_export($data, true) . ';'
                    );
                    if (function_exists('opcache_compile_file')) {
                        try {
                            @opcache_compile_file($dir . '/' . $file . '.php');
                        } catch (\Exception $ignore) {
                        }
                    }
                }
            }
        }
    }

    public static function static(
        bool $live = true,
        bool $rec = true,
        bool $dev = false,
        string $publish = null,
        string $dir = ''
    ): void
    {
        $dbc = App::db(false);
        $mode = $dbc->one("SELECT mode FROM modes WHERE name = 'real'");
        foreach ($dbc->all("SELECT mik, name FROM miks ORDER BY mik", [], 'mik', true) as $rik => $name) {
            $num = sprintf('%02d', $rik);
            $siks = $dbc->all("SELECT sik, num, address FROM siks WHERE mik = ? AND video = 1 AND insite = 1 ORDER BY num", $rik, 'sik', true);
            $servers = $dbc->all(
                "SELECT r.host 
                 FROM restreamers r, restreamer_miks rm 
                 WHERE r.restreamer = rm.restreamer AND rm.mik = ? AND r.enabled = 1
                 ORDER BY r.restreamer",
                $rik
            );
            $recordings = [];
            foreach ($dbc->all('SELECT r.* FROM recordings r, siks s WHERE s.sik = r.sik AND s.mik = ? AND r.mode = ? ORDER BY r.sik, r.created ASC', [$rik, $mode]) as $recording) {
                if (!isset($recordings[$recording['sik']])) {
                    $recordings[$recording['sik']] = [];
                }
                $recordings[$recording['sik']][] = $recording;
            }
            $rservers = [];
            $i = 0;
            $lstreams = [];
            if ($live) {
                $lstreams = $dbc->all('SELECT sik FROM streams WHERE mode = ? AND ended IS NULL', $mode);
            }
            foreach ($siks as $sik => $data) {
                $video = [];
                if ($live) {
                    $video['l'] = in_array($sik, $lstreams) ?
                        ['https://' . $servers[(++$i) % count($servers)] . '/' . $data['num'] . '.m3u8'] :
                        ['https://' . $servers[(++$i) % count($servers)] . '/' . $data['num'] . '.m3u8'];
                }
                foreach ($recordings[$sik] ?? [] as $recording) {
                    $type = $recording['src'] === 'live' ? 'r' : 'd';
                    if ($type === 'r' && !$rec) {
                        continue;
                    }
                    if ($type === 'd' && !$dev) {
                        continue;
                    }
                    if (!isset($video[$type])) {
                        $video[$type] = [];
                    }
                    $url = $recording['url'];
                    if (strpos($url, $data['num'])) {
                        $temp = explode('/' . $data['num'], $url, 2);
                        $serv = $temp[0] . '/';
                        $rserv = $rservers[$serv] ?? null;
                        if (!$rserv) {
                            $rserv = 'r' . count($rservers);
                            $rservers[$serv] = $rserv;
                        }
                        $url = $rserv . '#' . $temp[1];
                    }
                    $video[$type][] = $url;
                }
                $siks[$sik]['video'] = $video;
            }
            ob_start();
            include __DIR__ . '/../../views/static/rik.php';
            $html = ob_get_clean();
            file_put_contents(App::get('STORAGE_STATIC') . '/rik' . $num . '.html', $html);
        }
        if ($publish === '1') {
            $publish = App::get('BUCKET');
        }
        if ($publish) {
            $uploader = GoogleCloudStorage::fromFile(__DIR__ . '/../../../auth.json');
            $uploader->uploadDirectory(App::get('STORAGE_STATIC'), $dir, $publish);
            $uploader->uploadFile(App::get('STORAGE_STATIC') . '/404.html', '404.html', $publish);
            if ($dir) {
                $uploader->uploadFile(App::get('STORAGE_STATIC') . '/landing.html', 'index.html', $publish);
            }
        }
    }
}
