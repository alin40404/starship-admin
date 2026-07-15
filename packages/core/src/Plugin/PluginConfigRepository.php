<?php

namespace Starship\Core\Plugin;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Starship\Core\Models\PluginConfig;

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
        'password',
    ];

    /**
     * @var list<string>
     */
    private const FIELD_TYPES = [
        'string',
        'password',
        'number',
        'select',
        'switch',
        'textarea',
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

        return $this->maskConfig($merged, $slug);
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

            $encrypted = $this->shouldEncrypt($plugin, $key);
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
        return array_values(array_map(
            static fn (array $field): string => (string) $field['key'],
            $this->schema($slug),
        ));
    }

    /**
     * 配置表单 Schema（对象数组）。无 schema 文件时回退为默认配置 key → string 字段。
     *
     * @return list<array{
     *   key: string,
     *   label: string,
     *   type: string,
     *   options: list<array{label: string, value: string}>,
     *   default: mixed,
     *   rules: list<string>,
     *   help: string,
     *   group: string|null,
     *   encrypted: bool
     * }>
     */
    public function schema(string $slug): array
    {
        $plugin = $this->pluginManager->findBySlug($slug);
        if ($plugin === null) {
            return [];
        }

        $this->ensurePluginTranslations($plugin);

        $defaults = $this->defaultConfig($plugin);
        $declared = $this->loadDeclaredSchema($plugin);
        if ($declared !== []) {
            return $this->normalizeSchemaFields($declared, $defaults, $plugin);
        }

        $fields = [];
        foreach (array_keys($defaults) as $key) {
            $fields[] = $this->normalizeSchemaField([
                'key' => $key,
                'type' => $this->isSensitiveKey((string) $key) ? 'password' : 'string',
                'default' => $defaults[$key] ?? '',
            ], $defaults, $plugin);
        }

        return $fields;
    }

    protected function ensurePluginTranslations(Plugin $plugin): void
    {
        $path = $plugin->directory().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'lang';
        if (! is_dir($path)) {
            return;
        }

        app('translator')->addNamespace($plugin->slug(), $path);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function loadDeclaredSchema(Plugin $plugin): array
    {
        $path = $plugin->configSchemaPath();
        if ($path === null) {
            return [];
        }

        $schema = require $path;

        return is_array($schema) ? array_values(array_filter($schema, 'is_array')) : [];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $defaults
     * @return list<array<string, mixed>>
     */
    protected function normalizeSchemaFields(array $fields, array $defaults, Plugin $plugin): array
    {
        $normalized = [];
        foreach ($fields as $field) {
            $item = $this->normalizeSchemaField($field, $defaults, $plugin);
            if ($item !== null) {
                $normalized[] = $item;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>|null
     */
    protected function normalizeSchemaField(array $field, array $defaults, Plugin $plugin): ?array
    {
        $key = $field['key'] ?? null;
        if (! is_string($key) || $key === '') {
            return null;
        }

        $type = strtolower((string) ($field['type'] ?? 'string'));
        if (! in_array($type, self::FIELD_TYPES, true)) {
            $type = 'string';
        }

        $encrypted = array_key_exists('encrypted', $field)
            ? (bool) $field['encrypted']
            : $this->isSensitiveKey($key);

        if ($encrypted && $type === 'string') {
            $type = 'password';
        }

        $options = [];
        foreach (($field['options'] ?? []) as $option) {
            if (is_array($option)) {
                $value = (string) ($option['value'] ?? '');
                $label = $this->resolveLocalizedText($option['label'] ?? $value);
                if ($value !== '') {
                    $options[] = ['label' => $label, 'value' => $value];
                }

                continue;
            }

            if (is_string($option) || is_int($option)) {
                $options[] = ['label' => (string) $option, 'value' => (string) $option];
            }
        }

        $rules = [];
        foreach (($field['rules'] ?? []) as $rule) {
            if (is_string($rule) && $rule !== '') {
                $rules[] = $rule;
            }
        }

        $group = $field['group'] ?? null;

        return [
            'key' => $key,
            'label' => $this->resolveLocalizedText($field['label'] ?? $key),
            'type' => $type,
            'options' => $options,
            'default' => $field['default'] ?? ($defaults[$key] ?? ''),
            'rules' => $rules,
            'help' => $this->resolveLocalizedText($field['help'] ?? ''),
            'group' => is_string($group) && $group !== '' ? $group : null,
            'encrypted' => $encrypted,
        ];
    }

    protected function resolveLocalizedText(mixed $value): string
    {
        if (is_string($value)) {
            if ($value === '') {
                return '';
            }

            // 语言包键：如 sms-aliyun::messages.fields.access_key_label
            if (str_contains($value, '::')) {
                $translated = __($value);

                return $translated !== $value ? (string) $translated : $value;
            }

            return $value;
        }

        if (! is_array($value)) {
            return '';
        }

        $locale = str_replace('-', '_', (string) app()->getLocale());
        $candidates = [
            $locale,
            strtolower($locale),
            explode('_', $locale)[0] ?? '',
            'zh_CN',
            'zh_cn',
            'en',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && isset($value[$candidate]) && is_scalar($value[$candidate])) {
                return (string) $value[$candidate];
            }
        }

        foreach ($value as $text) {
            if (is_scalar($text)) {
                return (string) $text;
            }
        }

        return '';
    }

    protected function shouldEncrypt(Plugin $plugin, string $key): bool
    {
        foreach ($this->schema($plugin->slug()) as $field) {
            if (($field['key'] ?? null) === $key) {
                return (bool) ($field['encrypted'] ?? false);
            }
        }

        return $this->isSensitiveKey($key);
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
    protected function maskConfig(array $config, ?string $slug = null): array
    {
        $encryptedKeys = [];
        if ($slug !== null) {
            foreach ($this->schema($slug) as $field) {
                if (! empty($field['encrypted'])) {
                    $encryptedKeys[(string) $field['key']] = true;
                }
            }
        }

        $masked = [];

        foreach ($config as $key => $value) {
            $keyString = (string) $key;
            if (isset($encryptedKeys[$keyString]) || $this->isSensitiveKey($keyString)) {
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
