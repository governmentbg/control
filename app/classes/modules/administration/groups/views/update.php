<?php
require_once __DIR__ . '/../../../common/crud/views/update.php';
?>
<?php if ($pkey['grp'] == $config('GROUP_ADMINS')) : ?>
<style nonce="<?= $this->e($cspNonce) ?>">
.superadmin-column { display:block; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$('[name="name"]')
    .closest('.row')
        .nextAll().hide().end()
    .after(
        '<div class="ui one column row">'+
            '<div class="column">'+
                '<div class="ui info message column superadmin-column"><?= $intl("superadmin.rights") ?></div>'+
            '</div>'+
        '</div>'
    );
</script>
<?php endif ?>