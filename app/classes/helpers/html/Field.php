<?php

declare(strict_types=1);

namespace helpers\html;

class Field
{
    use ElementTrait;

    protected ?Form $form = null;
    protected array $options = [];

    public function __construct(string $type = "text", array $attr = [], array $options = [])
    {
        $this->attr = $attr;
        $this->attr['type'] = $type;
        $this->options = $options;
    }

    public function getType(string $default = 'text'): string
    {
        return $this->getAttr('type', $default);
    }
    public function setType(string $type): self
    {
        return $this->setAttr('type', $type);
    }

    public function getName(string $default = ''): string
    {
        return $this->getAttr('name', $default);
    }
    public function setName(string $value): self
    {
        return $this->setAttr('name', $value);
    }
    /**
     * @param mixed $default
     * @return mixed
     */
    public function getValue(mixed $default = null): mixed
    {
        return $this->getAttr('value', $default);
    }
    /**
     * @param mixed $value
     * @return self
     */
    public function setValue(mixed $value): self
    {
        return $this->setAttr('value', $value);
    }

    public function enable(): self
    {
        if ($this->hasAttr('readonly')) {
            $this->delAttr('readonly');
        }
        if ($this->hasAttr('disabled')) {
            $this->delAttr('disabled');
        }
        return $this;
    }
    public function disable(): self
    {
        if (in_array($this->getType(), ['select', 'multipleselect', 'tags'])) {
            return $this->setAttr('disabled', 'disabled');
        }
        return $this->setAttr('readonly', 'readonly');
    }

    public function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }
    /**
     * @return mixed
     */
    public function getOption(string $key, mixed $default = null)
    {
        $opt = $this->options[$key] ?? $default;
        if ($opt instanceof \Closure) {
            $opt = call_user_func($opt, $this);
        }
        return $opt;
    }
    public function getOptions(): array
    {
        $opts = [];
        foreach (array_keys($this->options) as $k) {
            $opts[$k] = $this->getOption((string)$k);
        }
        return $opts;
    }
    public function setOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;
        return $this;
    }
    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }
    public function delOption(string $option): self
    {
        unset($this->options[$option]);
        return $this;
    }
    public function delOptions(): self
    {
        $this->options = [];
        return $this;
    }
    public function setForm(Form $form = null): self
    {
        $this->form = $form;
        return $this;
    }
    public function getForm(): ?Form
    {
        return $this->form;
    }
}
