<?php

declare(strict_types=1);

namespace modules\administration\maildb;

use modules\common\crud\CRUDService;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\user\User;

class MailDBService extends CRUDService
{
    public function __construct(DBInterface $db, User $user)
    {
        parent::__construct($db, $user, 'mails');
    }
    public function insert(array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
}
