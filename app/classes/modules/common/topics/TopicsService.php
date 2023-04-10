<?php

declare(strict_types=1);

namespace modules\common\topics;

use modules\common\crud\CRUDException;
use modules\common\crud\CRUDServiceAdvanced;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\user\User;
use vakata\user\UserManagementInterface;

class TopicsService extends CRUDServiceAdvanced
{
    protected array $forums = [];

    public function __construct(DBInterface $db, User $user, UserManagementInterface $usrm)
    {
        parent::__construct($db, $user, 'forum_topics');
        $forums = [0];
        foreach ($this->db->all("SELECT forum FROM forums WHERE hidden = 0") as $forum) {
            if (!$usrm->permissionExists('forums/' . $forum) || $user->hasPermission('forums/' . $forum)) {
                $forums[] = $forum;
            }
        }
        $this->forums = $forums;
        $this->repository->filter('forum', $forums);
        if (!$this->isModerator()) {
            $this->repository->filter('hidden', 0);
        }
        $this->repository->with('forums');
    }
    public function getForums(bool $unlockedOnly = false): array
    {
        return $this->db->all(
            "SELECT forum, name FROM forums
             WHERE forum IN (??) AND hidden = 0" . ($unlockedOnly ? " AND locked = 0" : ""),
            [$this->forums],
            'forum',
            true
        );
    }
    public function listDefaults(): array
    {
        $params = parent::listDefaults();
        $params['o'] = 'updated';
        $params['d'] = '1';
        return $params;
    }
    public function insert(array $data = []): Entity
    {
        $data['created'] = date('Y-m-d H:i:s');
        $data['updated'] = date('Y-m-d H:i:s');
        $data['usr'] = $this->user->getID();
        $forums = $this->getForums(true);
        if (!isset($forums[$data['forum']])) {
            throw new \Exception('Not allowed', 403);
        }
        return parent::insert($data);
    }
    public function update(mixed $id, array $data = []): Entity
    {
        if (!$this->isModerator()) {
            throw new \Exception('Not allowed', 403);
        }
        $entity = $this->read($id);
        $data['created'] = $entity->created;
        $data['updated'] = $entity->updated;
        $data['usr'] = $entity->usr;
        return parent::update($id, $data);
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 403);
    }
    public function seen(int $id): void
    {
        $this->db->query(
            "UPDATE user_forums SET seen = ? WHERE usr = ? AND topic = ?",
            [ date('Y-m-d H:i:s'), $this->user->getID(), $id ]
        );
    }
    public function read(mixed $id): Entity
    {
        $entity = parent::read($id);
        $this->seen($entity->topic);
        $entity->replies = $this->db->table('forum_topics', true)->filter('topic', $entity->topic);
        if (!$this->isModerator()) {
            $entity->replies->filter('hidden', 0);
        }
        $entity->replies->sort('created');
        $entity->starred = $this->db->one(
            "SELECT 1 FROM user_forums WHERE usr = ? AND topic = ?",
            [$this->user->getID(), $entity->topic]
        );
        return $entity;
    }
    public function isModerator(): bool
    {
        return $this->user->hasPermission('forums/moderator');
    }
    public function postReply(array $data): void
    {
        try {
            $topic = $this->read($data['topic'] ?? 0);
        } catch (CRUDException $e) {
            throw new \Exception('Not allowed', 403);
        }
        if ($topic->locked || $topic->hidden) {
            throw new \Exception('Not allowed', 403);
        }
        $this->db->query(
            "INSERT INTO forum_posts (topic, created, usr, content, files) VALUES (??)",
            [
                $data['topic'],
                date('Y-m-d H:i:s'),
                $this->user->getID(),
                $data['content'] ?? '',
                $data['files'] ?? '',
            ]
        );
        $this->db->query("UPDATE forum_topics SET updated = ? WHERE topic = ?", [date('Y-m-d H:i:s'), $data['topic']]);
        $this->seen($topic->topic);
    }
    public function hideReply(int $id): int
    {
        if (!$this->isModerator()) {
            throw new \Exception('Not allowed', 403);
        }
        $reply = $this->db->one("SELECT * FROM forum_posts WHERE post = ?", $id);
        if (!$reply) {
            throw new \Exception('Not allowed', 403);
        }
        try {
            $this->read($reply['topic']);
        } catch (CRUDException $e) {
            throw new \Exception('Not allowed', 403);
        }
        $this->db->query("UPDATE forum_posts SET hidden = 1 WHERE post = ?", $id);
        return $reply['topic'];
    }
    public function showReply(int $id): int
    {
        if (!$this->isModerator()) {
            throw new \Exception('Not allowed', 403);
        }
        $reply = $this->db->one("SELECT * FROM forum_posts WHERE post = ?", $id);
        if (!$reply) {
            throw new \Exception('Not allowed', 403);
        }
        try {
            $this->read($reply['topic']);
        } catch (CRUDException $e) {
            throw new \Exception('Not allowed', 403);
        }
        $this->db->query("UPDATE forum_posts SET hidden = 0 WHERE post = ?", $id);
        return $reply['topic'];
    }
    public function addStar(int $id): void
    {
        $this->db->query(
            "INSERT INTO user_forums (usr, topic, created, seen) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE seen = ?",
            [
                $this->user->getID(),
                $id,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]
        );
    }
    public function removeStar(int $id): void
    {
        $this->db->query(
            "DELETE FROM user_forums WHERE usr = ? AND topic = ?",
            [
                $this->user->getID(),
                $id
            ]
        );
    }
    public function getUnread(int $limit = 5): array
    {
        return $this->db->table('forum_topics')
            ->filter('forum_topics.hidden', 0)
            ->filter('forums.hidden', 0)
            ->filter('user_forums.usr', $this->user->getID())
            ->sort('forum_topics.updated', true)
            ->paginate(1, $limit)
            ->select(['forum_topics.topic', 'forum_topics.name', 'forum_topics.updated', 'user_forums.seen']);
    }
}
