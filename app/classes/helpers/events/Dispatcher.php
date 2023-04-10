<?php

declare(strict_types=1);

namespace helpers\events;

class Dispatcher
{
    protected array $listeners;
    protected array $queue = [];

    public function __construct()
    {
        $this->listeners = [];
    }
    public function listen(string $type, callable $listener): self
    {
        $temp = explode('.', $type);
        $name = $temp[0];
        $namespaces = array_slice($temp, 1);
        if (!count($namespaces)) {
            $namespaces = ['*'];
        }
        foreach ($namespaces as $v) {
            if (!isset($this->listeners[$name . '.' . $v])) {
                $this->listeners[$name . '.' . $v] = [];
            }
            $this->listeners[$name . '.' . $v][] = $listener;
        }
        return $this;
    }
    public function dispatch(EventInterface $event, bool $lazy = false): self
    {
        if ($lazy) {
            $this->queue[] = $event;
            return $this;
        }
        $name = $event->getName();
        $namespaces = $event->getNamespaces();
        $namespaces[] = '*';
        foreach ([$name, '*'] as $n) {
            foreach ($namespaces as $v) {
                foreach ($this->listeners[$n . '.' . $v] ?? [] as $listener) {
                    call_user_func_array($listener, [ $event ]);
                    if ($event->isPropagationStopped()) {
                        return $this;
                    }
                }
            }
        }
        return $this;
    }
    public function run(): void
    {
        foreach ($this->queue as $event) {
            $this->dispatch($event);
        }
    }
}
