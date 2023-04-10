<?php if (strlen($field->getOption('label', ''))) : ?>
    <label>
        <?php if ($field->getOption('tooltip')) : ?>
            <span 
                data-tooltip="<?= $this->e($intl($field->getOption('tooltip'))) ?>"
                data-inverted="">
                <i class="question circle icon"></i>
            </span>
        <?php endif ?>
        <?= $this->e($intl($field->getOption('label'))) ?>
    </label>
<?php endif ?>
<?php if ($field->getOption('textOnly')) : ?>
    <div><?= $this->e($field->getValue('')) ?></div>
<?php else : ?>
<div class="ui 
    <?php
    if ($field->hasAttr('disabled')) {
        echo 'disabled ';
    }
    if ($field->hasOption('prefix') || $field->hasOption('suffix')) {
        echo 'right labeled ';
    }
    ?> 
    input"
>
    <?php if ($field->hasOption('prefix')) : ?>
        <div class="ui label"><?= $this->e($intl($field->getOption('prefix'))) ?></div>
    <?php endif ?>
    <input
        <?=
            $this->insert(
                'common/field/attrs',
                [
                    'attrs' => $field->getAttrs(),
                    'skip' => ['data-validate'],
                    'translate' => ['placeholder', 'title']
                ]
            )
        ?>
        />
    <?php if ($field->hasOption('suffix')) : ?>
        <div class="ui label"><?= $this->e($intl($field->getOption('suffix'))) ?></div>
    <?php endif ?>
</div>
<?php endif ?>
