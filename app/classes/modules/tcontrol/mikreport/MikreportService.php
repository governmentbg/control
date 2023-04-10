<?php

declare(strict_types=1);

namespace modules\tcontrol\mikreport;

use vakata\database\DBInterface;
use vakata\user\User;

class MikreportService
{
    protected DBInterface $db;
    protected User $user;

    public function __construct(DBInterface $db, User $user)
    {
        $this->db = $db;
        $this->user = $user;
    }
    public function getData() : array
    {
        return $this->db->all(
            "SELECT
                CONCAT(miks.mik, ' ', miks.name) as name,
                (
                    SELECT
                        COUNT(*)
                    FROM
                        siks
                    WHERE
                        siks.mik = miks.mik AND
                        siks.video = 1 AND
                        siks.election = ?
                ) as cnt,
                (
                    SELECT
                        COUNT(*)
                    FROM
                        siks
                    WHERE
                        siks.mik = miks.mik AND
                        siks.video = 1 AND
                        siks.election = ? AND
                        EXISTS (
                            SELECT
                                1
                            FROM
                                streams
                            WHERE
                                streams.mode = ? AND
                                streams.election = siks.election AND
                                streams.sik = siks.sik AND
                                streams.started IS NOT NULL AND
                                streams.ended IS NOT NULL AND
                                streams.created > ?
                        )
                ) as has_test,
                (
                    SELECT
                        COUNT(*)
                    FROM
                        siks
                    WHERE
                        siks.mik = miks.mik AND
                        siks.video = 1 AND
                        siks.election = ? AND
                        EXISTS (
                            SELECT
                                1
                            FROM
                                streams
                            WHERE
                                streams.mode = ? AND
                                streams.election = siks.election AND
                                streams.sik = siks.sik AND
                                streams.started IS NOT NULL AND
                                streams.ended IS NOT NULL AND
                                streams.created > ?
                        )
                ) as has_real,
                (
                    SELECT
                        COUNT(*)
                    FROM
                        siks
                    WHERE
                        siks.mik = miks.mik AND
                        siks.video = 1 AND
                        siks.election = ? AND
                        EXISTS (
                            SELECT
                                1
                            FROM
                                streams
                            WHERE
                                streams.mode = ? AND
                                streams.election = siks.election AND
                                streams.sik = siks.sik AND
                                streams.started IS NOT NULL AND
                                streams.ended IS NULL AND
                                streams.created > ?
                        )
                ) as real
            FROM
                miks
            ORDER BY
                miks.mik ASC",
            [
                $this->user->site,
                $this->user->site,
                2,
                '2023-04-01 00:00:00',
                $this->user->site,
                3,
                '2023-04-01 00:00:00',
                $this->user->site,
                3,
                '2023-04-01 00:00:00'
            ]
        );
    }
}