<?php

declare(strict_types=1);

namespace modules\tcontrol\apilog;

use modules\common\crud\CRUDException;
use modules\common\crud\CRUDService;
use vakata\database\DBException;
use vakata\database\DBInterface;
use vakata\user\User;

class ApilogService extends CRUDService
{
    public function __construct(DBInterface $db, User $user)
    {
        parent::__construct($db, $user, 'api_log');
    }
    public function insert(array $data = []): never
    {
        throw new CRUDException('Not allowed');
    }
    public function update(mixed $id, array $data = []): never
    {
        throw new CRUDException('Not allowed');
    }
    public function delete(mixed $id): never
    {
        throw new CRUDException('Not allowed');
    }
    public function getSiks() : array
    {
        return $this->db->all('SELECT sik, num FROM siks WHERE election = ?', [ $this->user->site ], 'sik', true);
    }
    public function getModes() : array
    {
        return $this->db->all('SELECT mode, name FROM modes', null, 'mode', true);
    }
    public function listDefaults() : array
    {
        return array_merge(parent::listDefaults(), [ 'o' => 'created', 'd' => 1 ]);
    }
    public function search(string $q) : void
    {
        try {
            $sql = [];
            $par = [];
            $table = $this->repository->getDefinition()->getName();
            $sql[] = $table . '.udi::varchar ILIKE ?';
            $par[] = '%' . str_replace(['%', '_'], ['\\%','\\_'], $q) . '%';

            $this->repository->where("(" . implode(" OR ", $sql) . ")", $par);
        } catch (DBException $ignore) {
        }
    }
}
