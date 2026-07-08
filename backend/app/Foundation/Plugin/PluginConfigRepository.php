<?php

namespace App\Foundation\Plugin;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Modules\System\App\Models\PluginConfig;

class PluginConfigRepository
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYWORDS = [
        'access_key',
        'secret_key',
        'app_secret',
        'token',
        'private_key',
    ];

    public function __construct(
        protected PluginManager $pluginManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function all(string $slug, bool $masked = true): array
    {
        $plugin = $this->pluginManager->findBySlug($slug);
        if ($plugin === null) {
            return [];
        }

        $defaults = $this->defaultConfig($plugin);
        $envConfig = $this->envConfig($plugin, $defaults);
        $stored = $this->storedConfig($slug);
        $merged = array_merge($defaults, $envConfig, $stored);

        if (! $masked) {
            return $merged;
        }

        return $this->maskConfig($merged);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function save(string $slug, array $values): array
    {
        $plugin = $this->pluginManager->findBySlug($slug);
        if ($plugin === null) {
            return [];
        }

        $defaults = $this->defaultConfig($plugin);

        foreach ($values as $key => $value) {
            if (! is_string($key) || $key === '' || ! array_key_exists($key, $defaults)) {
                continue;
            }

            if ($this->shouldSkipMaskedValue($value)) {
                continue;
            }

            $encrypted = $this->isSensitiveKey($key);
            $storedValue = is_scalar($value) || $value === null
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);

            PluginConfig::query()->updateOrCreate(
                [
                    'plugin_slug' => $slug,
                    'config_key' => $key,
                ],
                [
                    'config_value' => $encrypted ? Crypt::encryptString((string) $storedValue) : $storedValue,
                    'value_type' => $this->detectValueType($value),
                    'is_encrypted' => $encrypted ? 1 : 0,
                ],
            );
        }

        return $this->all($slug, masked: true);
    }

    public function get(string $slug, string $key, mixed $default = null): mixed
    {
        $config = $this->all($slug, masked: false);

        return $config[$key] ?? $default;
    }

    /**
     * @return list<string>
     */
    public function schemaKeys(string $slug): array
    {
        $plugin = $this->pluginManager->findBySlug($slug);
        if ($plugin === null) {
            return [];
        }

        return array_keys($this->defaultConfig($plugin));
    }

    /**
     * @return array<string, mixed>
     */
    protected function envConfig(Plugin $plugin, array $defaults): array
    {
        $prefix = 'PLUGIN_'.strtoupper(str_replace('-', '_', $plugin->slug())).'_';
        $result = [];

        foreach (array_keys($defaults) as $key) {
            $envKey = $prefix.strtoupper((string) $key);
            $value = env($envKey);
            if ($value !== null && $value !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultConfig(Plugin $plugin): array
    {
        $configPath = $plugin->configPath();
        if ($configPath === null || ! is_file($configPath)) {
            return [];
        }

        $config = require $configPath;

        return is_array($config) ? $config : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function storedConfig(string $slug): array
    {
        $result = [];

        foreach (PluginConfig::query()->where('plugin_slug', $slug)->get() as $item) {
            $value = $item->config_value;
            if ((int) $item->is_encrypted === 1 && is_string($value) && $value !== '') {
                try {
                    $value = Crypt::decryptString($value);
                } catch (DecryptException) {
                    $value = '';
                }
            }

            $result[$item->config_key] = $this->castValue($value, (string) $item->value_type);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function maskConfig(array $config): array
    {
        $masked = [];

        foreach ($config as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $masked[$key] = $value !== null && $value !== '' ? '__configured__' : '';
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    protected function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace('-', '_', $key));

        foreach (self::SENSITIVE_KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function shouldSkipMaskedValue(mixed $value): bool
    {
        return $value === '__configured__';
    }

    protected function detectValueType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'bool',
            is_int($value) => 'int',
            is_array($value) => 'json',
            default => 'string',
        };
    }

    protected function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }
}
