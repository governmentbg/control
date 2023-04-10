<?php $this->layout('common/master'); ?>

<?php $this->start('head'); ?>

    <link rel="stylesheet" href="<?= $asset('assets/static/perfect-scrollbar/perfect-scrollbar.css') ?>" />
    <script src="<?= $asset('assets/static/perfect-scrollbar/perfect-scrollbar.min.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/plupload/plupload.css') ?>" />
    <script src="<?= $asset('assets/static/plupload/plupload.full.min.js') ?>"></script>
    <script src="<?= $asset('assets/plupload/plupload.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/static/leaflet/leaflet.css') ?>" />
    <script src="<?= $asset('assets/static/leaflet/leaflet.js') ?>"></script>
    <link rel="stylesheet" href="<?= $asset('assets/static/leaflet/markercluster.css') ?>" />
    <link rel="stylesheet" href="<?= $asset('assets/static/leaflet/markercluster.default.css') ?>" />
    <script src="<?= $asset('assets/static/leaflet/markercluster.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/static/jstree/themes/default/style.min.css') ?>" />
    <script src="<?= $asset('assets/static/jstree/jstree.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/dtpckr/dtpckr.css') ?>" />
    <script src="<?= $asset('assets/dtpckr/dtpckr.js') ?>"></script>

    <script nonce="<?= $this->e($cspNonce) ?>">window.tinyNonce = "<?= $this->e($cspNonce) ?>";</script>
    <script src="<?= $asset('assets/static/tinymce/tinymce.min.js') ?>"></script>

    <link rel="stylesheet" href="<?= $asset('assets/main.css') ?>" />
    <script src="<?= $asset('assets/validator.js') ?>"></script>
    <script src="<?= $asset('assets/static/jq-tablesort/tablesort.min.js') ?>"></script>
    <script src="<?= $asset('assets/static/moment/moment.min.js') ?>"></script>
    <script src="<?= $asset('assets/static/urijs/URI.min.js') ?>"></script>
    <script src="<?= $asset('assets/main.js') ?>"></script>
    <script src="<?= $asset('assets/static/videojs/video.min.js') ?>"></script>
    <link rel="stylesheet" href="<?= $asset('assets/static/videojs/video-js.css') ?>" />
    <script nonce="<?= $cspNonce ?>">
    $.fn.popup.settings.transition = 'zoom';
    $.fn.transition.settings.duration = '0';
    $.fn.dimmer.settings.duration = '0';
    $.fn.modal.settings.duration = '0';
    $.fn.dropdown.settings.duration = '0';
    $.fn.modal.settings.detachable = true;
    $.fn.modal.settings.centered = false;
    $.fn.popup.settings.duration = '0';
    $.fn.popup.settings.inline = false;
    $.fn.popup.settings.movePopup = false;
    $.fn.popup.settings.onVisible = function (module) {
        var o = $(module).offset();
        this.appendTo(document.body).css({
            'top' : $(module).outerHeight() + o.top + 0,
            'bottom' : 'unset',
            'left' : $(module).outerWidth() + o.left - (this.hasClass('right') ? this.outerWidth() - 8 : 0),
            'right' : 'unset'
        });
    };
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (!settings.crossDomain) {
                xhr.setRequestHeader('X-CSPNonce', '<?= $cspNonce ?>');
            }
        }
    });
    window.fromLS = '<?= $this->e($intl('common.fromLS')) ?>';
    <?php if ($config('PUSH_NOTIFICATIONS') && is_file($config('STORAGE_KEYS') . '/push_public.txt')) : ?>
    window.onload = function () {
        if (Notification.permission === "default") {
            $('body').one('click', function () {
                Notification.requestPermission().then(perm => {
                    if (Notification.permission === "granted") {
                        registerWorker();
                    }
                });
            });
        } else if (Notification.permission === "granted") {
            registerWorker();
        }
    };
    function registerWorker() {
        navigator.serviceWorker.register(
            "<?= $url('service_worker.js') ?>", 
            { scope: "<?= $url->getBasePath() ?>" }
        ).then(function (registration) {
            registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey:
                    `<?=
                        str_replace(
                            ["\r", "\n"],
                            "",
                            @file_get_contents($config('STORAGE_KEYS') . '/push_public.txt') ?: ''
                        )
                        ?>`
            }).then(function (subscription) {
                $.post("<?= $url("pushnotifications") ?>", { "subscription" : JSON.stringify(subscription) });
            });
        });
    }
    <?php endif ?>
    </script>

