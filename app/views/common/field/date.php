<?php
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'date_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
if ($field->hasAttr('value')) {
    if (
        $field->getValue() === '0000-00-00' ||
        $field->getValue() === '0000-00-00 00:00:00' ||
        strtotime($field->getValue()) === false
    ) {
        $field->setValue('');
    } else {
        $field->setValue(date('d.m.Y', strtotime($field->getValue())));
    }
}
$options = $field->getOptions();
$options['mode'] = 'date';
if ($field->getOption('style') === 'inline') {
    $field->setType('hidden');
    include __DIR__ . '/hidden.php';
} else {
    $field
        ->setType('text')
        ->setAttr('autocomplete', 'off');
    include __DIR__ . '/text.php';
}
?>
<?php
if (
    !$field->getOption('textOnly') &&
    !$field->hasAttr('disabled') &&
    !$field->hasAttr('readonly') &&
    !$field->getOption('noJS')
) :
    ?>
<script nonce="<?= $this->e($cspNonce) ?>">
$("#<?= $this->e($field->getAttr('id')) ?>").dtpckr(
    JSON.parse(
        '<?=json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT)?>'
    )
);
</script>
<?php endif ?>
