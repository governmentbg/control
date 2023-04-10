<?php

if ($field->hasAttr('value')) {
    if (
        $field->getValue() === '0000-00-00' ||
        $field->getValue() === '0000-00-00 00:00:00' ||
        strtotime($field->getValue()) === false
    ) {
        $field->setValue('');
    } else {
        $field->setValue(date('Y-m-d', strtotime($field->getValue())));
    }
}
$field->setType('date');
include __DIR__ . '/text.php';
