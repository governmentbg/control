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
<?php
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'files_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
$disabled = $field->hasAttr('disabled') || $field->hasAttr('readonly');
$files = [];
$val = [];
if ($field->getAttr('value')) {
    foreach (array_filter(explode(',', $field->getValue(''))) as $v) {
        try {
            $temp = $app->file()->get($v);
            $files[] = [
                'id'       => $temp->id(),
                'hash'     => $temp->hash(),
                'thumb'    => $url('upload/' . $temp->id() . '/' . $temp->name(), [ 'w' => 128, 'h' => 128 ]),
                'url'      => $url('upload/' . $temp->id() . '/' . $temp->name()),
                'html'     => $temp->name(),
                'settings' => $temp->settings()
            ];
            $val[] = $temp->id();
        } catch (\Exception $ignore) {
        }
    }
}
$field->setValue(implode(',', $val));
$temp = $field->getOptions();
unset($temp['form']);
unset($temp['label']);
$config = array_merge([
    'images'    => true,
    'multiple'  => true,
    'url'       => $url('upload'),
    'settings'  => $field->hasOption('form') ? $field->getAttr('id') . '_form' : false,
    'edit'      => $field->hasOption('editor') ? $field->getAttr('id') . '_edit' : false,
    'chunksize' => '250kb',
    'value'     => count($files) ? $files : null,
    'disabled'  => isset($disabled) && $disabled,
    'browse'    => [ 'html' => $this->e($intl('fields.files.upload')) ]
], $temp);
?>
<input
    type="hidden"
    id="<?= $this->e($field->getAttr('id')) ?>"
    data-plupload='<?= json_encode($config); ?>'
    name="<?= $this->e($field->getAttr('name')); ?>"
    value="<?= $field->getValue('') ?>"
    <?= isset($disabled) && $disabled ? ' disabled="disabled" ' : '' ?>
    />
<input
    type="file"
    name="<?= $this->e($field->getAttr('name')); ?>"
    <?= isset($disabled) && $disabled ? ' disabled="disabled" ' : '' ?>
    />

<?php if ($field->hasOption('editor')) : ?>
<div class="ui modal" id="<?= $this->e($field->getAttr('id') . '_edit') ?>">
    <div class="ui form padded-form">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.form.wait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs="
                id="<?= $this->e($field->getAttr('id') . '_edit_image') ?>" />
        </div>
        <div class="ui section divider"></div>
        <div class="ui center aligned green secondary segment">
            <button class="ui green icon labeled submit button save-button">
                <i class="save icon"></i> <?= $this->e($intl('common.form.save')) ?>
            </button>
            <a class="ui basic button close-button" href="#"><?= $this->e($intl('common.form.cancel')) ?></a>
        </div>
    </div>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
#<?= $this->e($field->getAttr('id') . '_edit') ?> .darkroom-toolbar { position:static; margin-bottom:10px; }
#<?= $this->e($field->getAttr('id') . '_edit') ?> .darkroom-toolbar::before { display:none; }
#<?= $this->e($field->getAttr('id') . '_edit') ?> .canvas-container { margin:0 auto; }
#<?= $this->e($field->getAttr('id') . '_edit') ?> .canvas-container { margin:0 auto; }
#<?= $this->e($field->getAttr('id') . '_edit') ?> .darkroom-image-container {
    border-radius:3px; background:#333; padding:10px;
}
#<?= $this->e($field->getAttr('id') . '_edit') ?> img { max-width:100%; display:none; }
</style>
<?php endif ?>

<?php if ($field->hasOption('form')) : ?>
<div class="ui modal" id="<?= $this->e($field->getAttr('id') . '_form') ?>">
    <i class="close icon"></i>
    <div class="ui form padded-form">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.form.wait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <h3 class="dividing header"><?= $this->e($intl('common.fields.file.settings')) ?></h3>
        <?= $this->insert('common/form', [ 'form' => $field->getOption('form') ]) ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned green secondary segment">
            <button class="ui green icon labeled submit button save-button">
                <i class="save icon"></i> <?= $this->e($intl('common.form.save')) ?>
            </button>
            <a class="ui basic button close-button" href="#"><?= $this->e($intl('common.form.cancel')) ?></a>
        </div>
    </div>
</div>
<?php endif ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$('#<?= $this->e($field->getAttr("id")) ?>').plupload();
</script>
