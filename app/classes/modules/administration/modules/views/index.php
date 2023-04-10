<?php $this->layout('main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated header permissions-header">
        <i class="puzzle icon"></i>
        <span class="content"><?= $this->e($intl('modules.title')) ?></span>
    </h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form validate-form" id="modules-form" method="post">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <br />
        <?= $this->insert('common/form', [ 'form' => $form ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
        </div>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
#modules-form .checkbox { margin-top:10px; }
</style>