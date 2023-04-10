<iframe
    <?=
        $this->insert(
            'common/field/attrs',
            [
                'attrs' => $field->getAttrs(),
                'skip' => ['data-validate', 'type']
            ]
        )
    ?>
></iframe>