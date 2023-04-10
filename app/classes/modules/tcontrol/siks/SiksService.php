<?php

declare(strict_types=1);

namespace modules\tcontrol\siks;

use helpers\AppStatic;
use modules\common\crud\CRUDException;
use modules\common\crud\CRUDService;
use vakata\database\DB;
use vakata\database\DBException;
use vakata\database\DBInterface;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\random\Generator;
use vakata\user\User;
use vakata\validation\Validator;

class SiksService extends CRUDService
{
    public function __construct(DBInterface $db, User $user)
    {
        parent::__construct($db, $user);
        $this->repository->filter('election', $this->user->site);
    }
    public function getMiks() : array
    {
        return $this->db->all('SELECT mik, name FROM miks ORDER BY mik ASC', null, 'mik', true);
    }
    public function insert(array $data = []) : Entity
    {
        if (
            $this->db->one(
                'SELECT 1 FROM siks WHERE election = ? AND num = ?',
                [ $this->user->site, ($data['num'] ?? '') ]
            )
        ) {
            throw new CRUDException('Секцията вече съществува');
        }
        $data['election'] = $this->user->site;
        $data['prod_key'] = Generator::string(14);
        $data['test_key'] = Generator::string(14);

        $ret = parent::insert($data);
        AppStatic::log()->addNotice('Добавенa секция', ['sik' => $ret->sik, 'data' => $data ]);
        return $ret;
    }
    public function update(mixed $id, array $data = []) : Entity
    {
        $entity = $this->read($id);
        if (
            $this->db->one(
                'SELECT 1 FROM siks WHERE election = ? AND num = ? AND sik <> ?',
                [ $this->user->site, ($data['num'] ?? ''), $entity->sik ]
            )
        ) {
            throw new CRUDException('Секцията вече съществува');
        }
        $data['election'] = $entity->election;
        $data['prod_key'] = $entity->prod_key;
        $data['test_key'] = $entity->test_key;

        $ret = parent::update($id, $data);
        AppStatic::log()->addNotice('Редактирана секция', ['sik' => $ret->sik, 'data' => $data ]);
        return $ret;
    }
    public function getValidator(bool $isCreate = false) : Validator
    {
        $validator = parent::getValidator($isCreate);
        $validator
            ->required('num')
            ->length(9, 'Номерът на СИК е 9 цифри');
        $validator
            ->required('mik')
            ->inArray(
                array_map(
                    'strval',
                    array_keys($this->getMiks())
                ),
                'Невалиден РИК'
            );

        return $validator;
    }
    public function getQRPayloadData(Entity $entity, string $mode) : string
    {
        $key = $this->db->one('SELECT keyenc FROM elections WHERE election = ?', [ $this->user->site ]);
        $key = base64_decode($key);
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
        $payload = [
            'mode'  => $mode,
            'sik'   => $entity->num,
            'key'   => $mode === 'test-sik' ? $entity->test_key : $entity->prod_key
        ];
        $encrypted = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(json_encode($payload), '', $nonce, $key);

        return base64_encode($nonce . $encrypted);
    }
    public function delete(mixed $id): void
    {
        parent::delete($id);
        AppStatic::log()->addNotice('Изтрита секция', ['sik' => $id ]);
    }
    public function hasTestStream(int $sik) : bool
    {
        return (bool) $this->db->one(
            'SELECT 1 FROM streams WHERE mode = ? AND election = ? AND sik = ? AND started IS NOT NULL AND ended IS NOT NULL',
            [ 2, $this->user->site, $sik ]
        );
    }
    public function hasRealStream(int $sik) : bool
    {
        return (bool) $this->db->one(
            'SELECT 1 FROM streams WHERE mode = ? AND election = ? AND sik = ? AND started IS NOT NULL AND ended IS NOT NULL',
            [ 3, $this->user->site, $sik ]
        );
    }
    public function realStreamNow(int $sik) : bool
    {
        return (bool) $this->db->one(
            'SELECT 1 FROM streams WHERE mode = ? AND election = ? AND sik = ? AND started IS NOT NULL AND ended IS NULL',
            [ 3, $this->user->site, $sik ]
        );
    }
    public function list(array $options) : TableQueryMapped
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
                case 'has_test';
                    $value = (bool) $v;
                    $this->repository->where(
                        (!$value ? 'NOT ' : '') . 'EXISTS (
                            SELECT
                                1
                            FROM
                                streams
                            WHERE
                                mode = ? AND
                                election = ? AND
                                sik = siks.sik AND
                                started IS NOT NULL AND
                                ended IS NOT NULL
                            )',
                        [ 2, $this->user->site ]
                    );
                    break;
                case 'has_real';
                    $value = (bool) $v;
                    $this->repository->where(
                        (!$value ? 'NOT ' : '') . 'EXISTS (
                            SELECT
                                1
                            FROM
                                streams
                            WHERE
                                mode = ? AND
                                election = ? AND
                                sik = siks.sik AND
                                started IS NOT NULL AND
                                ended IS NOT NULL
                            )',
                        [ 3, $this->user->site ]
                    );
                    break;
                case 'real';
                    $value = (bool) $v;
                    $this->repository->where(
                        (!$value ? 'NOT ' : '') . 'EXISTS (
                            SELECT
                                1
                            FROM
                                streams
                            WHERE
                                mode = ? AND
                                election = ? AND
                                sik = siks.sik AND
                                started IS NOT NULL AND
                                ended IS NULL
                            )',
                        [ 3, $this->user->site ]
                    );
                    break;
                default:
                    try {
                        $this->repository->filter($k, $v);
                    } catch (DBException $ignore) {
                    }
                    break;
            }
        }
        return $this->repository;
    }
    protected function getDevicePoints(int $sik) : array
    {
        $data = [];
        $udi = $this->db->one(
            'SELECT
                udi
            FROM
                devices_elections
            WHERE
                election = ? AND
                sik = ? AND
                registered IS NOT NULL
            ORDER BY registered DESC
            LIMIT 1',
            [ $this->user->site, $sik ]
        );
        if ($udi) {
            $data = array_map(
                function (array $item) {
                    $item['ts'] = date('d.m.Y H:i:s', (int) ceil((int) $item['ts'] / 1000));
    
                    return $item;
                },
                $this->db->all(
                    'SELECT
                        plugin_devicelocations_history.lat,
                        plugin_devicelocations_history.lon as lng,
                        plugin_devicelocations_history.ts
                    FROM
                        hmdm_public.plugin_devicelocations_history
                    JOIN
                        hmdm_public.devices ON plugin_devicelocations_history.deviceid = devices.id AND devices.number = ?',
                    [ $udi ]
                )
            );
        }

        return $data;
    }
    public function getStreamUrl(int $sik) : ?string
    {
        $data = $this->db->one(
            'SELECT
                restreamers.host,
                siks.num
            FROM
                restreamers
            JOIN
                restreamer_servers ON restreamer_servers.restreamer = restreamers.restreamer
            JOIN
                streams ON streams.server = restreamer_servers.server AND
                streams.mode = ? AND
                streams.election = ? AND
                streams.sik = ? AND
                streams.started IS NOT NULL AND
                streams.ended IS NULL
            JOIN
                siks ON siks.sik = streams.sik AND
                siks.election = streams.election
            JOIN
                restreamer_miks ON restreamer_miks.restreamer = restreamers.restreamer AND
                restreamer_miks.mik = siks.mik',
            [ 3, $this->user->site, $sik ]
        );

        return isset($data) ? 'https://' . $data['host'] . '/' . $data['num'] . '.m3u8' : null;
    }
    protected function getRecords(int $sik) : array
    {
        return array_map(
            function (array $item) {
                return [
                    'url'   => $item['url'],
                    'title' => date('d.m.Y H:i:s', strtotime($item['created'])) . ' - ' . $item['name'],
                ];
            },
            $this->db->all(
                'SELECT
                    recordings.url,
                    recordings.created,
                    modes.name
                FROM
                    recordings
                JOIN
                    modes ON modes.mode = recordings.mode
                WHERE
                    recordings.sik = ?
                ORDER BY
                    recordings.created ASC',
                [ $sik ]
            )
        );
    }
    public function read(mixed $id) : Entity
    {
        $entity = parent::read($id);
        $entity->map = json_encode($this->getDevicePoints((int) $entity->sik));
        $entity->records = $this->getRecords((int) $entity->sik);

        return $entity;
    }
}
