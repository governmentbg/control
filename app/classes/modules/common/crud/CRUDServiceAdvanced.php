<?php

declare(strict_types=1);

namespace modules\common\crud;

use vakata\user\User;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;

abstract class CRUDServiceAdvanced extends CRUDService implements CRUDServiceVersionedInterface
{
    public const CREATE = 0;
    public const UPDATE = 1;
    public const DELETE = 2;

    protected ?string $table = null; // 'versions';

    public function __construct(DBInterface $db, User $user, string $table = null)
    {
        parent::__construct($db, $user, $table);
        $this->filterByUserGroup();
        $this->filterByUserOrganization();
        $this->filterByUserLanguage();
        $this->filterByUserSite();
    }

    protected function filterByUserGroup(): void
    {
        if (in_array('grp', $this->getFields(true))) {
            $this->repository->filter('grp', array_merge(['' => 0], array_keys($this->user->getGroups())));
        }
    }
    protected function filterByUserOrganization(): void
    {
        if (in_array('org', $this->getFields(true))) {
            $this->repository->where(
                'EXISTS (SELECT 1 FROM user_organizations WHERE usr = ? AND org = ' . $this->module . '.org) ',
                [ $this->user->getID() ]
            );
        }
    }
    protected function filterByUserLanguage(): void
    {
        if (in_array('lang', $this->getFields(true))) {
            $this->repository->filter('lang', array_merge(['' => 0], array_keys($this->user->languages)));
        }
    }
    protected function filterByUserSite(): void
    {
        if (in_array('site', $this->getFields(true))) {
            $this->repository->filter('site', $this->user->site);
        }
    }

    public function canUpdate(Entity $entity): bool
    {
        return (
            !in_array('_status', $this->getFields(true)) ||
            $entity->_status !== 'published' ||
            $this->user->hasPermission($this->module . '/publish') ||
            $this->user->hasPermission($this->module . '/publish_own')
        );
    }
    public function canPublish(?Entity $entity = null): bool
    {
        return (
            !in_array('_status', $this->getFields(true)) ||
            $this->user->hasPermission($this->module . '/publish_own') ||
            (
                (
                    !in_array('usr', $this->getFields(true)) ||
                    ($entity && (string)$entity->usr !== $this->user->getID())
                ) &&
                $this->user->hasPermission($this->module . '/publish')
            )
        );
    }

    public function insert(array $data = []): Entity
    {
        $temp = $this->repository->create();
        if (!($temp instanceof Entity)) {
            throw new CRUDException("Can not create");
        }
        if (
            in_array('grp', $this->getFields(true)) &&
            isset($data['grp']) && !$this->user->inGroup($data['grp'])
        ) {
            throw new CRUDException('Invalid group');
        }
        if (
            in_array('_status', $this->getFields(true)) &&
            $data['_status'] === 'published' &&
            !$this->canPublish($temp)
        ) {
            throw new CRUDException('Can not publish');
        }
        $entity = parent::insert($data);
        $this->version($entity, static::CREATE);
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        $temp = $this->read($id);
        if (!$this->canUpdate($temp)) {
            throw new CRUDException('Can not update');
        }
        if (
            in_array('grp', $this->getFields(true)) &&
            isset($data['grp']) && !$this->user->inGroup($data['grp'])
        ) {
            throw new CRUDException('Invalid group');
        }
        if (
            in_array('_status', $this->getFields(true)) &&
            $temp->status !== 'published' &&
            $data['_status'] === 'published' &&
            !$this->canPublish($temp)
        ) {
            throw new CRUDException('Can not publish');
        }
        $entity = parent::update($id, $data);
        $this->version($entity, static::UPDATE);
        return $entity;
    }
    public function patch(mixed $id, array $data = []): Entity
    {
        $entity = $this->read($id);
        foreach ($this->getFields(true) as $field) {
            if (!isset($data[$field])) {
                $data[$field] = $entity->{$field};
            }
        }
        if (!$this->canUpdate($entity)) {
            throw new CRUDException('Can not update');
        }
        if (
            in_array('grp', $this->getFields(true)) &&
            isset($data['grp']) && !$this->user->inGroup($data['grp'])
        ) {
            throw new CRUDException('Invalid group');
        }
        if (
            in_array('_status', $this->getFields(true)) &&
            $entity->status !== 'published' &&
            isset($data['_status']) &&
            $data['_status'] === 'published' &&
            !$this->canPublish($entity)
        ) {
            throw new CRUDException('Can not publish');
        }
        $entity = parent::patch($id, $data);
        $this->version($entity, static::UPDATE);
        return $entity;
    }
    public function delete(mixed $id): void
    {
        $entity = $this->read($id);
        parent::delete($id);
        $this->version($entity, static::DELETE);
    }
    public function version(Entity $entity, int $reason = 0, bool $modifyLast = false): void
    {
        if (!$this->table) {
            return;
        }
        $reasons = [ 'created', 'updated', 'deleted' ];
        $tbl = $this->repository->getDefinition()->getName();
        $id = json_encode($this->getID($entity));
        $json = json_encode($entity->toArray());
        if ($modifyLast) {
            $created = $this->db->one(
                "SELECT MAX(created) FROM {$this->table} WHERE tbl = ? AND id = ?",
                [ $tbl, $id ]
            );
            $this->db->table($this->table)
                ->filter('tbl', $tbl)
                ->filter('id', $id)
                ->filter('created', $created)
                ->update([
                    'entity'   => $json
                ]);
        } else {
            $this->db->table($this->table)
                ->insert([
                    'tbl'      => $tbl,
                    'id'       => $id,
                    'created'  => date('Y-m-d H:i:s'),
                    'entity'   => $json,
                    'reason'   => $reasons[$reason],
                    'usr'      => $this->user->getID(),
                    'usr_name' => $this->user->name
                ]);
        }
    }
    public function versions(Entity $entity): array
    {
        if (!$this->table) {
            return [];
        }
        return $this->db->table($this->table)
            ->filter('tbl', $this->repository->getDefinition()->getName())
            ->filter('id', json_encode($this->getID($entity)))
            ->sort('created', true)
            ->select();
    }

    protected function populate(Entity $entity, array $data = [], bool $isCreate = false): Entity
    {
        $columns = $this->getFields(true);
        if (in_array('usr', $columns)) {
            if ($isCreate) {
                $data['usr'] = $this->user->getID();
            } else {
                $data['usr'] = $entity->usr;
            }
        }
        if (in_array('grp', $columns)) {
            if (!isset($data['grp'])) {
                $data['grp'] = $this->user->getPrimaryGroup()?->getID();
            }
        }
        if (in_array('org', $columns)) {
            $org = array_keys($this->user->organization);
            if (!count($org)) {
                $data['org'] = null;
            } elseif (count($org) === 1) {
                $data['org'] = $org[0];
            } else {
                if (!isset($data['org']) || !in_array($data['org'], $org)) {
                    throw new CRUDException('Invalid organization level');
                }
            }
        }
        if (in_array('site', $columns)) {
            if ($isCreate) {
                $data['site'] = $this->user->site;
            } else {
                $data['site'] = $entity->site;
            }
        }
        return parent::populate($entity, $data, $isCreate);
    }
}
