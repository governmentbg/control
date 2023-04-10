<!DOCTYPE html>
<html lang="bg">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="icon" href="./favicon.ico" sizes="any">
        <link rel="icon" href="./favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="./apple-touch-icon.png">
        <title>РИК <?= htmlspecialchars($num) ?> <?= htmlspecialchars($name) ?> :: Видео излъчване</title>
        <link href="./css/video-js.css" rel="stylesheet" />
        <link href="./css/videojs-playlist-ui.vertical.css" rel="stylesheet" />
        <link href="./css/main.css" rel="stylesheet" />
        <meta property="og:title" content="Видео излъчване от СИК" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://evideo.bg/rik<?= htmlspecialchars($num) ?>.html" />
        <meta property="og:image" content="https://evideo.bg/images/evideo-og-image.png" />
        <script>
            var _paq = window._paq = window._paq || [];
            /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
            _paq.push(["setDoNotTrack", true]);
            _paq.push(['trackPageView']);
            _paq.push(['enableLinkTracking']);
            (function() {
              var u="//track.uslugi.io/";
              _paq.push(['setTrackerUrl', u+'matomo.php']);
              _paq.push(['setSiteId', '25']);
              var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
              g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
            })();
        </script>
        <style>
            .vjs-playlist { position:static; background:white; }
            .vjs-playlist .vjs-playlist-item { float:left; margin-bottom:0; margin-left:1px; }
            .vjs-playlist .vjs-playlist-title-container { position:static; }
            .vjs-playlist-now-playing-text { display:none !important; }
            .vjs-playlist-now-playing-text, .vjs-up-next-text { display:none !important; }
            .vjs-playlist-vertical .vjs-playlist-thumbnail-placeholder { height:auto; color:#1f444b; background:#98c5ce; }
            .vjs-playlist .vjs-playlist-name { padding-bottom:0; line-height:18px; }
            .vjs-playlist-now-playing, .vjs-selected > div { background:#1f444b !important; color:white !important; }
            .vjs-playlist .vjs-playlist-title-container { text-shadow:none; }

            .video-js .vjs-big-play-button,
            .video-js .vjs-big-play-button:hover {
                background:url('images/video-background-3.svg') left top no-repeat;
                background-size:cover;
            }
            .vjs-playlist .vjs-playlist-item { min-width:70px; margin-top:1px; }
            .modal { overflow-y:auto; }
        </style>
    </head>
    <body>
        <div class="page-content">
            <aside class="page-aside">
                <header class="page-header">
                    <a href="./index.html" class="logo">
                        <span>Видео <br />излъчване <br />от СИК&nbsp;</span>
                        <span>Избори за народни<br />представители 2 април 2023</span>
                    </a>
                </header>
                <nav aria-label="основна навигация" class="main-nav menu">
                    <ul>
                        <li><a href="./rik01.html" <?= ($num == '01') ? 'class="active"' : '' ?>><span>РИК 01</span>Благоевград</a></li>
                        <li><a href="./rik02.html" <?= ($num == '02') ? 'class="active"' : '' ?>><span>РИК 02</span>Бургас</a></li>
                        <li><a href="./rik03.html" <?= ($num == '03') ? 'class="active"' : '' ?>><span>РИК 03</span>Варна</a></li>
                        <li><a href="./rik04.html" <?= ($num == '04') ? 'class="active"' : '' ?>><span>РИК 04</span>Велико Търново</a></li>
                        <li><a href="./rik05.html" <?= ($num == '05') ? 'class="active"' : '' ?>><span>РИК 05</span>Видин</a></li>
                        <li><a href="./rik06.html" <?= ($num == '06') ? 'class="active"' : '' ?>><span>РИК 06</span>Враца</a></li>
                        <li><a href="./rik07.html" <?= ($num == '07') ? 'class="active"' : '' ?>><span>РИК 07</span>Габрово</a></li>
                        <li><a href="./rik08.html" <?= ($num == '08') ? 'class="active"' : '' ?>><span>РИК 08</span>Добрич</a></li>
                        <li><a href="./rik09.html" <?= ($num == '09') ? 'class="active"' : '' ?>><span>РИК 09</span>Кърджали</a></li>
                        <li><a href="./rik10.html" <?= ($num == '10') ? 'class="active"' : '' ?>><span>РИК 10</span>Кюстендил</a></li>
                        <li><a href="./rik11.html" <?= ($num == '11') ? 'class="active"' : '' ?>><span>РИК 11</span>Ловеч</a></li>
                        <li><a href="./rik12.html" <?= ($num == '12') ? 'class="active"' : '' ?>><span>РИК 12</span>Монтана</a></li>
                        <li><a href="./rik13.html" <?= ($num == '13') ? 'class="active"' : '' ?>><span>РИК 13</span>Пазарджик</a></li>
                        <li><a href="./rik14.html" <?= ($num == '14') ? 'class="active"' : '' ?>><span>РИК 14</span>Перник</a></li>
                        <li><a href="./rik15.html" <?= ($num == '15') ? 'class="active"' : '' ?>><span>РИК 15</span>Плевен</a></li>
                        <li><a href="./rik16.html" <?= ($num == '16') ? 'class="active"' : '' ?>><span>РИК 16</span>Пловдив</a></li>
                        <li><a href="./rik17.html" <?= ($num == '17') ? 'class="active"' : '' ?>><span>РИК 17</span>Пловдив</a></li>
                        <li><a href="./rik18.html" <?= ($num == '18') ? 'class="active"' : '' ?>><span>РИК 18</span>Разград</a></li>
                        <li><a href="./rik19.html" <?= ($num == '19') ? 'class="active"' : '' ?>><span>РИК 19</span>Русе</a></li>
                        <li><a href="./rik20.html" <?= ($num == '20') ? 'class="active"' : '' ?>><span>РИК 20</span>Силистра</a></li>
                        <li><a href="./rik21.html" <?= ($num == '21') ? 'class="active"' : '' ?>><span>РИК 21</span>Сливен</a></li>
                        <li><a href="./rik22.html" <?= ($num == '22') ? 'class="active"' : '' ?>><span>РИК 22</span>Смолян</a></li>
                        <li><a href="./rik23.html" <?= ($num == '23') ? 'class="active"' : '' ?>><span>РИК 23</span>София</a></li>
                        <li><a href="./rik24.html" <?= ($num == '24') ? 'class="active"' : '' ?>><span>РИК 24</span>София</a></li>
                        <li><a href="./rik25.html" <?= ($num == '25') ? 'class="active"' : '' ?>><span>РИК 25</span>София</a></li>
                        <li><a href="./rik26.html" <?= ($num == '26') ? 'class="active"' : '' ?>><span>РИК 26</span>София Област</a></li>
                        <li><a href="./rik27.html" <?= ($num == '27') ? 'class="active"' : '' ?>><span>РИК 27</span>Стара Загора</a></li>
                        <li><a href="./rik28.html" <?= ($num == '28') ? 'class="active"' : '' ?>><span>РИК 28</span>Търговище</a></li>
                        <li><a href="./rik29.html" <?= ($num == '29') ? 'class="active"' : '' ?>><span>РИК 29</span>Хасково</a></li>
                        <li><a href="./rik30.html" <?= ($num == '30') ? 'class="active"' : '' ?>><span>РИК 30</span>Шумен</a></li>
                        <li><a href="./rik31.html" <?= ($num == '31') ? 'class="active"' : '' ?>><span>РИК 31</span>Ямбол</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="main">
                <div class="content">
                    <div class="section" style="display:block;">
                        <h1 class="section-title">РИК <?= htmlspecialchars($num) ?> <?= htmlspecialchars($name) ?></h1>
                        <div class="section-search">
                            <label for="search">Търсене по адрес или номер на СИК</label>
                            <input id="search" type="search" />
                        </div>

                        <table>
                            <tbody>
                                <?php $i = 0; ?>
                                <?php foreach ($siks as $sik => $data) : ?>
<tr data-sik="<?= htmlspecialchars($data['num']) ?>" data-vid='<?= htmlspecialchars(json_encode($data['video']), ENT_NOQUOTES | ENT_HTML401); ?>'><td><strong><?= htmlspecialchars($data['num']) ?></strong></td><td><?= htmlspecialchars($data['address']) ?></td><td><?php if ($live) : ?><span class="a l"></span><?php endif ?><?php if ($rec && count($data['video']['r'] ?? [])) : ?><span class="a r"></span><?php endif ?><?php if ($dev && count($data['video']['d'] ?? [])) : ?> <span class="a d"></span><?php endif ?></td></tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="section modal">
                    <a href="#" class="close"></a>
                    <h2 class="modal-title"><small>Секция №</small><span class="sik__number"></span></h2>
                    <h3 class="modal-subtitle"><span>РИК<?= htmlspecialchars($num) ?></span><?= htmlspecialchars($name) ?></h3>
                    <video class="video-js" controls preload="none" poster="./images/video-background-3.svg" width="1280" height="720" data-setup='{"aspectRatio":"16:9"}'>
                        <p class="vjs-no-js">Моля, използвайте съвременен браузър с видео поддържка</p>
                    </video>
                </div>
                <script src="./scripts/video.min.js"></script>
                <script src="./scripts/playlist.min.js"></script>
                <script src="./scripts/playlist-ui.min.js"></script>
                <script>
                (function () {
                    var servers = <?= json_encode(array_flip($rservers)); ?>,
                        modal = document.querySelector('.modal'),
                        content = document.querySelector('.content'),
                        player = videojs(document.querySelector('video'));
                        fromHash = false;
                        
                    var time;
                    var sikNumber;
                    var sikNumberContainer = document.querySelector('.sik__number');
                    var track = function (ev) {
                        if (time && sikNumber) {
                            var timePlayed = (Date.now() - time) / 1000;
                            _paq.push(['trackEvent', 'video', ev.type, sikNumber, timePlayed]);
                            time = null;
                            sikNumber = null;
                        }
                    };
                    window.addEventListener("beforeunload", function () { track({ type: 'pause' }) });

                    modal.visible = false;
                    modal.source = false;

                    document.querySelector("table").addEventListener("click", function (e) {
                        if (e.target.classList.contains('a')) {
                            e.preventDefault();

                            var sik = e.target.closest('tr').dataset.sik;
                            content.style.display = 'none';
                            modal.style.display = 'block';
                            modal.visible = true;
                            modal.querySelector('.sik__number').innerHTML = sik;
                            window.location.hash = '#' + sik;
                            var live = false;
                            var vid = JSON.parse(e.target.closest('tr').dataset.vid)[e.target.className.replace('a ', '')] || [];
                            vid = vid
                                .map(x => x.replace(Object.keys(servers), Object.values(servers), x).replace('#', sik))
                                .map((x) => {
                                    x = { src: x };
                                    if (x.src.indexOf('m3u8') !== -1) {
                                        live = true;
                                        x.type = "application/x-mpegURL";
                                    }
                                    name = '';
                                    var t = x.src.match(/16\d{8}/);
                                    if (t && t.length) {
                                        t = new Date(parseInt(t, 10) * 1000);
                                        name = ('0' + t.getHours()).slice(-2) + ':' + ('0' + t.getMinutes()).slice(-2);
                                    } else {
                                        t = x.src.match(/2023[\d_]+/);
                                        if (t && t.length) {
                                            t = t[0].split('_')[1] || '';
                                            name = t.substr(0, 2) + ':' + t.substr(2,2);
                                        }
                                    }
                                    return {
                                        name : name,
                                        sources: [ x ],
                                        poster: live ? './images/video-background-2.svg' : './images/video-background-3.svg'
                                    }
                                });
                            
                            player.dispose();
                            modal.innerHTML += `<div class="vjs-playlist"></div><video class="video-js" controls preload="none" poster="./images/video-background-3.svg" width="1280" height="720" data-setup='{"aspectRatio":"16:9"}'>
                                    <p class="vjs-no-js">Моля, използвайте съвременен браузър с видео поддържка</p>
                                </video>`;
                            player = videojs(document.querySelector('video'));
                            if (vid.length > 1) {
                                player.playlistUi();
                            }
                            player.on('play', function () {
                                time = Date.now();
                                sikNumber = sikNumberContainer.textContent;
                            });
                            player.tech(true).on('retryplaylist', function () { time = Date.now(); });
                            player.on('pause', track);

                            modal.source = false;
                            if (vid.length) {
                                modal.source = true;
                                player.playlist(vid);
                                player.playlist.autoadvance(0);
                                player.controls(true);
                                if (!fromHash) {
                                    player.play();
                                }
                                fromHash = false;
                            }
                        } else {
                            var el = e.target.closest('tr');
                            if (el) {
                                el = el.querySelector('.a');
                                if (el) {
                                    el.click();
                                } else {
                                    alert('Все още не са налични записи от избраната секция');
                                }
                            }
                        }
                    });
                    modal.addEventListener("click", function (e) {
                        if (e.target.classList.contains("close")) {
                            e.preventDefault();
                            player.pause();
                            modal.style.display = 'none';
                            content.style.display = 'block';
                            modal.visible = false;
                            window.location.hash = '#/';
                        }
                    });

                    var to = null;
                    var input = document.querySelector('#search');
                    var search = function() {
                        var val = input.value.toLowerCase();
                        if (val.length <= 2) {
                            val = '';
                        }
                        document.querySelectorAll('tbody tr').forEach(function (v) {
                            v.style.display = (!val || (val && v.innerHTML.toLowerCase().indexOf(val))) !== -1 ? 'table-row' : 'none';
                        });
                    }
                    input.addEventListener('input', function () {
                        if (to) { clearTimeout(to); }
                        to = setTimeout(search, 250);
                    });
                    input.value = '';

                    var hash = window.location.hash.replace('#', '');
                    if (hash.length) {
                        var links = document.querySelectorAll('tr[data-sik="'+hash+'"] .a');
                        if (links.length == 1) {
                            fromHash = true;
                            links[0].click();
                        } else {
                            input.value = hash;
                            search();
                        }
                    }

                    // setInterval(function () {
                    //     if (!modal.visible || !modal.source) {
                    //         window.location.reload();
                    //     }
                    // }, 60000);
                }());
                </script>
            </main>
        </div>
    </body>
</html>
