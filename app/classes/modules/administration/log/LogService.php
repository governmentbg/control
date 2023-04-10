<?php

declare(strict_types=1);

namespace modules\administration\log;

use modules\common\crud\CRUDService;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\user\User;

class LogService extends CRUDService
{
    protected string $storage;

    public function __construct(DBInterface $db, User $user, Config $config)
    {
        parent::__construct($db, $user);
        $this->storage = $config->get('STORAGE_REQ');
    }
    public function listDefaults(): array
    {
        $params = parent::listDefaults();
        $params['o'] = 'created';
        $params['d'] = '1';
        return $params;
    }
    public function insert(array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function update(mixed $id, array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    public function users(): array
    {
        return $this->db->all(
            "SELECT usr, avatar_data, name FROM users ORDER BY name",
            null,
            "usr",
            true
        );
    }
    public function read(mixed $id): Entity
    {
        $entity = parent::read($id);
        $time = strtotime($entity->created);
        if (!$time) {
            $time = time();
        }
        if (strlen($this->storage) && $this->storage !== 'DATABASE') {
            $path = rtrim($this->storage, '/') . '/' .
                date('Y', $time) . '/' . date('m', $time) .  '/' . date('d', $time);
            $uuid = trim(explode("\n", (explode('X-Request-UUID: ', $entity->response, 2)[1] ?? ''), 2)[0] ?? '');
            if (strpos($entity->request, '*** SKIPPED ***') && $uuid && is_file($path . '/' . $uuid . '.req')) {
                $entity->request = str_replace(
                    '*** SKIPPED ***',
                    file_get_contents($path . '/' . $uuid . '.req') ?: throw new \RuntimeException(),
                    $entity->request
                );
            }
            if (strpos($entity->response, '*** SKIPPED ***') && $uuid && is_file($path . '/' . $uuid . '.res')) {
                $entity->response = str_replace(
                    '*** SKIPPED ***',
                    file_get_contents($path . '/' . $uuid . '.res') ?: throw new \RuntimeException(),
                    $entity->response
                );
            }
        }
        return $entity;
    }
    public function getFields(bool $namesOnly = false): array
    {
        $cols = parent::getFields();
        if (!$this->user->hasPermission('log/viewraw')) {
            unset($cols['request']);
            unset($cols['response']);
        }
        return $namesOnly ? array_keys($cols) : $cols;
    }
}
