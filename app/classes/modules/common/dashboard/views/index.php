<?php $this->layout('main'); ?>

<div class="dashboard-div">
<?php if (false && count($errors)) : ?>
<div class="ui icon error message">
    <i class="warning sign icon"></i>
    <div class="content">
        <div class="header"><?= $this->e($intl('admin_warnings')) ?></div>
        <ul><li>
        <?=
            implode(
                '</li><li>',
                array_map(
                    function ($v) use ($intl) {
                        return $this->e($intl($v));
                    },
                    $errors
                )
            )
        ?>
        </li></ul>
    </div>
</div>
<?php endif ?>
<div class="ui four small statistics">
    <div class="statistic">
        <div class="value"><i class="icon mobile small"></i> &nbsp;<?= $this->e($devices['count']); ?> <small>/ <?= $this->e($devices['total']); ?></small></div>
        <div class="label">Провизирани устройства</div>
    </div>
    <div class="statistic">
        <div class="value"><i class="icon check green small"></i> &nbsp;<?= $this->e($sik_test['count']); ?> <small>/ <?= $this->e($sik_test['total']); ?></small></div>
        <div class="label">Секции с успешен тест</div>
    </div>
    <div class="statistic">
        <div class="value"><i class="icon broadcast tower small"></i> &nbsp;<?= $this->e($sik_real['count']); ?> <small>/ <?= $this->e($sik_real['total']); ?></small></div>
        <div class="label">Секции с излъчване</div>
    </div>
    <div class="statistic">
        <div class="value"><i class="icon broadcast tower orange small"></i> &nbsp;<?= $this->e($sik_live['count']); ?> <small>/ <?= $this->e($sik_live['total']); ?></small></div>
        <div class="label">Секции с онлайн излъчване</div>
    </div>
</div>
<br/><br/>
<div class="ui grey divider"></div>
<br/><br/>

<div class="ui eight basic cards">
<?php foreach ($servers as $server) : ?>
    <?php
    $temp = json_decode($server['monitor'] ?? '{}', true) ?? [];
    $load = $temp['cpu_percent'][1] ?? '-';
    $cnt = 0;
    foreach ($temp['rtmp_stats']['rtmp']['server']['application'] ?? [] as $app) {
        $cnt += (int)($app['live']['nclients'] ?? 0);
    }
    $color = !$server['enabled'] ? 'grey' : ($load > 80 ? 'red' : ($load > 50 ? 'yellow' : 'green'));
    if ($server['enabled'] && (!isset($temp['timestamp']) || time() - $temp['timestamp'] > 10 * 60)) {
        $color = 'purple';
    }
    ?>
    <div class="<?= $color ?> card <?= $this->e(explode('-', $server['inner_host'])[0]) ?>">
        <div class="content">
            <small class="right floated">
                <?php if ($cnt) : ?>
                    <i class="users icon"></i> <?= $this->e($cnt) ?>
                <?php endif ?>
                <?php if ($load != '-') : ?>
                    <i class="microchip icon"></i> <?= $this->e($load) ?>%
                <?php endif ?>
            </small>
            <strong><?= $this->e($server['inner_host']) ?></strong>
            <!-- <div class="meta">
                <small><?= $this->e($server['host']) ?></small>
            </div> -->
        </div>
    </div>
<?php endforeach ?>
</div>
<div class="ui grey divider"></div>
<div class="ui eight basic cards">
<?php foreach ($restreamers as $server) : ?>
    <?php
    $temp = json_decode($server['monitor'] ?? '{}', true) ?? [];
    $load = $temp['cpu_percent'][1] ?? '-';
    $cnt = 0;
    foreach ($temp['rtmp_stats']['rtmp']['server']['application'] ?? [] as $app) {
        $cnt += (int)($app['live']['nclients'] ?? 0);
    }
    $color = !$server['enabled'] ? 'grey' : ($load > 80 ? 'red' : ($load > 50 ? 'yellow' : 'green'));
    if ($server['enabled'] && (!isset($temp['timestamp']) || time() - $temp['timestamp'] > 10 * 60)) {
        $color = 'purple';
    }
    ?>
    <div class="<?= $color ?> card <?= $this->e(explode('-', $server['inner_host'])[0]) ?>">
        <div class="content">
            <small class="right floated">
                <?php if ($cnt) : ?>
                    <i class="users icon"></i> <?= $this->e($cnt) ?>
                <?php endif ?>
                <?php if ($load != '-') : ?>
                    <i class="microchip icon"></i> <?= $this->e($load) ?>%
                <?php endif ?>
            </small>
            <strong><?= $this->e($server['inner_host']) ?></strong>
            <!-- <div class="meta">
                <small><?= $this->e($server['host']) ?></small>
            </div> -->
        </div>
    </div>
<?php endforeach ?>
</div>


<br/><br/>
<div class="ui grey divider"></div>
<br/><br/>
<?php
$colors = ['red','orange','yellow','olive','green','teal','blue','violet','purple','pink','brown','grey','black'];
?>

    <?php $parent = null;
    $k = 0; ?>
    <?php foreach ($modules as $name => $module) : ?>
        <?php
        if (!isset($module['dashboard']) || !$module['dashboard']) {
            continue;
        }
        if (isset($module['parent']) && $parent !== $module['parent']) {
            if ($parent !== null) {
                echo '</div>';
                echo '<br /><br />';
            }
            $parent = $module['parent'];
            if ($parent) {
                echo '<h3 class="ui grey horizontal left aligned clearing divider header">';
                echo '<i class="small cube icon"></i>&nbsp;&nbsp;<span>' . $this->e($intl($parent)) . '</span>';
                echo '</h3>';
            }
            echo '<div class="ui stackable cards">';
        }
        ?>
        <a href="<?= $this->e($url($name)) ?>"
            class="ui <?= $module['color'] ?? $colors[++$k % count($colors)] ?> card">
            <span class="content">
                <span class="ui <?= $module['color'] ?? $colors[$k % count($colors)] ?> icon header"
                    href="<?= $this->e($url($name)) ?>">
                    <i class="<?= $module['color'] ?? $colors[$k % count($colors)] ?>
                        <?= isset($module['icon']) ? $module['icon'] : 'settings' ?> icon"></i>
                    <span class="content">
                        <span class="ui <?= $module['color'] ?? $colors[$k % count($colors)] ?> header">
                            <?= $this->e($intl($name . '.title')) ?>
                        </span>
                    </span>
                </span>
            </span>
            <span class="ui extra content ">
                <?= $this->e($intl($name . '.description')) ?>
            </span>
        </a>
    <?php endforeach ?>
        <?php if ($parent !== null) : ?>
        </div><br /><br /><br />
        <?php endif ?>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.cards .header { padding-top:2rem; }
.cards .extra.content { text-align:center !important; background:#fafafa !important; }
.divider.header { text-transform: uppercase; }
.dashboard-div { padding:2rem; }
/* .stackable.cards .header .content { display:block; }
.module-link { display:block !important; padding:2rem 0 1rem 0 !important; }
.module-link .content { padding:0.5rem 1rem !important; }
.module-link .sub.header { padding-top:0.2rem !important; display:none !important; } */
.dashboard-div .cards .card { border: 1px solid #ddd !important; }
.ingest, .stream { cursor: pointer; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.ingest').on('click', function () { window.location = '<?= $url('servers') ?>'; });
$('.stream').on('click', function () { window.location = '<?= $url('restreamers') ?>'; });
setTimeout(function () { window.location.reload(); }, 10000);
</script>