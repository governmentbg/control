<?php

$this->layout('common/login/master'); ?>

<h4 class="ui large green header"><i class="lock icon"></i> <?= $this->e($intl($config('APPNAME'))) ?></h4>
<div class="ui green segment">
    <form class="ui form" method="post">
        <div class="ui negative message">
            <p><?= $this->e($intl('common.login.basic')) ?></p>
        </div>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
body > .grid .message { margin-top:0 !important; }
</style>