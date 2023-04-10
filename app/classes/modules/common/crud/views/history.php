<?php
$this->layout(
    "main",
    [
        'breadcrumb' => '<i class="ui ' . $this->e($icon ?? 'clock') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.history'])) .
            '<i class="right angle icon divider"></i> ' .
            implode('_', $pkey)
    ]
);
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated header">
    <i class="<?= $this->e($icon ?? 'clock') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.history'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div>
    <?php foreach ($versions as $k => $d) : ?>
        <a href="#" class="ui basic <?= $k ? 'orange' : 'green' ?> right pointing label history-label">
            <div class="detail">
                <i class="ui user icon"></i>
                <?= $this->e($d['author']) . '<br /><i class="ui clock icon"></i>' . $this->e($d['created']) ?>
            </div>
        </a>
    <?php endforeach ?>
</div>

<div class="ui segment">
    <?php if (!count($versions)) : ?>
    <div class="ui warning message"><?= $this->e($intl('crud.history.noversions')) ?></div>
    <?php endif ?>

    <?php foreach ($versions as $version) : ?>
    <form class="ui form history-item">
        <?= $this->insert('common/form', [ 'form' => $version['form'] ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned grey secondary segment">
            <a href="<?= $this->e($back) ?>" class="ui blue icon labeled submit button">
                <i class="left arrow icon"></i> <?= $this->e($intl('common.back')) ?>
            </a>
        </div>
    </form>
    <?php endforeach ?>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.history-label { margin-top:10px; }
.history-label > div { line-height:1.4rem; margin-left:0; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.history-label')
    .click(function (e) {
        e.preventDefault();
        $('.history-item').hide().eq($(this).index()).show();
        $(this).siblings().addClass('basic').end().removeClass('basic');
    })
    .eq(-1).removeClass('right pointing orange').addClass('blue').click();
var last = null;
$('.history-item').on('submit', function (e) { e.preventDefault(); });
$('.history-item').each(function () {
    var curr = $(this).find(':input');
    if (last) {
        curr.each(function (i) {
            if (JSON.stringify($(this).val()) !== JSON.stringify(last.eq(i).val())) {
                $(this).closest('.field').addClass('ui positive message').css({ 'padding': '10px' });
            } else {
                $(this).closest('.field').css('opacity', '0.75');
            }
        });
    }
    last = curr;
});
</script>
