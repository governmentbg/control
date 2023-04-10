<?php

use helpers\html\Field;

 $this->layout('main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<h3 class="ui left floated header">
    <i class="<?= $this->e($modules[$url->getSegment(0)]['icon']) ?> icon"></i>
    <span class="content"><?= $this->e($intl($url->getSegment(0, 'dashboard') . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form method="get" class="ui inline form grid center aligned">
        <div class="row">
            <div class="six wide column">
                <div class="inline field">
                    <?= $this->insert(
                        'common/field/timerange',
                        [
                            'field' => (new Field(
                                'timerange',
                                [ 'name'    => 'period' ],
                                [ 'label'   => 'Период' ]
                            ))->setValue($period)
                        ]
                    ); ?>
                    <button class="ui green icon labeled submit button"><i class="find icon"></i> Зареди</button>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="ui segment">
    <div id="map"></div>
    <script nonce="<?= $this->e($cspNonce) ?>">
        function setMarkers(cluster, values) {
            values.forEach(function (val) {
                cluster.addLayer(
                    L.marker(
                        [ val.lat, val.lng ],
                        {
                            icon: L.divIcon({
                                className: 'map-marker ' + (val.real ? 'map-real' : '')
                            })
                        }
                    )
                    .bindTooltip(
                        val.num + ' (' + val.number + ')',
                        {
                            permanent: false,
                            direction: 'top'
                        }
                    )
                    .on('click', function () {
                        window.location = "<?= $this->e($url('siks')); ?>?num=" + val.num
                    })
                )
            });
        }
        $('#map').on('resize', function () {
            var map = L
                .map('map', { scrollWheelZoom : true })
                .setView(
                    [ 42.7249925, 25.4833039 ],
                    8
                );
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: ' - ',
                crossOrigin: 'anonymous'
            }).addTo(map);
            var values = <?= json_encode(array_values($points)); ?>,
                cluster = L.markerClusterGroup();
            setMarkers(cluster, values);
            map.addLayer(cluster);

            setInterval(
                function () {
                    $.ajax({
                        method: "GET",
                        url: "<?= $this->e($url('siksmap/points')); ?>",
                        data: { period: "<?= $this->e($period); ?>"}
                    })
                    .done(function (data) {
                        cluster.removeLayers(cluster.getLayers());
                        setMarkers(cluster, data);
                        cluster.refreshClusters();
                    })
                    .fail(function () {
                        window.location.reload();
                    });
                },
                30000
            );
        });
        setTimeout(function () {
            $('#map').resize();
        }, 500);
    </script>
    <style nonce="<?= $this->e($cspNonce) ?>">
        .map-marker { background:blue; border-radius:10px !important; border: 1px solid white; box-shadow:0 0 3px blue; width: 20px !important; height: 20px !important; }
        .map-marker.map-device { background: gray; }
        .map-marker.map-real { background: green; }
        #map { height: calc(100vh - 105px); }
        div.leaflet-bottom.leaflet-right { display: none !important; }
    </style>
</div>