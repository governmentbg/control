<?php foreach ($actions as $button) : ?>
    <a
        href="<?= $this->e($url($button->getAttr('href'))) ?>"
        class="ui <?= $this->e($button->getClass()) ?>"
    >
        <i class="<?= $this->e($button->getIcon()) ?> icon"></i>
        <?php if ($button->getLabel()) : ?>
            <?= $this->e($intl($button->getLabel())) ?>
        <?php endif ?>
    </a>
<?php endforeach; ?>