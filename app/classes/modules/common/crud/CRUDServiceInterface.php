<?php

declare(strict_types=1);

namespace modules\common\crud;

use vakata\validation\Validator;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;

interface CRUDServiceInterface
{
    public function getValidator(bool $isCreate = false): Validator;
    public function insert(array $data = []): Entity;
    public function read(mixed $id): Entity;
    public function update(mixed $id, array $data = []): Entity;
    public function patch(mixed $id, array $data = []): Entity;
    public function delete(mixed $id): void;
    public function list(array $options): TableQueryMapped;
    public function listDefaults(): array;
    public function listColumns(): array;
    public function search(string $q): void;
    public function getFields(bool $namesOnly = false): array;
    public function getID(Entity $entity): array;
}
