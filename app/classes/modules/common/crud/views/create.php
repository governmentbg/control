<?php
$this->layout(
    "main",
    [
        'breadcrumb' => '<i class="ui ' . $this->e($icon ?? 'plus') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.create']))
    ]
)
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated header">
    <i class="<?= $this->e($icon ?? 'plus') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.create'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form validate-form main-form" method="post"
        data-redraw="<?= $this->e($url($url->getSegment(0) . '/redraw')) ?>">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <?= $this->insert('common/form', [ 'form' => $form ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned green secondary segment">
            <button class="ui green icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.create')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
</div>
<script nonce="<?= $this->e($cspNonce) ?>">
if (window.parent && window.parent !== window.self) {
    var selectedPromise = {
        cbks : [],
        then : function (cb) { this.cbks.push(cb); },
        when : function (value) {}
    };
    $('body').addClass('no-menu');
    $('.main-form').append('<input type="hidden" value="1" value="redirect_to_id" />');
}
</script>
<style nonce="<?= $this->e($cspNonce) ?>">
.no-menu { background:white !important; }
.no-menu .menu-top,
.no-menu .menu-side { display:none !important; }
.no-menu .content { padding:0 !important; }
.no-menu .content > .segment { box-shadow:none !important; border:0 !important; }
</style>
