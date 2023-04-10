<?php

declare(strict_types=1);

namespace helpers;

final class Exception extends \Exception
{
    protected array $context = [];

    public function set(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
    /**
     * @return mixed
     */
    public function get(string $key, mixed $default = null)
    {
        return $this->context[$key] ?? $default;
    }
    public function del(string $key): self
    {
        unset($this->context[$key]);
        return $this;
    }

    public static function decorate(\Throwable $e, array $context = []): self
    {
        $instance = $e instanceof self ? $e : new static($e->getMessage(), (int)$e->getCode(), $e);
        foreach ($context as $k => $v) {
            $instance->set($k, $v);
        }
        return $instance;
    }
}
