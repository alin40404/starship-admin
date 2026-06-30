<?php

namespace App\Support\Module;

class Module
{
    public function __construct(
        protected array $attributes
    ) {}

    public function name(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function title(): string
    {
        return $this->attributes['title'] ?? $this->name();
    }

    public function enabled(): bool
    {
        return (bool) ($this->attributes['enabled'] ?? false);
    }

    public function providers(): array
    {
        return $this->attributes['providers'] ?? [];
    }

    /**
     * @return list<class-string<\Illuminate\Database\Seeder>>
     */
    public function seeders(): array
    {
        return $this->attributes['seeders'] ?? [];
    }

    public function all(): array
    {
        return $this->attributes;
    }
}
