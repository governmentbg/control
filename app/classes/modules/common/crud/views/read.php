<?php
$mainField = array_values($form->getFields())[0] ?? null;
$this->layout(
    "main",
    [
        'breadcrumb' => '<i class="ui ' . $this->e($icon ?? 'eye') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.read'])) .
            '<i class="right angle icon divider"></i> ' .
            $this->e($mainField && $mainField->getType() == 'text' ? $mainField->getValue() : implode('_', $pkey))
    ]
)
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated header">
    <i class="<?= $this->e($icon ?? 'eye') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.read'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form read-form main-form">
        <?= $this->insert('common/form', [ 'form' => $form ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned blue secondary segment">
            <a href="<?= $this->e($back) ?>" class="ui blue icon labeled submit button">
                <i class="left arrow icon"></i> <?= $this->e($intl('common.back')) ?>
            </a>
            <?php if ($update) : ?>
            <a href="<?= $this->e($url($url->getSegment(0) . '/update/' . $url->getSegment(2))) ?>"
                class="ui orange icon labeled button">
                <i class="pencil icon"></i> <?= $this->e($intl('crud.actions.update')) ?>
            </a>
            <?php endif ?>
            <?php if ($delete) : ?>
            <a href="<?= $this->e($url($url->getSegment(0) . '/delete/' . $url->getSegment(2))) ?>"
                class="ui red icon labeled button">
                <i class="trash icon"></i> <?= $this->e($intl('crud.actions.delete')) ?>
            </a>
            <?php endif ?>
            <?php if ($history) : ?>
            <a href="<?= $this->e($url($url->getSegment(0) . '/history/' . $url->getSegment(2))) ?>"
                class="ui grey icon labeled button">
                <i class="clock icon"></i> <?= $this->e($intl('crud.actions.history')) ?>
            </a>
            <?php endif ?>
        </div>
    </form>
</div>
<script nonce="<?= $this->e($cspNonce) ?>">
$('.main-form').on('submit', function (e) { e.preventDefault(); })
</script>
