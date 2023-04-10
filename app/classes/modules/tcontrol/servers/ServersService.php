<?php

declare(strict_types=1);

namespace modules\tcontrol\servers;

use helpers\AppStatic;
use modules\common\crud\CRUDException;
use modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\random\Generator;

class ServersService extends CRUDService
{
    public function insert(array $data = []) : Entity
    {
        $data['key_setup'] = Generator::string(64);
        $data['key_sik'] = Generator::string(64);
        $data['key_real'] = Generator::string(64);
        if (!isset($data['ip']) || !strlen(trim($data['ip']))) {
            $data['ip'] = null;
        }
        $data['monitor'] = null;
        $ret = parent::insert($data);
        AppStatic::log()->addNotice('Добавен сървър', ['server' => $ret->server, 'data' => $data ]);
        return $ret;
    }
    public function update(mixed $id, array $data = []) : Entity
    {
        $entity = $this->read($id);
        $data['key_setup'] = $entity->key_setup;
        $data['key_sik'] = $entity->key_sik;
        $data['key_real'] = $entity->key_real;
        if (!isset($data['ip']) || !strlen(trim($data['ip']))) {
            $data['ip'] = null;
        }
        if (isset($data['monitor'])) {
            unset($data['monitor']);
        }
        $ret = parent::update($id, $data);
        AppStatic::log()->addNotice('Редакция на сървър', ['server' => $ret->server, 'data' => $data ]);
        return $ret;
    }
    public function delete(mixed $id) : never
    {
        throw new CRUDException('Not allowed');
    }
    public function listDefaults(): array
    {
        $ret = parent::listDefaults();
        $ret['l'] = 100;
        return $ret;
    }
}