<?php $this->stop(); ?>

<div class="menu">
    <div class="menu-top">
        <a href="#" class="ui tiny icon button menu-toggle">
            <i class="ui bars icon"></i>
        </a>
        <div class="ui breadcrumb">
            <?php if ($user->site !== null && count($user->sites) > 0 && isset($user->sites[$user->site])) : ?>
            <div class="ui floating dropdown labeled icon mini button site-dropdown">
                <i class="world icon"></i>
                <span class="text"><?= $this->e($user->sites[$user->site]) ?></span>
                <div class="menu">
                    <div class="ui icon search input">
                    <i class="search icon"></i>
                    <input type="text" placeholder="">
                    </div>
                    <div class="divider"></div>
                    <div class="scrolling menu">
                        <?php foreach ($user->sites as $k => $v) : ?>
                            <div class="item" data-value="<?= $this->e($k) ?>"><?= $this->e($v) ?></div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
            <?php endif ?>
            <?php if ($url->getSegment(0) !== 'dashboard' && isset($modules[$url->getSegment(0)])) : ?>
                <?php if ($user->site !== null && count($user->sites) > 0 && isset($user->sites[$user->site])) : ?>
                    <i class="right angle icon divider"></i>
                <?php endif ?>
                <a href="<?= $url("") ?>" class="section">
                    <i class="home icon"></i> <?= $this->e($intl('common.home')) ?>
                </a>
                <i class="right angle icon divider"></i>
                <a href="<?= $this->e($url($url->getSegment(0))) ?>"
                    <?php if (isset($modules[$url->getSegment(0)]['color'])) : ?> 
                        class="section"
                    <?php else : ?>
                        <?php if (!isset($breadcrumb)) : ?>
                            class="section"
                        <?php else : ?>
                            class="section"
                        <?php endif ?>
                    <?php endif ?>
                    >
                    <?php if (isset($modules[$url->getSegment(0)]['icon'])) : ?>
                        <i
                            class="ui white <?= $this->e($modules[$url->getSegment(0)]['icon']) ?> icon"></i>
                    <?php endif ?>
                    <?= $this->e($intl($url->getSegment(0) . '.title')) ?>
                </a>
                <?php if (isset($breadcrumb)) : ?>
                    <i class="right angle icon divider"></i>
                    <span class="active section"><?= $breadcrumb ?></span>
                <?php endif ?>
            <?php else : ?>
                <!-- <div class="active section"><?= $this->e($intl('common.home')) ?></div> -->
            <?php endif ?>
        </div>
        <a class="ui right floated tiny compact red circular icon button floated-button"
            href="<?= $this->e($url($config('LOGIN_URL'))) ?>"
            title="<?= $this->e($intl('common.exit')) ?>">
            <i class="inverted <?= $user->impersonated ? 'spy' : 'power' ?> icon"></i>
        </a>
        <?php if ($config('FORUM') && isset($modules['forum'])) : ?>
        <a data-count="<?= min(99, $user->forums) ?>"
            class="ui <?= $user->forums ? 'unread olive' : 'gray' ?> right floated tiny compact
                circular icon button forums-button floated-button"
            href="<?= $this->e($url('topics')) ?>"
            title="<?= $this->e($intl('topics.title')) ?>">
            <i class="inverted chat icon"></i>
        </a>
        <div class="ui flowing popup bottom left transition hidden forums-popup">
            <div class="forums-list">
                <br /><br />
                <div class="ui active centered inline loader"></div>
                <br /><br />
            </div>
            <div class="ui divider"></div>
            <p class="centered">
                <a href="<?= $this->e($url('topics')) ?>" class="ui orange button">
                    <?= $this->e($intl('forums.all')) ?>
                </a>
            </p>
        </div>
        <?php endif ?>
        <?php if ($config('MESSAGING') && isset($modules['notifications'])) : ?>
        <a data-count="<?= min(99, $user->notifications) ?>"
            class="ui <?= $user->notifications ? 'unread teal' : 'gray' ?> right floated tiny compact 
                circular icon button notifications-button floated-button"
            href="<?= $this->e($url('notifications')) ?>"
            title="<?= $this->e($intl('notifications.title')) ?>">
            <i class="inverted mail icon"></i>
        </a>
        <div class="ui flowing popup bottom left transition hidden notifications-popup">
            <div class="notifications-list">
                <br /><br />
                <div class="ui active centered inline loader"></div>
                <br /><br />
            </div>
            <div class="ui divider"></div>
            <p class="centered">
                <a href="<?= $this->e($url('notifications')) ?>" class="ui teal button">
                    <?= $this->e($intl('notifications.all')) ?>
                </a>
            </p>
        </div>
        <?php endif ?>
        <?php if ($config('HELP') && isset($modules['help'])) : ?>
            <?php if ($helper || $user->hasPermission('help')) : ?>
        <a id="helper-show" 
            class="ui item right floated tiny compact circular icon button floated-button
                <?= $helper ? 'yellow' : 'gray' ?>"
            href="#" title="<?= $this->e($intl('help.show')) ?>">
            <i class="help circle icon"></i>
        </a>
            <?php endif ?>
        <?php endif ?>
        <?php if ($config('TRANSLATIONS') && isset($modules['translation'])) : ?>
            <?php if ($user->hasPermission('translation')) : ?>
        <a id="missing-translations"
            class="ui item right floated tiny compact gray circular icon button floated-button" href="#"
            title="<?= $this->e($intl('translation.missingtitle')) ?>">
            <i class="font icon"></i>
        </a>
            <?php endif ?>
        <?php endif ?>
        <?php if ($user->hasPermission('profile')) : ?>
        <a class="profile-button" href="<?= $this->e($url('profile')) ?>"
            title="<?= $this->e($intl('common.profile')) ?>">
        <?php else : ?>
        <span class="profile-button">
        <?php endif ?>
            <?php
            if ($user->avatar_data) {
                echo '<img class="ui right spaced avatar image" alt="" src="' . $this->e($user->avatar_data) . '">';
            } else {
                echo '<span class="ui grey circular label">';
                echo '<i class="ui user icon"></i>';
                echo '</span>';
            }
            ?>
            <span class="username"><?= $this->e($user->name) ?></span>
            <!--<div class="detail"><?= $this->e($user->getPrimaryGroup()) ?></div>-->
        <?php if ($user->hasPermission('profile')) : ?>
        </a>
        <?php else : ?>
        </span>
        <?php endif ?>
    </div>
    <div class="menu-side">
        <div class="ui fluid attached inverted vertical menu">
            <div class="item header main-header" title="<?= $this->e($config('VERSION')) ?>">
                <?= $this->e($intl($config('APPNAME'))) ?>
            </div>
            <div class="ui inverted fluid accordion">
                <?php $parent = null; ?>
                <?php foreach ($modules as $name => $module) : ?>
                    <?php
                    if (!isset($module['menu']) || !$module['menu']) {
                        continue;
                    }
                    if (isset($module['parent']) && $parent !== $module['parent']) {
                        if ($parent !== null) {
                            echo '</div>';
                        }
                        $parent = null;
                        if (strlen($module['parent'])) {
                            $parent = $module['parent'];
                            echo '<div class="title" data-title="' . $this->e($intl($parent)) . '">';
                            echo '<div class="header item vertical-menu-header">';
                            echo '<i class="dropdown icon vertical-menu-dropdown"></i> ' . $this->e($intl($parent));
                            echo '</div>';
                            echo '</div>';
                            echo '<div class="content">';
                        }
                    }
                    ?>
                    <a href="<?= $this->e($url($name === 'dashboard' ? '' : $name)) ?>"
                        class="item 
                            <?= str_replace('dashboard', '', $name) === (string)$url->getSegment(0) ? 'active' : '' ?>
                        ">
                        <?php if (isset($module['icon'])) : ?>
                            <i class="<?= $this->e($module['icon']) ?> icon"></i>
                        <?php endif ?>
                        <?= $this->e($intl($name . '.title')) ?>
                    </a>
                <?php endforeach ?>
                <?php
                if ($parent !== null) {
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <br />
    </div>

    <div class="content">
    <?php if ($config('MAINTENANCE')) : ?>
    <div class="ui warning message">
        <div class="header"><i class="configure icon"></i> <?= $this->e($intl('maintenance.mode')) ?></div>
    </div>
    <?php endif ?>
        <?= $this->section('title') ?>

        <?php if (isset($session) && $session->get('error')) : ?>
            <?php
            $mess = $session->del('error');
            if (!is_array($mess)) {
                $mess = [ $mess ];
            }
            $mess = implode(
                "<br />",
                array_map(function ($v) use ($intl) {
                    return $this->e($intl($v));
                }, $mess)
            );
            ?>
            <div class="ui error message">
                <div><?= $mess ?></div>
            </div>
        <?php endif ?>
        <?php if (isset($session) && $session->get('removeLS')) : ?>
            <script nonce="<?= $this->e($cspNonce) ?>">
                localStorage.removeItem("<?= $session->del('removeLS') ?>");
            </script>
        <?php endif ?>
        <?php if (isset($session) && $session->get('success')) : ?>
            <?php
            $mess = $session->del('success');
            if (!is_array($mess)) {
                $mess = [ $mess ];
            }
            $mess = implode(
                "<br />",
                array_map(function ($v) use ($intl) {
                    return $this->e($intl($v));
                }, $mess)
            );
            ?>
            <div class="ui positive message">
                <div><?= $mess ?></div>
            </div>
        <?php endif ?>
        
        <?php if ($config('HELP') && isset($modules['help'])) : ?>
        <div class="ui segment hide" id="helper">
            <?= $helper ?>
            <?php if ($user->hasPermission('help')) : ?>
                <div class="ui divider"></div>
                <p class="centered">
                    <a href="#" class="ui orange icon labeled button" id="helper-edit">
                        <i class="pencil icon"></i> <?= $this->e($intl('help.edit')) ?>
                    </a>
                </p>
            <?php endif ?>
            <script nonce="<?= $this->e($cspNonce) ?>">
            $('#helper-show')
                .click(function (e) {
                    e.preventDefault();
                    $('#helper').toggle();
                });
            </script>
        </div>
        <?php endif ?>

        <?= $this->section('content') ?>
    </div>
</div>

<link rel="stylesheet" href="<?= $asset('assets/darkroom/darkroom.css') ?>" />
<script src="<?= $asset('assets/static/fabric/fabric.js') ?>"></script>
<script src="<?= $asset('assets/darkroom/darkroom.js') ?>"></script>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.menu-top .site-dropdown').dropdown({
    onChange : function (v) {
        document.cookie = "" + 
            encodeURIComponent('<?= $config('APPNAME_CLEAN') ?>_SITE') + "=" + encodeURIComponent(v) + "; "+
            "expires=Fri, 31 Dec 9999 23:59:59 GMT; path=" + '<?= $url->getBasePath() ?>';
        window.location.reload();
    }
});
$('.menu-side .site-dropdown').dropdown({
    onChange : function (v) {
        document.cookie = "" + 
            encodeURIComponent('<?= $config('APPNAME_CLEAN') ?>_SITE') + "=" + encodeURIComponent(v) + "; "+
            "expires=Fri, 31 Dec 9999 23:59:59 GMT; path=" + '<?= $url->getBasePath() ?>';
        window.location.reload();
    }
});
</script>

