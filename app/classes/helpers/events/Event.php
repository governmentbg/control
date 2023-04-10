<?php

declare(strict_types=1);

namespace helpers\events;

class Event implements EventInterface
{
    protected string $name;
    protected mixed $data = null;
    protected bool $stopped = false;

    public function __construct(string $name, mixed $data = null)
    {
        $this->name = $name;
        $this->data = $data;
    }
    public function getName(): string
    {
        return explode('.', $this->name)[0];
    }
    public function getPayload(): mixed
    {
        return $this->data;
    }
    public function getNamespaces(): array
    {
        return array_slice(explode('.', $this->name), 1);
    }
    public function hasNamespace(string $namespace): bool
    {
        return in_array($namespace, $this->getNamespaces());
    }
    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
    public function stopPropagation(): void
    {
        $this->stopped = true;
    }
}
