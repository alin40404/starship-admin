<?php

namespace Modules\Generator\App\Support;

final class StatusFieldProfile
{
    /**
     * @param  list<int|string>  $allowedValues
     */
    public function __construct(
        public readonly string $mode,
        public readonly int|string $enabledValue,
        public readonly int|string $disabledValue,
        public readonly array $allowedValues,
    ) {}

    public function isInteger(): bool
    {
        return $this->mode === 'integer';
    }

    public static function integer(): self
    {
        return new self('integer', 1, 0, [0, 1]);
    }

    /**
     * @param  list<string>  $values
     */
    public static function string(array $values): self
    {
        $enabled = $values[0] ?? 'normal';
        $disabled = $values[1] ?? 'hidden';

        return new self('string', $enabled, $disabled, $values);
    }

    public function tsType(): string
    {
        return $this->isInteger() ? 'number' : 'string';
    }

    public function tsUnionType(): string
    {
        if ($this->isInteger()) {
            return '0 | 1';
        }

        return implode(' | ', array_map(
            static fn (int|string $value): string => "'".addslashes((string) $value)."'",
            $this->allowedValues,
        ));
    }

    public function laravelInRule(): string
    {
        return 'in:'.implode(',', array_map(
            static fn (int|string $value): string => (string) $value,
            $this->allowedValues,
        ));
    }

    public function laravelValidationType(): string
    {
        return $this->isInteger() ? 'integer' : 'string';
    }

    public function jsLiteral(int|string $value): string
    {
        return $this->isInteger() ? (string) (int) $value : "'".addslashes((string) $value)."'";
    }

    public function labelTsExpr(int|string $value): string
    {
        if ($this->isInteger()) {
            return (int) $value === (int) $this->enabledValue
                ? "\$t('common.enabled')"
                : "\$t('common.disabled')";
        }

        return match ((string) $value) {
            'normal' => "\$t('system.shared.status.normal')",
            'hidden' => "\$t('system.shared.status.hidden')",
            default => "'".addslashes((string) $value)."'",
        };
    }
}