<?php if ($config('HELP') && isset($modules['help']) && $user && $user->hasPermission('help')) : ?>
    <div id="helper-modal" class="ui modal">
        <form class="ui form validate-form" method="post"
            action="<?= $this->e($url('help')) ?>">
            <div class="ui inverted dimmer">
                <div class="content">
                    <div class="center">
                        <div class="ui text loader dimmer-message dimmer-message-load">
                            <?= $this->e($intl('common.pleasewait')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                echo $this->insert(
                    'common/field/hidden',
                    ['field' => new \helpers\html\Field('hidden', [ 'name' => 'url', 'value' => $url->getRealPath() ])]
                );
            ?>
            <div class="field">
                <?php
                    echo $this->insert(
                        'common/field/richtext',
                        [
                            'field' => new \helpers\html\Field(
                                'richtext',
                                [ 'id' => 'helper_content', 'name' => 'helper_content', 'value' => $helper ?? '' ],
                                ['label' => '' ]
                            )
                        ]
                    );
                ?>
            </div>
            <div class="ui section divider"></div>
            <div class="ui center aligned olive secondary segment">
                <button class="ui olive icon labeled submit button">
                    <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
                </button>
                <div class="ui basic cancel button">
                    <?= $this->e($intl('common.cancel')) ?>
                </div>
            </div>
        </form>
    </div>
    <script nonce="<?= $this->e($cspNonce) ?>">
    $('#helper-modal .cancel').on('click', function () {
        $(this).closest('.modal').modal('hide');
    });
    $(function () {
        //if (!!window.performance && window.performance.navigation.type === 2) {
        //    window.location.reload();
        //}
        $('#helper-modal').find('form').submit(function (e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                $.post($(this).attr('action'), $(this).serialize())
                    .always(function () {
                        $('#helper-modal').modal('hide');
                    });
            }
        });
        $("#helper-modal").modal();
        $('#helper-edit')
            .click(function (e) {
                e.preventDefault();
                $('#helper-modal').modal('show');
            });
    });
    </script>
