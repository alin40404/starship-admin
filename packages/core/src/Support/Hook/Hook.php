<?php

namespace Starship\Core\Support\Hook;

/**
 * WordPress 风格 Hook：action（副作用）与 filter（改值）。
 * 与 Contract 分工：Contract 换实现；Hook 在既有流程上打补丁。
 */
final class Hook
{
    /**
     * @var array<string, list<array{callback: callable, priority: int, id: int}>>
     */
    private static array $actions = [];

    /**
     * @var array<string, list<array{callback: callable, priority: int, id: int}>>
     */
    private static array $filters = [];

    private static int $sequence = 0;

    public static function reset(): void
    {
        self::$actions = [];
        self::$filters = [];
        self::$sequence = 0;
    }

    public static function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        self::$actions[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'id' => ++self::$sequence,
        ];
    }

    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        self::$filters[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'id' => ++self::$sequence,
        ];
    }

    public static function doAction(string $hook, mixed ...$args): void
    {
        foreach (self::sorted(self::$actions[$hook] ?? []) as $listener) {
            ($listener['callback'])(...$args);
        }
    }

    public static function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach (self::sorted(self::$filters[$hook] ?? []) as $listener) {
            $value = ($listener['callback'])($value, ...$args);
        }

        return $value;
    }

    /**
     * @param  list<array{callback: callable, priority: int, id: int}>  $listeners
     * @return list<array{callback: callable, priority: int, id: int}>
     */
    private static function sorted(array $listeners): array
    {
        usort(
            $listeners,
            static fn (array $a, array $b): int => $a['priority'] <=> $b['priority'] ?: $a['id'] <=> $b['id'],
        );

        return $listeners;
    }
}
