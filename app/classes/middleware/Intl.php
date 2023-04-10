<?php

declare(strict_types=1);

namespace middleware;

use vakata\http\Request;
use vakata\http\Response;
use vakata\intl\Intl as Translations;

class Intl
{
    protected Translations $intl;
    protected array $langs;
    protected string $cookieName;
    protected bool $translations;

    public function __construct(
        Translations $intl,
        array $langs = [],
        string $cookieName = '_LOCALE',
        bool $translations = false
    ) {
        $this->intl = $intl;
        $this->langs = $langs;
        $this->cookieName = $cookieName;
        $this->translations = $translations;
    }
    public function __invoke(Request $req, callable $next): Response
    {
        $available = array_keys($this->langs);
        $lng = strtolower(
            $req->getCookieParams()[$this->cookieName] ??
            $req->getPreferredResponseLanguage('bg', $available)
        );
        $locale = in_array($lng, $available) ? $lng : ($available[0] ?? null);
        if ($locale) {
            if (is_file($this->langs[$locale] . '.php')) {
                /** @psalm-suppress UnresolvableInclude */
                $lang = include $this->langs[$locale] . '.php';
                $this->intl->addArray($lang);
            } else {
                $this->intl->addFile($this->langs[$locale]);
            }
        }
        $res = $next(
            $req->withAttribute(
                'locale',
                $locale ? $this->intl->get('_locale.code.short', [], (string)$locale) : null
            )
        );
        if ($this->translations && $locale) {
            $missing = [];
            foreach ($this->intl->used() as $k => $v) {
                $k = (string)$k;
                if ($k === '') {
                    continue;
                }
                if ($v === null || mb_strtolower($k) === mb_strtolower($v)) {
                    $missing[] = $k;
                }
            }
            if (count($missing)) {
                $name = dirname($this->langs[$locale]) . '/missing.' . basename($this->langs[$locale]);
                $curr = [];
                if (is_file($name)) {
                    $curr = json_decode(file_get_contents($name) ?: throw new \RuntimeException(), true);
                }
                if (!$curr) {
                    $curr = [];
                }
                $all = array_filter(array_unique(array_merge($missing, $curr)));
                file_put_contents($name, json_encode($all));
            }
        }
        return $res;
    }
}
