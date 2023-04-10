<?php

declare(strict_types=1);

namespace modules\administration\translation;

use helpers\Jobs;

class TranslationService
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function addTranslation(string $key, string $value): void
    {
        $data = [];
        $data[$key] = $value;
        $this->addTranslations($data);
    }
    public function removeTranslation(string $key): void
    {
        $curr = $this->getTranslations();
        if (isset($curr[$key])) {
            unset($curr[$key]);
        }
        $this->setTranslations($curr);
    }
    public function removeTranslations(array $keys): void
    {
        $curr = $this->getTranslations();
        foreach ($keys as $key) {
            if (isset($curr[$key])) {
                unset($curr[$key]);
            }
        }
        $this->setTranslations($curr);
    }
    public function addTranslations(array $data, bool $removeOthers = false): void
    {
        $curr = $removeOthers ? [] : $this->getTranslations();
        foreach ($data as $k => $v) {
            if (strlen((string)$v)) {
                $curr[(string)$k] = (string)$v;
            }
        }
        $this->setTranslations($curr);
        // this will update the missing translations file
        $this->getMissingTranslations();
    }
    public function getMissingTranslations(): array
    {
        $data = $this->getTranslations();
        $name = dirname($this->path) . '/missing.' . basename($this->path);
        if (!is_file($name)) {
            return [];
        }
        $curr = array_filter(
            array_unique(json_decode(file_get_contents($name) ?: throw new \RuntimeException(), true))
        );
        if (!$curr) {
            $curr = [];
        }
        sort($curr);
        $temp = [];
        foreach ($curr as $k) {
            if (!isset($data[$k])) {
                $temp[$k] = '';
            }
        }
        file_put_contents($name, json_encode(array_keys($temp)));
        return $temp;
    }
    public function getTranslations(): array
    {
        if (!is_file($this->path) || !is_readable($this->path)) {
            return [];
        }
        try {
            $data = file_get_contents($this->path) ?: throw new \RuntimeException();
            $temp = json_decode($data, true);
            if (!is_array($temp)) {
                return [];
            }
            ksort($temp);
            return $temp;
        } catch (\Exception $e) {
            return [];
        }
    }
    protected function setTranslations(array $data, bool $sort = true): void
    {
        if ($sort) {
            ksort($data);
        }
        if (
            is_file($this->path) && !@file_put_contents(
                $this->path,
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
            )
        ) {
            throw new \Exception('Could not store translations');
        }
        Jobs::langs();
    }
}
