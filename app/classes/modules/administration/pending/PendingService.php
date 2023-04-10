<?php

declare(strict_types=1);

namespace modules\administration\pending;

use modules\common\crud\CRUDService;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\user\User;
use vakata\user\UserManagementInterface;

class PendingService extends CRUDService
{
    protected UserManagementInterface $usrm;
    protected string $defaultGroup;

    public function __construct(DBInterface $db, User $user, UserManagementInterface $usrm, Config $config)
    {
        parent::__construct($db, $user, 'user_pending');
        $this->repository->where(
            'NOT EXISTS (SELECT 1 FROM user_providers WHERE provider = user_pending.provider AND id = user_pending.id)'
        );
        $this->usrm = $usrm;
        $this->defaultGroup = $config->get('GROUP_USERS');
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    public function listDefaults(): array
    {
        $d = parent::listDefaults();
        $d['o'] = 'created';
        $d['d'] = 1;
        return $d;
    }
    public function user(Entity $pending): int
    {
        if (
            $this->db->one(
                "SELECT 1 FROM user_providers WHERE provider = ? AND id = ?",
                [ $pending->provider, $pending->id ]
            )
        ) {
            throw new \Exception('User already exists');
        }
        $user = new \vakata\user\User(
            '',
            [
                'name' => $pending->name,
                'mail' => $pending->mail
            ]
        );
        $user->addGroup($this->usrm->getGroup($this->defaultGroup));
        $user->addProvider(new \vakata\user\Provider($pending->provider, $pending->id));
        $this->usrm->saveUser($user);
        return (int)$user->getID();
    }
    public function auth(int $userID, Entity $pending): int
    {
        if (
            $this->db->one(
                "SELECT 1 FROM user_providers WHERE provider = ? AND id = ?",
                [ $pending->provider, $pending->id ]
            )
        ) {
            throw new \Exception('User already exists');
        }
        $user = $this->usrm->getUser((string)$userID);
        $user->addProvider(new \vakata\user\Provider($pending->provider, $pending->id));
        $this->usrm->saveUser($user);
        return (int)$user->getID();
    }
}
