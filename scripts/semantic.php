#!/usr/bin/env php
<?php

/**
 * This script replaces the google font used in semantic UI with the default system font.
 *
 * It is executed manually after a semantic UI upgrade - no need to schedule!
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$css = <<<'CSS'
/*! system-font.css v2.0.2 | CC0-1.0 License | github.com/jonathantneal/system-font-css */
@font-face {
    font-family: Lato;
    font-style: normal;
    font-weight: 300;
    src: local(".SFNS-Light"),
        local(".SFNSText-Light"),
        local(".HelveticaNeueDeskInterface-Light"),
        local(".LucidaGrandeUI"),
        local("Segoe UI Light"),
        local("Ubuntu Light"),
        local("Roboto-Light"),
        local("DroidSans"),
        local("Tahoma");
}

@font-face {
    font-family: Lato;
    font-style: italic;
    font-weight: 300;
    src: local(".SFNS-LightItalic"),
        local(".SFNSText-LightItalic"),
        local(".HelveticaNeueDeskInterface-Italic"),
        local(".LucidaGrandeUI"),
        local("Segoe UI Light Italic"),
        local("Ubuntu Light Italic"),
        local("Roboto-LightItalic"),
        local("DroidSans"),
        local("Tahoma");
}

@font-face {
    font-family: Lato;
    font-style: normal;
    font-weight: 400;
    src: local(".SFNS-Regular"),
        local(".SFNSText-Regular"),
        local(".HelveticaNeueDeskInterface-Regular"),
        local(".LucidaGrandeUI"),
        local("Segoe UI"),
        local("Ubuntu"),
        local("Roboto-Regular"),
        local("DroidSans"),
        local("Tahoma");
}

@font-face {
    font-family: Lato;
    font-style: italic;
    font-weight: 400;
    src: local(".SFNS-Italic"),
        local(".SFNSText-Italic"),
        local(".HelveticaNeueDeskInterface-Italic"),
        local(".LucidaGrandeUI"),
        local("Segoe UI Italic"),
        local("Ubuntu Italic"),
        local("Roboto-Italic"),
        local("DroidSans"),
        local("Tahoma");
}

@font-face {
    font-family: Lato;
    font-style: normal;
    font-weight: 500;
    src: local(".SFNS-Medium"),
        local(".SFNSText-Medium"),
        local(".HelveticaNeueDeskInterface-MediumP4"),
        local(".LucidaGrandeUI"),
        local("Segoe UI Semibold"),
        local("Ubuntu Medium"),
        local("Roboto-Medium"),
        local("DroidSans-Bold"),
        local("Tahoma Bold");
}

@font-face {
    font-family: Lato;
    font-style: italic;
    font-weight: 500;
    src: local(".SFNS-MediumItalic"),
        local(".SFNSText-MediumItalic"),
        local(".HelveticaNeueDeskInterface-MediumItalicP4"),
        local(".LucidaGrandeUI"),
        local("Segoe UI Semibold Italic"),
        local("Ubuntu Medium Italic"),
        local("Roboto-MediumItalic"),
        local("DroidSans-Bold"),
        local("Tahoma Bold");
}

@font-face {
    font-family: Lato;
    font-style: normal;
    font-weight: 700;
    src: local(".SFNS-Bold"),
        local(".SFNSText-Bold"),
        local(".HelveticaNeueDeskInterface-Bold"),
        local(".LucidaGrandeUI"),
        local("Segoe UI Bold"),
        local("Ubuntu Bold"),
        local("Roboto-Bold"),
        local("DroidSans-Bold"),
        local("Tahoma Bold");
}

@font-face {
    font-family: Lato;
    font-style: italic;
    font-weight: 700;
    src: local(".SFNS-BoldItalic"),
        local(".SFNSText-BoldItalic"),
        local(".HelveticaNeueDeskInterface-BoldItalic"),
        local(".LucidaGrandeUI"),
        local("Segoe UI Bold Italic"),
        local("Ubuntu Bold Italic"),
        local("Roboto-BoldItalic"),
        local("DroidSans-Bold"),
        local("Tahoma Bold");
}
CSS;

// replace Lato google font with local system
foreach (['semantic.min.css'] as $file) {
    $file = __DIR__ . '/../public/assets/static/fomantic-ui-css/' . $file;
    $content = file_get_contents($file) ?: throw new \RuntimeException();
    $content = preg_replace(
        '(' . preg_quote("@import url(https://fonts.googleapis") . '[^;]+;)ui',
        $css,
        $content
    ) ?? '';
    $content = preg_replace(
        '(' . preg_quote("@import url('https://fonts.googleapis") . '[^;]+;)ui',
        $css,
        $content
    ) ?? '';
    file_put_contents($file, $content);
}

// add nonce to tinyMCE scripts
$file = __DIR__ . '/../public/assets/static/tinymce/tinymce.min.js';
$content = file_get_contents($file) ?: throw new \RuntimeException();
$content = preg_replace(
    '(' . preg_quote('.id="mceDefaultStyles",') . ')ui',
    '.id="mceDefaultStyles",n.setAttribute("nonce",window.tinyNonce),',
    str_replace('n.setAttribute("nonce",window.tinyNonce),', '', $content)
) ?? '';
file_put_contents($file, $content);

// remove inline styles
$file = __DIR__ . '/../public/assets/static/plupload/plupload.full.min.js';
$content = file_get_contents($file) ?: throw new \RuntimeException();
$content = preg_replace(
    '(style="[^"]+")ui',
    '',
    $content
) ?? '';
file_put_contents($file, $content);

// remove inline styles
$file = __DIR__ . '/../public/assets/static/fomantic-ui-css/semantic.min.js';
$content = file_get_contents($file) ?: throw new \RuntimeException();
$content = str_replace('.attr("style"', '.css("cssText"', $content);
file_put_contents($file, $content);

// remove animation
$file = __DIR__ . '/../public/assets/static/fomantic-ui-css/semantic.min.css';
$content = file_get_contents($file) ?: throw new \RuntimeException();
$content = preg_replace('(([\d]*\.)?[\d]+m?s( |;|\}))i', '0s$2', $content);
file_put_contents($file, $content);

// border-radius -> 2px
$file = __DIR__ . '/../public/assets/static/fomantic-ui-css/semantic.min.css';
$content = file_get_contents($file) ?: throw new \RuntimeException();
$content = preg_replace_callback(
    '(radius:([^;}]+))i',
    function (array $matches) {
        $values = explode(' ', $matches[1]);
        foreach ($values as $k => $v) {
            if ($v == '.21428571rem' || $v == '.28571429rem') {
                //$values[$k] = '2px';
                $values[$k] = '0';
            }
        }
        return 'radius:' . implode(' ', $values);
    },
    $content
);
file_put_contents($file, $content);

copy(__DIR__ . '/../public/assets/jstree.png', __DIR__ . '/../public/assets/static/jstree/themes/default/32px.png');
