<?php $this->layout('main'); ?>

<?php $this->start('title') ?>
<div class="ui clearing basic segment title-segment">
    <h3 class="ui left floated header translation-header">
        <i class="language icon"></i>
        <span class="content"><?= $this->e($intl('translation.title')) ?></span>
    </h3>
</div>
<?php $this->stop() ?>

<div class="ui segment">
    <form class="ui form validate-form" method="post" id="translation">
        <div class="ui inverted dimmer">
            <div class="content">
                <div class="center">
                    <div class="ui text loader dimmer-message dimmer-message-load">
                        <?= $this->e($intl('common.pleasewait')) ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- <p><?= $this->e($intl('translation.description')) ?></p> -->
        <div class="ui pointing secondary menu">
            <a href="?all=0" class="item <?= $all ? '' : 'active' ?>"><?= $this->e($intl('translations.missing')) ?></a>
            <a href="?all=1" class="item <?= $all ? 'active' : '' ?>"><?= $this->e($intl('translations.all')) ?></a>
        </div>
        <?php
        $last = null;
        if (!count($data) && !$all) {
            echo '<div class="ui info message block-message">' .
                $this->e($intl('translations.nomissing')) .
                '</div>';
        }
        foreach ($data as $k => $v) {
            $word = explode('.', $k)[0];
            if ($last !== $word) {
                echo '<h4 class="ui dividing header">' . $this->e($word) . '</h4>';
                $last = $word;
            }
            echo '<div class="two fields">';
            echo '<div class="ui field">';
            echo '<div class="ui input">';
            echo '<input name="keys[]" readonly value="' . $this->e($k) . '" />';
            echo '</div>';
            echo '</div>';
            echo '<div class="ui field">';
            echo '<div class="ui input">';
            echo '<input name="values[]" value="' . $this->e($v) . '" />';
            echo '</div>';
            echo '</div>';
            if ($all) {
                echo '<button class="ui red icon button remove-button"><i class="delete icon"></i></button>';
            }
            echo '</div>';
        }
        if ($all) {
            echo '<h4 class="ui dividing header">' . $this->e($intl('translation.new')) . '</h4>';
            echo '<div id="translation_new"></div>';
            echo '<div class="translation-center">';
            echo '<button id="translation_add" class="ui green labeled icon button">';
            echo '<i class="plus icon"></i>' . $this->e($intl('translation.add'));
            echo '</button></div>';
        }
        ?>
        <div class="ui section divider"></div>
        <div class="ui center aligned orange secondary segment">
            <button class="ui orange icon labeled submit button">
                <i class="save icon"></i> <?= $this->e($intl('common.save')) ?>
            </button>
        </div>
    </form>
</div>
<style nonce="<?= $this->e($cspNonce) ?>">
.block-message { display:block;}
.translation-header { padding:0.5rem !important; }
.translation-center { text-align:center; }
</style>

<script nonce="<?= $this->e($cspNonce) ?>">
$('#translation').on('click', '.remove-button', function (e) {
    e.preventDefault();
    var h = $(this).parent().prevAll('h4').eq(0);
    $(this).parent().remove();
    if (h.next().is('h4')) {
        h.remove();
    }
});
$('#translation_add').click(function (e) {
    e.preventDefault();
    $('#translation_new').append(
        '<div class="two fields">'+
        '<div class="ui required field" '+
        ' data-validate=\'{"required":"<?= $this->e($intl('translation.required')) ?>"}\'>'+
        '<div class="ui input"><input name="keys[]" value="" /></div>'+
        '</div>'+
        '<div class="ui field"><div class="ui input"><input name="values[]" value="" /></div></div>'+
        '<button class="ui red icon button remove-button"><i class="delete icon"></i></button>'+
        '</div>'
    );
});
</script>