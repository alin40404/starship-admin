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

    public function version(): ?string
    {
        $version = $this->attributes['version'] ?? null;

        return is_string($version) && $version !== '' ? $version : null;
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

    /**
     * 模块关联菜单（禁用时由 System 侧过滤）。
     *
     * @return array{paths: list<string>, permission_prefixes: list<string>}
     */
    public function menus(): array
    {
        $menus = $this->attributes['menus'] ?? [];
        if (! is_array($menus)) {
            $menus = [];
        }

        $paths = $menus['paths'] ?? [];
        $prefixes = $menus['permission_prefixes'] ?? [];

        return [
            'paths' => array_values(array_filter(
                is_array($paths) ? $paths : [],
                static fn ($path) => is_string($path) && $path !== '',
            )),
            'permission_prefixes' => array_values(array_filter(
                is_array($prefixes) ? $prefixes : [],
                static fn ($prefix) => is_string($prefix) && $prefix !== '',
            )),
        ];
    }

    public function all(): array
    {
        return $this->attributes;
    }
}
