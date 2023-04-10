<tr class="<?= $this->e($row->getClass()) ?>" data-id='<?= $this->e($row->getAttr('id', '')) ?>'>
    <?php foreach ($columns as $column) : ?>
        <td class="<?= $this->e($column->getClass()) ?>" data-column="<?= $this->e($column->getName()) ?>">
        <?php
        $temp = explode('.', $column->getName());
        $value = $row->getData();
        foreach ($temp as $part) {
            if (
                $value === null ||
                !is_object($value) ||
                (!property_exists($value, $part) && !method_exists($value, '__get'))
            ) {
                $value = '';
                break;
            }
            $value = $value->{$part};
        }
        if ($column->hasMap()) {
            $value = call_user_func($column->getMap(), $value, $row->getData());
        }
        echo $value instanceof \helpers\html\HTML ? (string)$value : $this->e((string)$value);
        ?>
        </td>
    <?php endforeach; ?>
    <td class="operations">
        <?php foreach ($row->getOperations() as $button) : ?>
            <a
                href="<?= $this->e($url($button->getAttr('href'))) ?>"
                class="ui <?= $this->e($button->getClass()) ?>"
                title="<?= $this->e($intl($button->getLabel())) ?>"
            >
                <i class="<?= $this->e($button->getIcon()) ?> icon"></i>
            </a>
        <?php endforeach; ?>
    </td>
</tr>