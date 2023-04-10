<?php

declare(strict_types=1);

namespace modules\common\notifications;

use modules\common\crud\CRUDServiceAdvanced;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\user\User;

class NotificationsService extends CRUDServiceAdvanced
{
    public function __construct(DBInterface $db, User $user)
    {
        parent::__construct($db, $user);
        $this->repository
            ->with('notification_recipients')
            ->with('users')
            ->filter('notification_recipients.recipient', (int)$this->user->getID());
    }

    public function push(mixed $user, string $title, string $body = '', string $link = '', bool $reply = false): Entity
    {
        $entity = parent::insert([
            'thread' => null,
            'sender' => null,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'files' => '',
            'sent' => date('Y-m-d H:i:s'),
            'reply' => $reply ? 1 : 0
        ]);
        if (!is_array($user)) {
            $user = [ $user ];
        }
        foreach ($user as $u) {
            if ($u instanceof User) {
                $u = $u->getID();
            }
            if ((int)$u) {
                $this->db->table('notification_recipients')->insert([
                    'notification' => $entity->notification,
                    'recipient' => $u
                ]);
            }
        }
        return $entity;
    }

    public function getAvailableRecipients(): array
    {
        if ($this->user->hasPermission('users/master')) {
            return $this->db->all(
                "SELECT usr, name FROM users ORDER BY name",
                null,
                'usr',
                true
            );
        }
        return $this->user->organization && count($this->user->organization) ?
            $this->db->all(
                "SELECT u.usr, u.name
                FROM users u, user_organizations uo
                WHERE u.usr = uo.usr AND uo.org IN (??)
                ORDER BY u.name",
                [array_keys($this->user->organization)],
                'usr',
                true
            ) :
            [];
    }

    public function listDefaults(): array
    {
        $params = parent::listDefaults();
        $params['o'] = 'sent';
        $params['d'] = '1';
        return $params;
    }
    public function insert(array $data = []): Entity
    {
        $data['link'] = '';
        $data['sent'] = date('Y-m-d H:i:s');
        $data['sender'] = (int)$this->user->getID();
        $recpt = $this->getAvailableRecipients();
        // allow posting to thread only if in conversation
        if (
            !(int)$data['thread'] ||
            !$this->db->one(
                "SELECT 1 FROM notifications WHERE notification = ? AND reply = 1",
                $data['thread']
            ) ||
            !$this->db->one(
                "SELECT 1 FROM notification_recipients
                    WHERE notification = ? AND recipient = ?",
                [ (int)$data['thread'], (int)$this->user->getID() ]
            )
        ) {
            $data['thread'] = null;
        } else {
            $thread = $this->db->one("SELECT * FROM notifications WHERE notification = ?", $data['thread']);
            $data['reply'] = $thread['reply'];
            $data['title'] = 'RE: ' . $thread['title'];
            $data['recipients'] = $this->db->all(
                "SELECT recipient FROM notification_recipients WHERE notification = ?",
                $data['thread']
            );
        }
        $entity = parent::insert($data);
        foreach (($data['recipients'] ?? []) as $recipient) {
            if (
                (int)$recipient !== (int)$this->user->getID() &&
                ($data['thread'] !== null || isset($recpt[(int)$recipient]))
            ) {
                $this->db->table('notification_recipients')->insert([
                    'notification' => (int)$entity->notification,
                    'recipient' => (int)$recipient
                ]);
            }
        }
        $this->db->table('notification_recipients')->insert([
            'notification' => (int)$entity->notification,
            'recipient' => (int)$this->user->getID(),
            'opened' => date('Y-m-d H:i:s')
        ]);
        return $entity;
    }
    protected function notification(int $id): ?object
    {
        $temp = $this->db->table('notifications')
                    ->with('notification_recipients')
                    ->with('users')
                    ->filter('notification_recipients.recipient', (int)$this->user->getID())
                    ->filter('notification', $id)[0] ?? null;
        if (!$temp) {
            return null;
        }
        $temp = (object)$temp;
        $temp->users = $temp->users ? (object)$temp->users : null;
        return $temp;
    }
    public function read(mixed $id): Entity
    {
        $entity = parent::read($id);
        if (!$entity->notification_recipients[0]->opened) {
            $this->db->table('notification_recipients')
                ->filter('notification', (int)$entity->notification)
                ->filter('recipient', (int)$this->user->getID())
                ->update([ 'opened' => date('Y-m-d H:i:s') ]);
        }
        $entity->parents = [];
        if ($entity->thread) {
            $entity->parents[] = $this->notification((int)$entity->thread);
            foreach (
                $this->db->all(
                    'SELECT notification FROM notifications WHERE thread = ? ORDER BY sent',
                    [ $entity->thread ]
                ) as $id
            ) {
                $entity->parents[] = $this->notification((int)$id);
            }
        } else {
            $entity->parents[] = $this->notification((int)$entity->notification);
            foreach (
                $this->db->all(
                    'SELECT notification FROM notifications WHERE thread = ? ORDER BY sent',
                    [ $entity->notification ]
                ) as $id
            ) {
                $entity->parents[] = $this->notification((int)$id);
            }
        }
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        throw new \Exception('Not allowed', 400);
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    public function getUser(): int
    {
        return (int)$this->user->getID();
    }
    public function getNotifications(int $limit = 5): array
    {
        $unread = $this->db->table('notifications')
            ->filter('notification_recipients.recipient', (int)$this->user->getID())
            ->filter('notification_recipients.opened', null)
            ->sort('sent', true)
            ->paginate(1, $limit)
            ->select(['notification', 'title', 'link', 'sent', 'unread' => 1]);
        $read = $this->db->table('notifications')
            ->filter('notification_recipients.recipient', (int)$this->user->getID())
            ->filter('notification_recipients.opened', null, true)
            ->sort('sent', true)
            ->paginate(1, $limit)
            ->select(['notification', 'title', 'link', 'sent', 'unread' => 0]);
        return array_slice(array_merge($unread, $read), 0, $limit);
    }
}
