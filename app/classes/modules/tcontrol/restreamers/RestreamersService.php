<?php

declare(strict_types=1);

namespace modules\tcontrol\restreamers;

use helpers\AppStatic;
use modules\common\crud\CRUDException;
use modules\common\crud\CRUDService;
use vakata\database\schema\Entity;

class RestreamersService extends CRUDService
{
    public function insert(array $data = []) : Entity
    {
        try {
            $this->db->begin();
            if (!isset($data['ip']) || !strlen(trim($data['ip']))) {
                $data['ip'] = null;
            }
            $data['monitor'] = null;
            $entity = parent::insert($data);
            if (isset($data['_servers']) && is_array($data['_servers'])) {
                $servers = $this->getServers();
                foreach ($data['_servers'] as $server) {
                    if (isset($servers[$server])) {
                        $this->db->table('restreamer_servers')
                            ->insert([
                                'restreamer'    => $entity->restreamer,
                                'server'        => $server
                            ]);
                    }
                }
            }
            if (isset($data['_miks']) && is_array($data['_miks'])) {
                $miks = $this->getMiks();
                foreach ($data['_miks'] as $mik) {
                    if (isset($miks[$mik])) {
                        $this->db->table('restreamer_miks')
                            ->insert([
                                'restreamer'    => $entity->restreamer,
                                'mik'           => $mik
                            ]);
                    }
                }
            }
            $this->db->commit();
            AppStatic::log()->addNotice('Добавен рестриймър', ['restreamer' => $entity->restreamer, 'data' => $data ]);
            return $entity;
        } catch (\Throwable $e) {
            $this->db->rollback();

            throw new CRUDException('В момента не можем да обслужим заявката Ви');
        }
    }
    public function update(mixed $id, array $data = []) : Entity
    {
        try {
            $this->db->begin();
            if (!isset($data['ip']) || !strlen(trim($data['ip']))) {
                $data['ip'] = null;
            }
            if (isset($data['monitor'])) {
                unset($data['monitor']);
            }
            $entity = parent::update($id, $data);
            $this->db->table('restreamer_servers')
                ->filter('restreamer', $entity->restreamer)
                ->delete();
            $this->db->table('restreamer_miks')
                ->filter('restreamer', $entity->restreamer)
                ->delete();

            if (isset($data['_servers']) && is_array($data['_servers'])) {
                $servers = $this->getServers();
                foreach ($data['_servers'] as $server) {
                    if (isset($servers[$server])) {
                        $this->db->table('restreamer_servers')
                            ->insert([
                                'restreamer'    => $entity->restreamer,
                                'server'        => $server
                            ]);
                    }
                }
            }
            if (isset($data['_miks']) && is_array($data['_miks'])) {
                $miks = $this->getMiks();
                foreach ($data['_miks'] as $mik) {
                    if (isset($miks[$mik])) {
                        $this->db->table('restreamer_miks')
                            ->insert([
                                'restreamer'    => $entity->restreamer,
                                'mik'           => $mik
                            ]);
                    }
                }
            }
            $this->db->commit();
            AppStatic::log()->addNotice('Редакция на рестриймър', ['restreamer' => $entity->restreamer, 'data' => $data ]);
            return $entity;
        } catch (\Throwable $e) {
            $this->db->rollback();

            throw new CRUDException('В момента не можем да обслужим заявката Ви');
        }
    }
    public function delete(mixed $id) : void
    {
        try {
            $this->db->begin();
            $entity = parent::read($id);
            $this->db->table('restreamer_servers')
                ->filter('restreamer', $entity->restreamer)
                ->delete();
            $this->db->table('restreamer_miks')
                ->filter('restreamer', $entity->restreamer)
                ->delete();
            parent::delete($id);
            $this->db->commit();
            AppStatic::log()->addNotice('Премахване на рестриймър', ['restreamer' => $id ]);
        } catch (\Throwable $e) {
            $this->db->rollback();

            throw new CRUDException('В момента не можем да обслужим заявката Ви');
        }
    }
    public function listDefaults(): array
    {
        $ret = parent::listDefaults();
        $ret['l'] = 100;
        return $ret;
    }
    public function getServers() : array
    {
        return $this->db->all(
            "SELECT server, CONCAT(host, '/', inner_host) FROM servers ORDER BY server ASC",
            null,
            'server',
            true
        );
    }
    public function getMiks() : array
    {
        return $this->db->all("SELECT mik, name FROM miks ORDER BY mik ASC", null, 'mik', true);
    }
    public function read(mixed $id) : Entity
    {
        $entity = parent::read($id);
        $entity->_servers = $this->db->all(
            'SELECT server FROM restreamer_servers WHERE restreamer = ?',
            [ $entity->restreamer ]
        );
        $entity->_miks = $this->db->all(
            'SELECT mik FROM restreamer_miks WHERE restreamer = ?',
            [ $entity->restreamer ]
        );

        return $entity;
    }
}
