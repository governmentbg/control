<?php

if (isset($view)) {
    echo $this->insert(
        $view,
        compact(
            'options',
            'value',
            'values',
            'name',
            'validate',
            'disabled',
            'label',
            'readonly',
            'prefix',
            'suffix',
            'id'
        )
    );
}
