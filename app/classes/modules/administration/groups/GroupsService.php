<?php

declare(strict_types=1);

namespace modules\administration\groups;

use vakata\user\UserManagementInterface;
use modules\common\crud\CRUDException;
use modules\common\crud\CRUDServiceAdvanced;
use vakata\database\DBInterface;
use vakata\user\User;
use vakata\user\Group;
use vakata\validation\Validator;
use vakata\collection\Collection;
use vakata\database\schema\Entity;
use vakata\di\DIInterface;

class GroupsService extends CRUDServiceAdvanced
{
    protected UserManagementInterface $usrm;
    protected DIInterface $di;
    protected static array $classes = [];

    public static function module(string $class): void
    {
        self::$classes[] = $class;
    }

    public function __construct(DBInterface $db, User $user, UserManagementInterface $usrm, DIInterface $di)
    {
        parent::__construct($db, $user, 'grps');
        $this->module     = 'groups';
        $this->repository = $db->tableMapped('grps');
        $this->usrm       = $usrm;
        $this->di         = $di;
    }

    public function getStoredPermissions(): array
    {
        $stored = $this->usrm->permissions();
        $methods = [];
        $modules = Collection::from(self::$classes)
            ->sortBy(function (string $a, string $b) {
                return $a <=> $b;
            })
            ->toArray();
        foreach ($modules as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }
            $name = strtolower(str_replace('Controller', '', basename(str_replace('\\', '/', $class))));
            $methods[] = $name;
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() && $method->name === 'permissions') {
                    $methods = array_merge(
                        $methods,
                        $this->di->invoke($class, $method->name)
                    );
                    continue;
                }
            }
        }
        $rslt = [];
        foreach ($stored as $perm) {
            if (isset($methods[$perm])) {
                $rslt[$perm] = $methods[$perm];
            } elseif (in_array($perm, $methods)) {
                $rslt[] = $perm;
            }
        }
        return $rslt;
    }

    public function getValidator(bool $isCreate = false): Validator
    {
        $validator = parent::getValidator($isCreate);
        $validator->required('name', 'required');
        return $validator;
    }
    public function insert(array $data = []): Entity
    {
        if ($this->usrm->groupExists((string)$data['name'])) {
            throw new CRUDException('modules.groups.groupalreadyexists');
        }
        $group = new Group('', $data['name'] ?? '');
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            foreach ($data['permissions'] as $permission) {
                $group->addPermission($permission);
            }
        }
        if (isset($data['additional']) && is_array($data['additional'])) {
            foreach ($data['additional'] as $permission) {
                $group->addPermission($permission);
            }
        }
        $this->usrm->saveGroup($group);
        return $this->read($group->getID());
    }
    public function read(mixed $id): Entity
    {
        $entity = parent::read($id);
        $entity->permissions = $this->usrm->getGroup((string)$entity->grp)->getPermissions();
        $entity->additional = $this->usrm->getGroup((string)$entity->grp)->getPermissions();
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        $group = $this->usrm->getGroup((string)($id['grp'] ?? ''));
        $group->setName($data['name'] ?? '');
        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            $data['permissions'] = [];
        }
        if (!isset($data['additional']) || !is_array($data['additional'])) {
            $data['additional'] = [];
        }
        foreach ($group->getPermissions() as $permission) {
            if (!in_array($permission, $data['permissions']) && !in_array($permission, $data['additional'])) {
                $group->deletePermission($permission);
            }
        }
        foreach ($data['permissions'] as $permission) {
            if (!$group->hasPermission($permission)) {
                $group->addPermission($permission);
            }
        }
        foreach ($data['additional'] as $permission) {
            if (!$group->hasPermission($permission)) {
                $group->addPermission($permission);
            }
        }
        $this->usrm->saveGroup($group);
        return $this->read($group->getID());
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
}
