<?php $this->layout('main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
<h3 class="ui left floated header">
    <i class="<?= $this->e($modules[$url->getSegment(0)]['icon']) ?> icon"></i>
    <span class="content"><?= $this->e($intl($url->getSegment(0, 'dashboard') . '.title')) ?></span>
</h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <table class="ui table">
        <thead>
            <th>РИК</th>
            <th>Брой секции с видеоизлъчване</th>
            <th>Брой секции с тестово излъчване</th>
            <th>Брой секции с реално излъчване</th>
            <th>Брой секции с живо излъчване</th>
        </thead>
        <tbody>
            <?php foreach ($data as $row) : ?>
                <tr>
                    <?php foreach ($row as $value) : ?>
                        <td><?= $this->e($value); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>