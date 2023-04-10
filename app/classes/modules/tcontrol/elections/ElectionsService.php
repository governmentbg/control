<?php

declare(strict_types=1);

namespace modules\tcontrol\elections;

use helpers\AppStatic;
use modules\common\crud\CRUDException;
use modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\random\Generator;

class ElectionsService extends CRUDService
{
    public function insert(array $data = []) : Entity
    {
        $data['keyenc'] = base64_encode(Generator::bytes(32));
        $ret = parent::insert($data);
        AppStatic::log()->addNotice('Добавен избор', ['election' => $ret->election, 'data' => $data]);
        return $ret;
    }
    public function update(mixed $id, array $data = []) : Entity
    {
        $entity = parent::read($id);
        $data['keyenc'] = $entity->keyenc;

        $ret = parent::update($id, $data);
        AppStatic::log()->addNotice('Редакция на избор', ['election' => $ret->election, 'data' => $data]);
        return $ret;
    }
    public function delete(mixed $id) : never
    {
        throw new CRUDException('Not allowed');
    }
}
