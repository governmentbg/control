<?php

$this->layout('common/master') ?>

<style nonce="<?= $this->e($cspNonce) ?>">
    body { background:#e0e0e0; min-width:320px; }
    h1 { font-size:1.4em; text-align:center; margin:2em 0 0 0; color:#8b0000; text-shadow:1px 1px 0 white; }
</style>

<h1><?= $this->e($intl('common.error.toomanyrequests')) ?></h1>
