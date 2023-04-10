<?php

declare(strict_types=1);

namespace modules\administration\authentication;

use modules\common\crud\CRUDException;
use modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;

class AuthenticationService extends CRUDService
{
    public function insert(array $data = []): Entity
    {
        throw new CRUDException('Not allowed');
    }
    public function delete(mixed $id): void
    {
        throw new CRUDException('Not allowed');
    }
    public function read(mixed $id): Entity
    {
        $res = parent::read($id);
        $res->settings = $res->settings && json_decode($res->settings, true) ?
            json_encode(
                json_decode($res->settings, true),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ) :
            '';
        $res->conditions = $res->conditions && json_decode($res->conditions, true) ?
            json_encode(
                json_decode($res->conditions, true),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ) :
            '';
        $res->position = (int)$res->position;
        return $res;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        if (trim($data['settings'])) {
            if (!json_decode($data['settings'], true)) {
                throw new CRUDException('Invalid settings JSON');
            }
        } else {
            $data['settings'] = '{}';
        }
        if (trim($data['conditions'])) {
            if (!json_decode($data['conditions'], true)) {
                throw new CRUDException('Invalid conditions JSON');
            }
        } else {
            $data['conditions'] = null;
        }
        return parent::update($id, $data);
    }
    public function list(array $options): TableQueryMapped
    {
        $options['o'] = 'position';
        $options['d'] = '0';
        $options['p'] = '1';
        $options['l'] = 'all';
        return parent::list($options);
    }
}
