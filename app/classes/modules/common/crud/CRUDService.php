<?php

declare(strict_types=1);

namespace modules\common\crud;

use vakata\validation\Validator;
use vakata\collection\Collection;
use vakata\database\DBInterface;
use vakata\database\DBException;
use vakata\database\schema\Entity;
use vakata\database\schema\TableColumn;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;

abstract class CRUDService implements CRUDServiceInterface
{
    protected DBInterface $db;
    protected User $user;
    protected TableQueryMapped $repository;
    protected string $module;

    public function __construct(DBInterface $db, User $user, string $table = null)
    {
        $this->db         = $db;
        $this->user       = $user;
        $this->module     = $table ?? strtolower(
            preg_replace('(Service$)', '', basename(str_replace('\\', '/', get_class($this)))) ?? ''
        );
        $this->repository = $this->db->tableMapped($this->module);
    }

    protected function create(): object
    {
        return new \stdClass();
    }

    public function getValidator(bool $isCreate = false): Validator
    {
        $validator = new Validator();
        foreach ($this->getFields() as $name => $column) {
            if (!$column['nullable'] && $column['default'] === null) {
                $validator->required($name);
            } else {
                $validator->optional($name);
            }
            switch ($column['type']) {
                case 'int':
                    $validator->int('integer');
                    break;
                case 'float':
                    $validator->float('float');
                    break;
                case 'enum':
                    $validator->inArray($column['values']);
                    break;
                case 'date':
                case 'datetime':
                    $validator->date(null, 'date');
                    break;
                case 'text':
                    if (isset($column['length']) && (int)$column['length']) {
                        $validator->maxLength((int)$column['length']);
                    }
                    break;
            }
        }
        // override parent function so that there is no validation on primary key columns
        // primary key validation can be added manually in classes that extend this one
        foreach ($this->repository->getDefinition()->getPrimaryKey() as $name) {
            $validator->remove($name);
        }
        return $validator;
    }
    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function validate(array $data, Entity $entity, bool $isCreate = false): void
    {
        $errors = $this->getValidator($isCreate)->run($data);
        if (count($errors)) {
            foreach ($errors as $k => $v) {
                if (!$v['message']) {
                    $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                }
            }
            throw (new CRUDException("validation", 400))->setErrors($errors);
        }
    }
    protected function populate(Entity $entity, array $data = [], bool $isCreate = false): Entity
    {
        $columns = $this->getFields(true);
        if (in_array('_created', $columns)) {
            if ($isCreate) {
                $data['_created'] = date('Y-m-d H:i:s');
            } else {
                $data['_created'] = $entity->_created;
            }
        }
        if (in_array('_updated', $columns)) {
            $data['_updated'] = date('Y-m-d H:i:s');
        }
        $this->validate($data, $entity, $isCreate);
        return $entity->fromArray($data);
    }

    public function insert(array $data = []): Entity
    {
        $entity = $this->repository->create();
        if (!($entity instanceof Entity)) {
            throw new CRUDException("Can not create");
        }
        return $this->populate($entity, $data, true)->save();
    }
    public function read(mixed $id): Entity
    {
        $entity = $this->repository->find($id);
        if (!$entity) {
            throw new CRUDException('Record not found');
        }
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        $entity = $this->read($id);
        return $this->populate($entity, $data)->save();
    }
    public function patch(mixed $id, array $data = []): Entity
    {
        $entity = $this->read($id);
        foreach ($this->getFields(true) as $field) {
            if (!isset($data[$field])) {
                $data[$field] = $entity->{$field};
            }
        }
        return $this->update($id, $data);
    }
    public function delete(mixed $id): void
    {
        $this->read($id)->delete();
    }

    public function list(array $options): TableQueryMapped
    {
        if (!isset($options['p']) || (int)$options['p'] < 1) {
            $options['p'] = 1;
        }
        if (!isset($options['l'])) {
            $options['l'] = 25;
        }
        if ($options['l'] !== 'all') {
            $options['l'] = (int)$options['l'];
            if (!$options['l']) {
                $options['l'] = 25;
            }
        }
        if ($options['l'] !== 'all') {
            $this->repository->limit($options['l'], ((int)$options['p'] - 1) * $options['l']);
        }
        foreach ($options as $k => $v) {
            switch ($k) {
                case 'd':
                case 'p':
                case 'l':
                    break;
                case 'o':
                    try {
                        $this->repository->sort($v, isset($options['d']) && (int)$options['d'] ? true : false);
                    } catch (DBException $ignore) {
                    }
                    break;
                case 'q':
                    $this->search($v);
                    break;
                default:
                    try {
                        $this->repository->filter($k, $v);
                    } catch (DBException $ignore) {
                    }
                    break;
            }
        }
        return $this->repository;//->columns($this->listColumns());
    }
    public function listDefaults(): array
    {
        return [ 'l' => 25, 'p' => 1 ];
    }
    public function listColumns(): array
    {
        return $this->getFields(true);
    }
    public function search(string $q): void
    {
        try {
            $sql = [];
            $par = [];
            $table = $this->repository->getDefinition()->getName();
            foreach ($this->getFields() as $name => $column) {
                $sql[] = $table . '.' . $name . '::varchar ILIKE ?';
                $par[] = '%' . str_replace(['%', '_'], ['\\%','\\_'], $q) . '%';
            }
            if (count($sql)) {
                $this->repository->where("(" . implode(" OR ", $sql) . ")", $par);
            }
        } catch (DBException $ignore) {
        }
    }

    public function getFields(bool $namesOnly = false): array
    {
        $cols = Collection::from($this->repository->getDefinition()->getFullColumns())
            ->map(function (TableColumn $v) {
                $type = $v->getType();
                // fix for text columns not having a default
                $default = $v->getDefault();
                if (
                    $default === null &&
                    !$v->isNullable() &&
                    in_array($type, ['tinytext', 'text', 'mediumtext', 'longtext'])
                ) {
                    $default = '';
                }
                return [
                    'name'     => $v->getName(),
                    'type'     => $v->getBasicType(),
                    'null'     => $v->isNullable(),
                    'values'   => $v->getValues(),
                    'nullable' => $v->isNullable(),
                    'default'  => $default,
                    'length'   => $v->hasLength() ? $v->getLength() : null
                ];
            });
        return $namesOnly ? $cols->keys()->toArray() : $cols->toArray();
    }
    public function getID(Entity $entity): array
    {
        return $entity->id();
    }
    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function version(Entity $entity, int $reason = 0, bool $modifyLast = false): void
    {
    }
    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function versions(Entity $entity): array
    {
        return [];
    }
}
