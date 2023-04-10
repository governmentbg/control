<?php

declare(strict_types=1);

namespace modules\tcontrol\modes;

use helpers\AppStatic;
use modules\common\crud\CRUDException;
use modules\common\crud\CRUDService;
use vakata\database\schema\Entity;

class ModesService extends CRUDService
{
    public function insert(array $data = []) : Entity
    {
        throw new CRUDException('Not allowed');
    }
    public function update(mixed $id, array $data = []) : Entity
    {
        $entity = parent::read($id);
        $data['name'] = $entity->name;

        $ret = parent::update($id, $data);
        AppStatic::log()->addNotice('Редакция на режим', ['mode' => $ret->mode, 'data' => $data ]);
        return $ret;
    }
    public function delete(mixed $id) : never
    {
        throw new CRUDException('Not allowed');
    }
}
