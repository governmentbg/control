<?php
require_once __DIR__ . '/../../../common/crud/views/index.php';
?>
<div class="ui modal video-modal">
    <div class="header">Секция № <span id="section-number"></span></div>
    <div class="content">
        <video class="video-js" controls preload="auto" width="1280" height="720" autoplay="false" data-setup='{"aspectRatio":"16:9"}'>
            <p class="vjs-no-js">Моля, използвайте съвременен браузър с видео поддържка</p>
        </video>
    </div>
</div>
<script nonce="<?= $this->e($cspNonce) ?>">
    var player = videojs(document.querySelector('video'));
    $('.video-play').on('click', function () {
        var sik = $(this).closest('tr').find('[data-column="num"]').html().trim();
        $('#section-number').html(sik);
        $.ajax({
            method: "GET",
            url: "<?= $this->e($url('siks/stream')); ?>",
            data: { sik: $(this).closest('tr').data('id') }
        })
        .done(function (data) {
            if (!data.url) {
                $('.video-modal')
                    .find('.content')
                    .html('<h1>В момента няма живо излъчване от тази секция</h1>');
                $('.video-modal').modal('show');
            } else {
                player.dispose();
                $('.video-modal')
                    .find('.content')
                    .html(`<video class="video-js" controls preload="auto" width="1280" height="720" autoplay="false" data-setup='{"aspectRatio":"16:9"}'>
                        <p class="vjs-no-js">Моля, използвайте съвременен браузър с видео поддържка</p>
                    </video>`);
                player = videojs(document.querySelector('video'));
                player.src({
                    src: data.url,
                    type: "application/x-mpegURL"
                });
                player.play();
            }
            $('.video-modal').modal('show');
        })
        .fail(function () {
            alert("Неуспешно извличане на адрес на стрийм");
            window.location.reload();
        });
    });
</script>