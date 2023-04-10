<?php

declare(strict_types=1);

namespace modules\common\crud;

use vakata\database\schema\Entity;

interface CRUDServiceVersionedInterface
{
    public function version(Entity $entity, int $reason = 0, bool $modifyLast = false): void;
    public function versions(Entity $entity): array;
}
