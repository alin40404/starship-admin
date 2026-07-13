<?php

namespace Starship\Core\Module;

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

    public function devOnly(): bool
    {
        return (bool) ($this->attributes['dev_only'] ?? false);
    }

    public function providers(): array
    {
        return $this->attributes['providers'] ?? [];
    }

    /**
     * @return list<string>
     */
    public function requireModules(): array
    {
        $require = $this->attributes['require'] ?? [];
        if (! is_array($require)) {
            return [];
        }

        $modules = $require['modules'] ?? [];

        return array_values(array_filter(
            is_array($modules) ? $modules : [],
            static fn ($module) => is_string($module) && $module !== '',
        ));
    }

    /**
     * @return list<class-string<\Illuminate\Database\Seeder>>
     */
    public function seeders(): array
    {
        return $this->attributes['seeders'] ?? [];
    }

    /**
     * @return array{paths: list<string>}
     */
    public function menus(): array
    {
        $menus = $this->attributes['menus'] ?? [];
        if (! is_array($menus)) {
            $menus = [];
        }

        $paths = $menus['paths'] ?? [];

        return [
            'paths' => array_values(array_filter(
                is_array($paths) ? $paths : [],
                static fn ($path) => is_string($path) && $path !== '',
            )),
        ];
    }

    /**
     * @return array{prefixes: list<string>, exact: list<string>}
     */
    public function permissions(): array
    {
        return $this->parsePermissionBindings($this->attributes['permissions'] ?? []);
    }

    /**
     * @return array{prefixes: list<string>, exact: list<string>}
     */
    private function parsePermissionBindings(mixed $permissions): array
    {
        if (! is_array($permissions)) {
            $permissions = [];
        }

        $prefixes = $permissions['prefixes'] ?? [];
        $exact = $permissions['exact'] ?? [];

        return [
            'prefixes' => array_values(array_filter(
                is_array($prefixes) ? $prefixes : [],
                static fn ($prefix) => is_string($prefix) && $prefix !== '',
            )),
            'exact' => array_values(array_filter(
                is_array($exact) ? $exact : [],
                static fn ($permission) => is_string($permission) && $permission !== '',
            )),
        ];
    }

    public function all(): array
    {
        return $this->attributes;
    }
}
