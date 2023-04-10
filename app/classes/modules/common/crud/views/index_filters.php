<div class="filters-column"></div>
<form class="filters-form">
    <?php foreach ($params as $k => $v) : ?>
        <?php
        if (in_array($k, ['q','p'])) {
            continue;
        }
        ?>
        <?php if (is_array($v)) : ?>
            <?php foreach ($v as $kk => $vv) : ?>
                <?php
                if (is_array($vv)) {
                    continue;
                }
                ?> 
                <input type="hidden" name="<?= $this->e($k . '[' . $kk . ']') ?>" value="<?= $this->e($vv) ?>" />
            <?php endforeach ?>
        <?php else : ?>
            <input type="hidden" name="<?= $this->e($k) ?>" value="<?= $this->e($v) ?>" />
        <?php endif ?>
    <?php endforeach; ?>
    <input type="hidden" name="p" value="1" />
    <div class="ui action input">
        <input placeholder="<?= $this->e($intl('common.search')) ?> ..." type="text" name="q"
            value="<?= $this->e($params['q'] ?? '') ?>" />
        <?php if (isset($params['q']) && strlen($params['q'])) : ?>
        <a href="?<?= $this->e(http_build_query(array_merge($params, ['q' => '', 'p' => 1]))) ?>"
            class="ui icon button">
            <i class="remove icon"></i>
        </a>
        <?php endif; ?>
        <button class="ui blue icon button"><i class="transparent search icon"></i></button>
    </div>
</form>