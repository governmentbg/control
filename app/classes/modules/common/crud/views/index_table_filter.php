<form class="ui form" method="get">
    <?php foreach ($data as $k => $v) : ?>
        <?php
        if (in_array($k, $fields)) {
            continue;
        }
        ?>
        <?php if (is_array($v)) : ?>
            <?php foreach ($v as $kk => $vv) : ?>
                <input type="hidden" name="<?= $k ?>[<?= !is_numeric($kk) ? $kk : '' ?>]"
                    value="<?= $this->e($vv) ?>" />
            <?php endforeach ?>
        <?php else : ?>
            <input type="hidden" name="<?= $k ?>" value="<?= $this->e($k === 'p' ? 1 : $v) ?>" />
        <?php endif ?>
    <?php endforeach ?>

    <?= $this->insert('common/form', [ 'form' => $form ]) ?>

    <div class="ui center aligned green secondary segment">
        <button class="ui tiny green submit button"><?= $this->e($intl('common.filter.filter')) ?></button>
        <?php if ($clear) : ?>
            <?php
            $temp = $data;
            foreach ($fields as $name) {
                unset($temp[$name]);
            }
            $temp['p'] = 1;
            ?>
            <a class="ui tiny basic button" href="?<?= http_build_query($temp) ?>">
                <?= $this->e($intl('common.filter.clear')) ?>
            </a>
        <?php endif ?>
    </div>

</form>
