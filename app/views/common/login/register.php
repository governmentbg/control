<?php $this->layout('common/login/master'); ?>

<h4 class="ui large teal header"><i class="lock icon"></i> <?= $this->e($intl($config('APPNAME'))) ?></h4>
<div class="ui teal segment">
    <form class="ui form" method="post">
        <?php if ($error) : ?>
        <div class="ui negative message"><div class="header"><?= $this->e($intl($error)) ?></div></div>
        <?php endif; ?>
        <?php if ($sent) : ?>
        <div class="ui positive message">
            <div class="header"><?= $this->e($intl('common.login.register_sent')) ?></div>
        </div>
        <?php else : ?>
            <?php if ($change) : ?>
                <div class="field">
                    <label><?= $this->e($intl('common.login.newpassword')) ?></label>
                    <input type="password" autocomplete="new-password" name="password1" />
                </div>
                <div class="field">
                    <label><?= $this->e($intl('common.login.repeatpassword')) ?></label>
                    <input type="password" autocomplete="new-password" name="password2" />
                </div>
                <div class="ui divider"></div>
                <button type="submit" class="ui labeled icon teal submit button login-submit-button">
                    <i class="sign in icon"></i>
                    <?= $this->e($intl('common.login.login')) ?>
                </button>
            <?php else : ?>
                <div class="ui info message">
                    <div class="header"><?= $this->e($intl('common.login.register_text')) ?></div>
                </div>
                <div class="field">
                    <div class="ui left icon input">
                        <i class="mail icon"></i>
                        <input type="text" name="mail" autofocus autocomplete="email"
                            placeholder="<?= $this->e($intl('common.login.register_mail')) ?>" />
                    </div>
                </div>
                <div class="field">
                    <div class="ui left icon input">
                        <i class="user icon"></i>
                        <input type="text" name="name" autocomplete="name"
                            placeholder="<?= $this->e($intl('common.login.register_name')) ?>" />
                    </div>
                </div>
                <div class="ui divider"></div>
                <button type="submit" class="ui labeled icon teal submit button login-submit-button">
                    <i class="sign in icon"></i>
                    <?= $this->e($intl('common.login.register_send')) ?>
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
body > .grid .segment { padding-bottom:1.4rem !important; }
</style>