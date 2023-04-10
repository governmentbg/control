<?php

declare(strict_types=1);

namespace modules\administration\forums;

use modules\common\crud\CRUDService;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\user\User;
use vakata\user\UserManagementInterface;

class ForumsService extends CRUDService
{
    protected UserManagementInterface $usrm;

    public function __construct(DBInterface $db, User $user, UserManagementInterface $usrm)
    {
        parent::__construct($db, $user);
        $this->usrm = $usrm;
    }

    public function insert(array $data = []): Entity
    {
        $data['created'] = date('Y-m-d H:i:s');
        $data['usr'] = $this->user->getID();
        $res = parent::insert($data);
        $id = $res->forum;
        if (!isset($data['grps']) || !is_array($data['grps']) || !count($data['grps'])) {
            if ($this->usrm->permissionExists('forums/' . $id)) {
                $this->usrm->deletePermission('forums/' . $id);
            }
        } else {
            if (!$this->usrm->permissionExists('forums/' . $id)) {
                $this->usrm->addPermission('forums/' . $id);
            }
            foreach ($data['grps'] as $grp) {
                if ($this->usrm->groupExists((string)$grp)) {
                    $group = $this->usrm->getGroup((string)$grp);
                    if (!$group->hasPermission('forums/' . $id)) {
                        $group->addPermission('forums/' . $id);
                        $this->usrm->saveGroup($group);
                    }
                }
            }
            foreach ($this->usrm->groups() as $group) {
                if (!in_array($group->getID(), $data['grps'])) {
                    if ($group->hasPermission('forums/' . $id)) {
                        $group->deletePermission('forums/' . $id);
                        $this->usrm->saveGroup($group);
                    }
                }
            }
        }
        return $res;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        $entity = $this->read($id);
        $data['created'] = $entity->created;
        $data['usr'] = $entity->usr;
        $res = parent::update($id, $data);
        $id = $entity->forum;
        if (!isset($data['grps']) || !is_array($data['grps']) || !count($data['grps'])) {
            if ($this->usrm->permissionExists('forums/' . $id)) {
                $this->usrm->deletePermission('forums/' . $id);
            }
        } else {
            if (!$this->usrm->permissionExists('forums/' . $id)) {
                $this->usrm->addPermission('forums/' . $id);
            }
            foreach ($data['grps'] as $grp) {
                if ($this->usrm->groupExists((string)$grp)) {
                    $group = $this->usrm->getGroup((string)$grp);
                    if (!$group->hasPermission('forums/' . $id)) {
                        $group->addPermission('forums/' . $id);
                        $this->usrm->saveGroup($group);
                    }
                }
            }
            foreach ($this->usrm->groups() as $group) {
                if (!in_array($group->getID(), $data['grps'])) {
                    if ($group->hasPermission('forums/' . $id)) {
                        $group->deletePermission('forums/' . $id);
                        $this->usrm->saveGroup($group);
                    }
                }
            }
        }
        return $res;
    }
    public function delete(mixed $id): void
    {
        parent::delete($id);
        if ($this->usrm->permissionExists('forums/' . $id['forum'])) {
            $this->usrm->deletePermission('forums/' . $id['forum']);
        }
    }
    public function read(mixed $id): Entity
    {
        $entity = parent::read($id);
        $entity->grps = [];
        foreach ($this->usrm->groups() as $group) {
            if (
                $this->usrm->permissionExists('forums/' . $entity->forum) &&
                $group->hasPermission('forums/' . $entity->forum)
            ) {
                $entity->grps[] = $group->getID();
            }
        }
        return $entity;
    }

    public function getForums(): array
    {
        return $this->db->all("SELECT forum, name FROM forums ORDER BY name", null, 'forum', true);
    }
    public function getGroups(): array
    {
        $groups = [];
        foreach ($this->usrm->groups() as $group) {
            $groups[$group->getID()] = $group->getName();
        }
        return $groups;
    }
}
