<?php
if (!$field->hasAttr('id')) {
    $field->setAttr('id', 'video_list_' . md5($field->getName('') . microtime() . rand(0, 100)));
}
?>
<label><?= $this->e($intl($field->getOption('label', ''))); ?></label>
<ul class="ui list" id="<?= $this->e($field->getAttr('id')); ?>_list">
    <?php foreach ($field->getValue() as $id => $item) : ?>
        <li>
            <a href="<?= $this->e($item['url']); ?>"><?= $this->e($item['title']); ?></a>
        </li>
    <?php endforeach; ?>
</ul>
<div class="ui modal video-modal" id="<?= $this->e($field->getAttr('id')); ?>_modal">
    <div class="header">Запис: <span></span></div>
    <div class="content">
        <video id="<?= $this->e($field->getAttr('id')); ?>_video" class="video-js" controls preload="auto" width="1280" height="720" autoplay="false" data-setup='{"aspectRatio":"16:9"}'>
            <p class="vjs-no-js">Моля, използвайте съвременен браузър с видео поддържка</p>
        </video>
    </div>
</div>
<script nonce="<?= $this->e($cspNonce); ?>">
    var player = videojs(document.getElementById("<?= $this->e($field->getAttr('id')); ?>_video"));
    $('#<?= $this->e($field->getAttr('id')); ?>_list > li > a').on('click', function (e) {
        e.preventDefault();
        player.dispose();
        $('#<?= $this->e($field->getAttr('id')); ?>_modal')
            .find('.content')
            .html(`<video id="<?= $this->e($field->getAttr('id')); ?>_video" class="video-js" controls preload="auto" width="1280" height="720" autoplay="false" data-setup='{"aspectRatio":"16:9"}'>
                <p class="vjs-no-js">Моля, използвайте съвременен браузър с видео поддържка</p>
            </video>`)
            .end()
            .find('.header > span')
                .html($(this).html());
        player = videojs(document.getElementById("<?= $this->e($field->getAttr('id')); ?>_video"));
        player.src({
            src: $(this).attr('href')
        });
        player.play();
        $('.video-modal').modal('show');
    });
</script>