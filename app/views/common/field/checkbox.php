<?php
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'checkbox_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
if (!$field->hasAttr('value')) {
    $field->setValue('0');
}
?>
<?php if (!$field->getOption('nobr')) : ?>
<br />
<?php endif ?>
<?php if ($field->getOption('textOnly')) : ?>
    <label>
        <?= $field->getValue() ?
            '<i class="ui icon check square outline"></i>' :
            '<i class="ui icon square outline"></i>'
        ?> 
        <?= $this->e($intl($field->getOption('label', ''))) ?>
    </label>
<?php else : ?>
    <div class="ui checkbox">
        <input
            type="hidden"
            value="<?= $field->getValue() ? 1 : 0 ?>"
            <?=
                $this->insert(
                    'common/field/attrs',
                    [
                        'attrs' => $field->getAttrs(),
                        'skip' => ['id', 'data-validate', 'value'],
                        'translate' => ['placeholder', 'title']
                    ]
                )
            ?>
        ><input
            id="<?= $this->e($field->getAttr('id')) ?>"
            type="checkbox"
            <?php
            if ($field->getValue()) {
                echo ' checked="checked" ';
            }
            if ($field->hasAttr('disabled') || $field->hasAttr('readonly')) {
                echo ' disabled="disabled" ';
            }
            if ($field->hasAttr('data-redraw')) {
                echo ' data-redraw="' . $this->e($field->hasAttr('data-redraw')) . '" ';
            }
            ?>
        ><label for="<?= $this->e($field->getAttr('id')) ?>">
            <?php if ($field->getOption('tooltip')) : ?>
                <span 
                    data-tooltip="<?= $this->e($intl($field->getOption('tooltip'))) ?>"
                    data-inverted="">
                    <i class="question circle icon"></i>
                </span>
            <?php endif ?>
            <?= $this->e($intl($field->getOption('label', ''))) ?>
        </label>
    </div>
    <?php if (!$field->getOption('noJS')) : ?>
    <script nonce="<?= $this->e($cspNonce) ?>">
    $("#<?= $this->e($field->getAttr('id')) ?>").on('change', function () {
        this.previousSibling.value = this.checked ? 1 : 0;
    });
    $("#<?= $this->e($field->getAttr('id')) ?>").checkbox();
    </script>
    <?php endif ?>
<?php endif ?>