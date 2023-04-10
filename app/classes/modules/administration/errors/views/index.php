<?php $this->layout('main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<h3 class="ui left floated header">
    <i class="<?= $this->e($modules[$url->getSegment(0)]['icon']) ?> icon"></i>
    <span class="content"><?= $this->e($intl($url->getSegment(0, 'dashboard') . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui center aligned segment errors-container">
    <form method="get" class="ui form">
        <div class="inline one field">
            <div class="field">
                <?= $this->insert(
                    'common/field/date',
                    [
                        'field' => new \helpers\html\Field(
                            'date',
                            [ 'name' => 'date', 'value' => $date ],
                            [ 'label' => 'errors.fields.date', 'maxDate' => 'now' ]
                        )
                    ]
                ) ?>
            </div>
        </div>
    </form>
</div>

<div class="ui segment">
    <?php if (!count($errors)) : ?>
    <div class="ui message"><?= $this->e($intl('common.table.norecords')) ?></div>
    <?php else : ?>
    <table class="ui basic compact table">
        <thead>
            <tr>
                <th><?= $this->e($intl('errors.error')) ?></th>
                <th><?= $this->e($intl('errors.times')) ?></th>
                <th><?= $this->e($intl('errors.date')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($errors as $error) : ?>
            <tr class="<?= $this->e($error['level']) ?>">
                <td>
                    <?= $this->e($error['text']) ?><br />
                    <small><?= $error['file'] ? $this->e($error['file'] . ' : ' . $error['line']) : '&nbsp;' ?></small>
                </td>
                <td><?= $this->e($error['count']) ?></td>
                <td><?= $this->e(date('d.m.Y H:i:s', $error['time'])) ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <?php endif ?>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.top-container { margin-top:0; }
.top-container .one.field { margin-bottom:0; text-align:center; }
input[name="date"] { text-align:center !important; }
</style>
<script nonce="<?= $this->e($cspNonce) ?>">
$(function () {
    $('[name=date]').change(function (e) { window.location = '?date=' + this.value; });
});
</script>