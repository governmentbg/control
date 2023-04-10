<?php if (!$field->getOption('textOnly')) : ?>
<input
    type="hidden"
    <?= $this->insert('common/field/attrs', [ 'attrs' => $field->getAttrs(), 'skip' => [], 'translate' => [] ]) ?>
    />
<?php endif ?>
