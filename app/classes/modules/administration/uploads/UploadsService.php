<?php

declare(strict_types=1);

namespace modules\administration\uploads;

use modules\common\crud\CRUDException;
use modules\common\crud\CRUDService;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\files\FileStorageInterface;
use vakata\user\User;

class UploadsService extends CRUDService
{
    protected FileStorageInterface $files;
    protected string $tmp;

    public function __construct(DBInterface $db, User $user, FileStorageInterface $files, Config $config)
    {
        parent::__construct($db, $user, 'uploads');
        $this->files = $files;
        $this->tmp = $config->get('STORAGE_TMP');
    }
    public function insert(array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function read(mixed $id): Entity
    {
        $entity = $this->repository->columns($this->listColumns())->find($id);
        if (!$entity) {
            throw new CRUDException('Record not found');
        }
        //var_dump($entity->data); die();
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        if (isset($data['temp']) && $data['temp'] && is_file($this->tmp . '/' . $data['temp'])) {
            $file = $this->files->get((string)$id['id']);
            $data['hash'] = md5_file($this->tmp . '/' . $data['temp']);
            $data['bytesize'] = filesize($this->tmp . '/' . $data['temp']);
            $this->files->set($file, fopen($this->tmp . '/' . $data['temp'], 'r'));
            unlink($this->tmp . '/' . $data['temp']);
            if (is_file($this->tmp . '/' . $data['temp'] . '.settings')) {
                unlink($this->tmp . '/' . $data['temp'] . '.settings');
            }
        }
        return parent::update($id, $data);
    }
    public function listColumns(): array
    {
        return array_filter(
            parent::listColumns(),
            function ($v) {
                return $v !== 'data';
            }
        );
    }
}
