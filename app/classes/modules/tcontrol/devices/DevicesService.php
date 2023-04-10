<?php

declare(strict_types=1);

namespace modules\tcontrol\devices;

use helpers\AppStatic;
use modules\common\crud\CRUDException;
use modules\common\crud\CRUDService;
use vakata\database\DB;
use vakata\database\schema\Entity;
use vakata\random\Generator;
use vakata\validation\Validator;

class DevicesService extends CRUDService
{
    public function getValidator(bool $isCreate = false) : Validator
    {
        $validator = new Validator();
        $validator
            ->required('device')
            ->int();

        return $validator;
    }
    public function insert(array $data = []) : Entity
    {
        if (!isset($data['device']) || !(int) $data['device']) {
            throw new CRUDException('Невалиден номер на партида');
        }

        $data['install_key'] = Generator::string(14);
        $data['registered'] = null;
        $max = (int) $this->db->one(
            'SELECT MAX(udi) FROM devices WHERE floor(udi / 100000) = ?',
            [ $data['device'] ]
        );
        $data['udi'] = ($max === 0 ? (int) $data['device'] * 100000 : $max) + 1;

        $ret = parent::insert($data);
        AppStatic::log()->addNotice('Добавено устройство', ['udi' => $ret->udi, 'data' => $data]);
        return $ret;
    }
    public function update(mixed $id, array $data = []) : never
    {
        throw new CRUDException('Not allowed');
    }
    public function getPDFData() : array
    {
        return $this->db->all('SELECT udi, install_key FROM devices ORDER BY udi ASC');
    }
    public function getDevicePoints(Entity $entity) : array
    {
        return array_map(
            function (array $item) {
                $item['ts'] = date('d.m.Y H:i:s', (int) ceil((int) $item['ts'] / 1000));

                return $item;
            },
            $this->db->all(
                'SELECT
                    plugin_devicelocations_history.lat,
                    plugin_devicelocations_history.lon as lng,
                    plugin_devicelocations_history.ts
                FROM
                    hmdm_public.plugin_devicelocations_history
                JOIN
                    hmdm_public.devices ON plugin_devicelocations_history.deviceid = devices.id AND devices.number = ?',
                [ $entity->udi ]
            )
        );
    }
}
