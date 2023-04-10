<?php $this->layout('main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated black header settings-header">
        <i class="cogs icon"></i>
        <span class="content"><?= $this->e($intl('settings.title')) ?></span>
    </h3>
</div>
<?php $this->stop() ?>

<div class="ui stackable cards">
    <div class="ui orange card">
        <div class="center aligned content">
            <div class="ui icon header">
                <i class="lightning icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.clearcache.title')) ?></p>
                    <div class="sub header">
                        <?= $this->e($intl('settings.clearcache.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra center aligned content">
            <form method="post" class="ui form" action="<?= $this->e($url('settings/clear')) ?>">
                <button class="ui orange labeled icon button">
                    <i class="trash icon"></i> <?= $this->e($intl('settings.cache.clear')) ?>
                </button>
            </form>
        </div>
    </div>

    <div class="ui olive card">
        <div class="center aligned content">
            <div class="ui icon header">
                <i class="database icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.database.title')) ?></p>
                    <div class="sub header">
                        <?= $this->e($intl('settings.database.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra center aligned content">
            <form method="post" class="ui form" action="<?= $this->e($url('settings/database')) ?>">
                <button class="ui olive labeled icon button">
                    <i class="refresh icon"></i> <?= $this->e($intl('settings.database.refresh')) ?>
                </button>
            </form>
        </div>
    </div>

    <div class="ui teal card">
        <div class="center aligned content">
            <div class="ui icon header">
                <i class="trash alternate icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.clearfiles.title')) ?></p>
                    <div class="sub header">
                        <?= $this->e($intl('settings.clearfiles.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra center aligned content">
            <form method="post" class="ui form" action="<?= $this->e($url('settings/clearfiles')) ?>">
                <button class="ui teal labeled icon button">
                    <i class="check icon"></i> <?= $this->e($intl('settings.clearfiles.clear')) ?>
                </button>
            </form>
        </div>
    </div>
    <div class="ui purple card">
        <div class="center aligned content">
            <a class="ui center aligned purple icon header"
                href="<?= $this->e($url('modules')) ?>">
                <i class="puzzle icon"></i>
                <div class="content">
                    <?= $this->e($intl('modules.title')) ?>
                    <div class="sub header">
                        <?= $this->e($intl('modules.description')) ?>
                    </div>
                </div>
            </a>
        </div>
        <div class="extra center aligned content">
            <form method="get" class="ui form" action="<?= $this->e($url('modules')) ?>">
                <button class="ui purple labeled icon button">
                    <i class="puzzle icon"></i> <?= $this->e($intl('modules.title')) ?>
                </button>
            </form>
        </div>
    </div>
</div>
<div class="ui divider"></div>

<?php if (!$writable) : ?>
<div class="ui icon warning message">
    <i class="warning sign icon"></i>
    <div class="content">
        <div class="header"><?= $this->e($intl('settings.config_not_writable')) ?></div>
    </div>
</div>
<?php endif ?>

<div class="ui stackable cards">
    <div class="ui <?= !$debug ? 'green' : 'red' ?> card">
        <div class="center aligned content">
            <div class="ui <?= !$debug ? 'green' : 'red' ?> icon header">
                <i class="bug icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.debug.title')) ?></p>
                    <div class="ui divider"></div>
                    <p><?= $this->e($intl($debug ? 'settings.ison' : 'settings.isoff')) ?></p>
                    <div class="sub header">
                        <?= $this->e($intl('settings.debug.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($writable) : ?>
        <div class="extra center aligned content">
            <form method="post" class="ui form" action="<?= $this->e($url('settings/debug')) ?>">
                <button class="ui <?= !$debug ? 'gray' : 'green' ?> labeled icon button">
                    <i class="<?= $debug ? 'remove' : 'check' ?> icon"></i> 
                    <?= $this->e($intl('settings.' . ($debug ? 'off' : 'on'))) ?>
                </button>
            </form>
        </div>
        <?php endif ?>
    </div>
    <div class="ui <?= !$maintenance ? 'green' : 'red' ?> card">
        <div class="center aligned content">
            <div class="ui <?= !$maintenance ? 'green' : 'red' ?> icon header">
                <i class="configure icon"></i>
                <div class="content">
                    <p><?= $this->e($intl('settings.maintenance.title')) ?></p>
                    <div class="ui divider"></div>
                    <p><?= $this->e($intl($maintenance ? 'settings.ison' : 'settings.isoff')) ?></p>
                    <div class="sub header">
                        <?= $this->e($intl('settings.maintenance.description')) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($writable) : ?>
        <div class="extra center aligned content">
            <form method="post" class="ui form" action="<?= $this->e($url('settings/maintenance')) ?>">
                <button class="ui <?= !$maintenance ? 'gray' : 'green' ?> labeled icon button">
                    <i class="<?= $maintenance ? 'remove' : 'check' ?> icon"></i> 
                    <?= $this->e($intl('settings.' . ($maintenance ? 'off' : 'on'))) ?>
                </button>
            </form>
        </div>
        <?php endif ?>
    </div>
    <?php
    foreach (
        [
        'https' => [ $https, 'lock' ],
        'totp' => [ $totp, 'mobile' ],
        'cors' => [ $cors, 'plug' ],
        'csrf' => [ $csrf, 'spy' ],
        'csp' => [ $csp, 'zoom' ],
        'ids' => [ $ids, 'hand paper' ],
        'ratelimit' => [ $ratelimit, 'wait' ],
        'gzip' => [ $gzip, 'file archive', true ],
        ] as $key => $data
    ) : ?>
        <?php list($value, $icon) = $data; ?>
        <div class="ui <?= $value ? 'green' : 'red' ?> card">
            <?php if (isset($data[2]) && $data[2]) : ?>
                <span class="ui orange corner label">
                    <i class="warning sign icon"></i>
                </span>
            <?php endif ?>
            <div class="center aligned content">
                <div class="ui <?= $value ? 'green' : 'red' ?> icon header">
                    <i class="<?= $icon ?> icon"></i>
                    <div class="content">
                        <p><?= $this->e($intl('settings.' . $key . '.title')) ?></p>
                        <div class="ui divider"></div>
                        <p><?= $this->e($intl($value ? 'settings.ison' : 'settings.isoff')) ?></p>
                        <div class="sub header">
                            <?= $this->e($intl('settings.' . $key . '.description')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($writable) : ?>
            <div class="extra center aligned content">
                <form method="post" class="ui form" action="<?= $this->e($url('settings/' . $key)) ?>">
                    <button class="ui <?= $value ? 'gray' : 'green' ?> labeled icon button">
                        <i class="<?= $value ? 'remove' : 'check' ?> icon"></i> 
                        <?= $this->e($intl('settings.' . ($value ? 'off' : 'on'))) ?>
                    </button>
                </form>
            </div>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>

<?php if ($files || $shell || $adminer) : ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.special.card').hide();
$('.cards').on('dblclick', function () {
    $('.special.card').toggle();
});
</script>
<?php endif ?>

<?php if ($files) : ?>
<div class="ui modal" id="files_modal">
    <i class="close icon"></i>
    <form class="ui form" method="post"
        action="<?= $this->e($url('settings/updateFiles')) ?>">
        <div class="scrolling content">
            <h3 class="dividing header"><?= $this->e($intl('settings.files.results')) ?></h3>
            <div class="ui inverted dimmer">
                <div class="content">
                    <div class="center">
                        <div class="ui text loader dimmer-message dimmer-message-load">
                            <?= $this->e($intl('common.pleasewait')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <table class="ui definition basic compact table">
                <tbody>
                    <tr><td>File</td><td></td></tr>
                    <tr><td>Read</td><td></td></tr>
                    <tr class="negative"><td>Hash</td><td>
                        <div class="ui icon mini transparent fluid input">
                            <input id="hashFiles"
                                placeholder="<?= $this->e($intl('settings.files.hash')) ?>" type="password">
                            <i class="remove icon"></i>
                        </div>
                    </td></tr>
                    <tr><td>Version</td><td></td></tr>
                    <tr><td>Generated</td><td></td></tr>
                    <tr><td>Created</td><td class="mono-date"></td></tr>
                    <tr><td>Changed</td><td class="mono-date"></td></tr>
                    <tr><td>Deleted</td><td class="mono-date"></td></tr>
                </tbody>
            </table>
        </div>
    </form>
</div>

<style nonce="<?= $this->e($cspNonce) ?>">
.settings-title {padding:0.5rem !important;}
.mono-date { font-family: monospace !important; white-space: pre !important; }
</style>
<?php endif ?>