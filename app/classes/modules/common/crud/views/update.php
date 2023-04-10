<?php
$mainField = array_values($form->getFields())[0] ?? null;
$this->layout(
    "main",
    [
        'breadcrumb' => '<i class="ui ' . $this->e($icon ?? 'pencil') . ' icon"></i> ' .
            $this->e($intl([$breadcrumb, 'crud.breadcrumb.update'])) .
            '<i class="right angle icon divider"></i> ' .
            $this->e($mainField && $mainField->getType() == 'text' ? $mainField->getValue() : implode('_', $pkey))
    ]
)
?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<a class="ui basic right floated button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.back')) ?></a>
<h3 class="ui left floated header">
    <i class="<?= $this->e($icon ?? 'pencil') ?> icon"></i>
    <span class="content"><?= $this->e($intl([$title, 'crud.titles.update'])) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form validate-form main-form" method="post"
        data-redraw="<?= $this->e($url($url->getSegment(0) . '/redraw/' . implode('|', $pkey))) ?>">
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
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
            <a class="ui basic button" href="<?= $this->e($back) ?>"><?= $this->e($intl('common.cancel')) ?></a>
        </div>
    </form>
</div>
