<?php

$this->layout('common/master'); ?>

<div class="ui center aligned grid">
    <div class="column">
        <?= $this->section('content'); ?>
    </div>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
body > .grid > .column { max-width:450px; margin-top:4rem; } 
body > .grid > .column .segment { padding:2rem; }
body > .grid > .column .header { text-shadow:1px 1px 0px rgba(255,255,255,0.75); }
body { background: #cdeb8e; background: linear-gradient(to bottom, #cdeb8e 0%,#9dd34c 100%); }
</style>