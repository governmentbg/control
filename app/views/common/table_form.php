<?php

$keys = [
    1 => 'one','two','three','four','five','six','seven','eight','nine','ten',
    'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen'
];
if ($form->hasLayout()) {
    $layout = $form->getLayout();
} else {
    $layout = [];
    foreach ($form->getFields() as $field) {
        $layout[] = [ $field->getName() ];
    }
}
$usedFields = [];

$widths = [];
foreach (array_values($layout)[0] as $item) {
    $temp = array_pad(explode(':', $item, 2), 2, 1);
    if (!(int)$temp[1]) {
        $temp[1] = 1;
    }
    $widths[] = $temp[1];
}
$total = array_sum($widths);
foreach ($widths as $k => $v) {
    $widths[$k] = sprintf('%01.2f', $v / $total * 100);
}

echo '<table class="ui striped table">';
foreach ($layout as $row) {
    if (is_string($row)) {
        echo '<tr><th colspan="' . count($widths) . '"><h4 class="ui header">';
        echo $this->e($row !== '' ? $intl($row) : '');
        echo '</h4></th></tr>';
        continue;
    }
    $fields = [];
    foreach ($row as $k => $field) {
        $temp = array_pad(explode(':', $field, 2), 2, 1);
        if ($form->hasField($temp[0])) {
            $fields[] = [ 'type' => 'field', 'width' => $widths[$k] ?? null, 'field' => $form->getField($temp[0]) ];
        } else {
            $fields[] = [ 'type' => 'text',  'width' => $widths[$k] ?? null, 'text'  => $temp[0] ];
        }
    }
    echo '<tr>';
    $bn = 'col-' . md5($microtime() . rand(0, 100)) . '-';
    $ws = [];
    foreach ($fields as $k => $field) {
        if ($field['width']) {
            $ws[$bn . $k] = $field['width'];
        }
        switch ($field['type']) {
            case 'text':
                echo '<td class="' . $this->e($bn . $k) . '">';
                echo $this->e($field['text']);
                echo '</td>';
                break;
            case 'field':
                $f = $field['field'];
                echo '<td class="' . $this->e($bn . $k) . '">';
                echo '<div class="';
                if ($f->hasAttr('disabled')) {
                    echo 'disabled ';
                }
                if (
                    $f->hasAttr('data-validate') &&
                    isset($f->getAttr('data-validate')['required']) &&
                    !$f->hasAttr('disabled') &&
                    !$f->hasAttr('readonly')
                ) {
                    echo 'required ';
                }
                echo ' field" ';
                if ($f->hasAttr('data-validate')) {
                    echo ' data-validate=\'';
                    echo json_encode(
                        $f->getAttr('data-validate'),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT
                    );
                    echo '\' ';
                }
                echo '>';
                echo $this->insert(
                    'common/field/' . $f->getType(),
                    [ 'field' => $f ]
                );
                echo '</div>';
                echo '</td>';
                $usedFields[] = $f;
                break;
        }
    }
    echo '</tr>';
}
if (count($usedFields) !== count($form->getFields())) {
    echo '<tbody class="hide"><tr><td colspan="' . count($widths) . '">';
    foreach ($form->getFields() as $field) {
        if (!in_array($field, $usedFields)) {
            echo $this->insert(
                'common/field/' . $field->getType(),
                [ 'field' => $field ]
            );
        }
    }
    echo '</td></tr></tbody>';
}
echo '</table>';

echo '<style nonce="' . $this->e($cspNonce) . '">' . "\n";
foreach ($ws as $kkk => $vvv) {
    echo '.' . $kkk . ' { width:' . $vvv . '%; }' . "\n";
}
echo '</style>' . "\n";
