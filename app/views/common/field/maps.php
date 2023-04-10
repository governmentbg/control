<?php
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'maps_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$defaults = [
    'height' => 700,
    'zoom'   => 10,
    'center' => [ 42.7249925, 25.4833039 ]
];

$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$options = array_merge($defaults, $field->getOptions());

if ($field->hasAttr('value') && ($temp = json_decode($field->getValue(), true))) {
    $field->setValue($temp);
} else {
    $field->delAttr('value');
}
$value = $field->getValue();
if (isset($value) && is_array($value) && count($value)) {
    $options['center'][0] = $value[0]['lat'];
    $options['center'][1] = $value[0]['lng'];
}
?>
<?php if (strlen($field->getOption('label', ''))) : ?>
    <label>
        <?php if ($field->getOption('tooltip')) : ?>
            <span 
                data-tooltip="<?= $this->e($intl($field->getOption('tooltip'))) ?>"
                data-inverted="">
                <i class="question circle icon"></i>
            </span>
        <?php endif ?>
        <?= $this->e($intl($field->getOption('label'))) ?>
    </label>
<?php endif ?>
<div id="<?= $this->e($field->getAttr('id')) ?>"></div>
<script nonce="<?= $this->e($cspNonce) ?>">
(function () {
    $('#<?= $this->e($field->getAttr("id")) ?>').on('resize', function () {
        var map = L
            .map('<?= $this->e($field->getAttr("id")) ?>', { scrollWheelZoom : false })
            .setView(
                [<?= $this->e($options['center'][0]) ?>, <?= $this->e($options['center'][1]) ?> ],
                <?= $this->e($options['zoom']) ?>
            );
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: ' - ',
            crossOrigin: 'anonymous'
        }).addTo(map);
        var marker = null;
        <?php if ($field->hasAttr('value')) : ?>
            var values = <?= json_encode($field->getValue()); ?>;
            values.forEach(function (val) {
                L.marker(
                    [ val.lat, val.lng ],
                    {
                        icon: L.divIcon({
                            className: 'map-marker'
                        })
                    }
                )
                .bindTooltip(
                    val.ts,
                    {
                        permanent: false,
                        direction: 'top'
                    }
                )
                .addTo(map);
            });
        <?php endif ?>
    });
    setTimeout(function () {
        $('#<?= $this->e($field->getAttr("id")) ?>').resize();
    }, 500);
}());
</script>
<style nonce="<?= $this->e($cspNonce) ?>">
.map-marker { background:blue; border-radius:6px !important; border:1px solid white; box-shadow:0 0 3px blue; }
#<?= $this->e($field->getAttr('id')) ?>_buttons { padding-top:5px; }
#<?= $this->e($field->getAttr('id')) ?> { height: <?= $this->e($options['height']) ?>px }
div.leaflet-bottom.leaflet-right { display: none !important; }
</style>