<?php endif ?>

<?php if ($config('TRANSLATIONS') && isset($modules['translation'])) : ?>
    <?php
    $translations = ['used' => [], 'missing' => []];
    foreach ($intl->used() as $k => $v) {
        if ($k === '') {
            continue;
        }
        if ($v === null || mb_strtolower($k) === mb_strtolower($v)) {
            $translations['missing'][$k] = $k;
        } else {
            $translations['used'][$k] = $v;
        }
    }
    ?>
    <?php if ($user && $user->hasPermission('translation') && count($translations)) : ?>
    <div id="missing-translations-modal" class="ui modal">
        <div class="header"><?= $this->e($intl('translation.title')) ?></div>
        <div class="scrolling content">
            <form class="ui form validate-form" method="post"
            action="<?= $this->e($url('translation/missing')) ?>">
                <div class="ui inverted dimmer">
                    <div class="content">
                        <div class="center">
                            <div class="ui text loader dimmer-message dimmer-message-load">
                                <?= $this->e($intl('common.pleasewait')) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (count($translations['missing'])) : ?>
                    <p><?= $this->e($intl('translation.missingdescription')) ?></p>
                    <?php
                    ksort($translations['missing']);
                    foreach ($translations['missing'] as $t) {
                        echo '<div class="two fields">';
                        echo '<div class="ui field">';
                        echo '<div class="ui input">';
                        echo '<input name="keys[]" readonly value="' . $this->e($t) . '" />';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="ui field">';
                        echo '<div class="ui input">';
                        echo '<input name="values[]" value="" />';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                    <div class="ui section divider"></div>
                <?php endif ?>
                <?php if (count($translations['used'])) : ?>
                    <p><?= $this->e($intl('translation.useddescription')) ?></p>
                    <?php
                    ksort($translations['used']);
                    foreach ($translations['used'] as $t => $tt) {
                        echo '<div class="two fields">';
                        echo '<div class="ui field">';
                        echo '<div class="ui input">';
                        echo '<input name="keys[]" readonly value="' . $this->e($t) . '" />';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="ui field">';
                        echo '<div class="ui input">';
                        echo '<input name="values[]" value="' . $this->e($tt) . '" />';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                <?php endif ?>
                <div class="ui center aligned olive secondary segment">
                    <button class="ui olive icon labeled submit button">
                        <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
                    </button>
                    <div class="ui basic cancel button">
                        <?= $this->e($intl('common.cancel')) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script nonce="<?= $this->e($cspNonce) ?>">
    $('#missing-translations-modal .cancel').on('click', function () {
        $(this).closest('.modal').modal('hide');
    });
    $(function () {
        //if (!!window.performance && window.performance.navigation.type === 2) {
        //    window.location.reload();
        //}
        $('#missing-translations-modal').find('form').submit(function (e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                $.post($(this).attr('action'), $(this).serialize())
                    .done(function () {
                        $('#missing-translations').removeClass('olive').addClass('gray');
                    })
                    .always(function () {
                        $('#missing-translations-modal').find('form').find('.dimmer').dimmer('hide');
                        $('#missing-translations-modal').modal('hide');
                    });
            }
        });
    });
    </script>

        <?php if (count($translations['missing'])) : ?>
        <script nonce="<?= $this->e($cspNonce) ?>">
        $(function () {
            $('#missing-translations').removeClass('gray').addClass('olive')
                .click(function (e) {
                    e.preventDefault();
                    $('#missing-translations-modal').modal('show');
                    $('#missing-translations-modal form')[0].reset();
                });
        });
        </script>
        <?php else : ?>
        <script nonce="<?= $this->e($cspNonce) ?>">
        $(function () {
            $('#missing-translations').removeClass('olive').addClass('gray')
                .click(function (e) {
                    e.preventDefault();
                    $('#missing-translations-modal').modal('show');
                    $('#missing-translations-modal form')[0].reset();
                });
        });
        </script>
        <?php endif ?>
    <?php endif ?>
<?php endif ?>