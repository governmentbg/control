<?php

declare(strict_types=1);

namespace modules\administration\modules;

use vakata\cache\CacheInterface;
use vakata\database\DBInterface;

class ModulesService
{
    protected DBInterface $db;
    protected CacheInterface $cache;

    public function __construct(DBInterface $db, CacheInterface $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
    }
    public function getModules(): array
    {
        $base = __DIR__ . '/../../';
        $parents = scandir($base);
        if (!$parents) {
            throw new \Exception('Can not read modules');
        }

        $temp = [];
        foreach ($parents as $parent) {
            if ($parent === '.' || $parent === '..' || !is_dir($base . $parent)) {
                continue;
            }
            $modules = scandir($base . $parent);
            if (!$modules) {
                continue;
            }
            foreach ($modules as $module) {
                if ($module === '.' || $module === '..' || !is_dir($base . $parent . '/' . $module)) {
                    continue;
                }
                $temp[$module] = [ 'name' => $module, 'loaded' => false, 'visible' => false ];
            }
        }
        $data = [];
        foreach ($this->db->all("SELECT * FROM modules ORDER BY pos", null, 'name') as $module => $rest) {
            if (!isset($temp[$module])) {
                continue;
            }
            $data[] = $rest;
            unset($temp[$module]);
        }
        foreach ($temp as $module => $rest) {
            if ($module === 'crud') {
                continue;
            }
            $rest['pos'] = count($data);
            $data[] = $rest;
        }
        return $data;
    }
    public function setModules(array $modules): void
    {
        $this->db->begin();
        $this->db->query("DELETE FROM modules");
        foreach ($modules as $pos => $module) {
            $this->db->query(
                "INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, icon, color, pos) VALUES (??)",
                [
                    $module['name'],
                    $module['loaded'],
                    $module['dashboard'],
                    $module['menu'],
                    $module['parent'],
                    $module['classname'],
                    $module['icon'],
                    $module['color'],
                    $pos
                ]
            );
        }
        $this->db->commit();
        $this->cache->set(
            'modules',
            $this->db->all("SELECT * FROM modules WHERE loaded = 1 ORDER BY pos", null, 'name')
        );
    }
}
