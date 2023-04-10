<!DOCTYPE html>
<html lang="<?= $this->e($req->getAttribute('locale') ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $this->e(strip_tags($intl($config('APPNAME')))) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="icon" href="<?= $this->e($url('favicon.ico')) ?>" sizes="any">
    <link rel="icon" href="<?= $this->e($url('favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= $this->e($url('apple-touch-icon.png')) ?>">

    <link rel="stylesheet" href="<?= $asset('assets/static/fomantic-ui-css/semantic.min.css') ?>">
    <script src="<?= $asset('assets/static/jquery/jquery.min.js') ?>"></script>
    <script nonce="<?= $this->e($cspNonce) ?>">
    function styleToCSS(str) {
        if (!str) { return {}; }
        var tmp = {};
        str.split(';').forEach(function (v) {
            v = v.split(':');
            if (v.length == 2) {
                tmp[v[0].trim()] = v[1].replace("!important","").trim();
            }
        });
        return tmp;
    }
    </script>
    <script src="<?= $asset('assets/static/fomantic-ui-css/semantic.min.js') ?>"></script>
    <script nonce="<?= $this->e($cspNonce) ?>">$.fn.dropdown.settings.fullTextSearch = 'exact';</script>
    <script nonce="<?= $this->e($cspNonce) ?>">$.fn.dropdown.settings.delay.search = 150;</script>

    <?= $this->section('head'); ?>
</head>
<body>

    <?= $this->section('content'); ?>
    
    <script nonce="<?= $this->e($cspNonce) ?>">
    // ensure a reload once the session expires - meaning:
    // 1) inactive logged in users will be sent to the login screen
    // 2) the login form will be refreshed and a new CSRF token will be generated
    // may need a revisit if the system is migrated to AJAX requests
    setInterval(
        function () {
            $.get('<?= $this->e($url($config('LOGIN_URL'))) ?>')
                .done(function (data) {
                    if (!data.user) {
                        window.location.reload();
                    }
                })
                .fail(function () {
                    window.location.reload();
                });
        },
        JSON.parse('<?= (((int)$config('SESSION_TIMEOUT')) + 2) * 1000 ?>')
    );
    </script>
</body>
</html>