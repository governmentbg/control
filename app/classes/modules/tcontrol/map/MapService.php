<?php

declare(strict_types=1);

namespace modules\tcontrol\map;

use vakata\database\DBInterface;

class MapService
{
    protected DBInterface $db;

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }
    public function getDevicesCoordinates(?string $period = null) : array
    {
        if (isset($period) && strlen($period)) {
            $period = array_map(
                function (string $item) {
                    return (int) strtotime($item) * 1000;
                },
                explode('-', $period, 2)
            );
        } else {
            $period = null;
        }

        $data = $this->db->all(
            'SELECT
                hmdm_public.devices.number,
                hmdm_public.plugin_devicelocations_latest.lat,
                hmdm_public.plugin_devicelocations_latest.lon as lng,
                (
                    SELECT
                        siks.num
                    FROM
                        public.siks
                    WHERE
                        EXISTS (
                            SELECT
                                1
                            FROM
                                public.devices_elections
                            WHERE
                                public.devices_elections.udi::varchar = hmdm_public.devices.number AND
                                public.devices_elections.sik = public.siks.sik
                        )
                    LIMIT 1
                ) as num,
                (
                    SELECT
                        1
                    FROM
                        public.streams
                    WHERE
                        public.streams.sik = (
                            SELECT
                                public.devices_elections.sik
                            FROM
                                public.devices_elections
                            WHERE
                                public.devices_elections.udi::varchar = hmdm_public.devices.number
                            LIMIT 1
                        ) AND
                        public.streams.ended IS NULL
                    LIMIT 1
                ) as real
            FROM
                hmdm_public.plugin_devicelocations_latest
            JOIN
                hmdm_public.devices ON
                hmdm_public.plugin_devicelocations_latest.deviceid = hmdm_public.devices.id' .
            (
                isset($period) && is_array($period) ?
                    ' WHERE hmdm_public.plugin_devicelocations_latest.ts BETWEEN ? AND ?' :
                    ''
            ),
            $period
        );

        return $data;
    }
}