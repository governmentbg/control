<?php

declare(strict_types=1);

namespace helpers\events;

interface EventInterface
{
    public function getName(): string;
    public function getNamespaces(): array;
    public function hasNamespace(string $namespace): bool;
    public function getPayload(): mixed;
    public function isPropagationStopped(): bool;
    public function stopPropagation(): void;
}
