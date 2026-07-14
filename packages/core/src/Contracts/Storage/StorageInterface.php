<?php

namespace Starship\Core\Contracts\Storage;

/**
 * 文件存储契约。System 提供基于 Laravel Filesystem 的默认实现，插件可覆盖容器绑定。
 */
interface StorageInterface
{
    /**
     * @param  resource|string  $content
     */
    public function put(string $path, mixed $content): bool;

    public function get(string $path): string;

    public function delete(string $path): bool;

    public function url(string $path): string;

    public function exists(string $path): bool;
}
