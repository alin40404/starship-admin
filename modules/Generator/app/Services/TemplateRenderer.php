<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class TemplateRenderer
{
    protected string $stubPath;

    public function __construct()
    {
        $this->stubPath = dirname(__DIR__, 2).'/resources/stubs';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $stub, array $data = []): string
    {
        $path = $this->stubPath.'/'.$stub;
        if (! File::exists($path)) {
            throw new InvalidArgumentException("模板不存在：{$stub}");
        }

        $content = File::get($path);
        $content = $this->processForeach($content, $data);
        $content = $this->processIf($content, $data);
        $content = $this->replaceExpressions($content, $data);

        return rtrim($content).PHP_EOL;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function processIf(string $content, array $data): string
    {
        $pattern = '/@if\(\$([^)]+)\)((?:(?!@if).)*?)@endif/s';

        while (preg_match($pattern, $content)) {
            $content = (string) preg_replace_callback(
                $pattern,
                function (array $matches) use ($data): string {
                    $condition = $matches[1];
                    $body = $matches[2];
                    $matched = $this->evaluateIfCondition($condition, $data);

                    if (str_contains($body, '@else')) {
                        [$ifPart, $elsePart] = explode('@else', $body, 2);

                        return $this->renderIfBody($matched ? $ifPart : $elsePart, true);
                    }

                    return $this->renderIfBody($body, $matched);
                },
                $content,
            );
        }

        return $content;
    }

    /**
     * 支持 stub 中的简单布尔表达式，如：
     * has_upload_fields
     * has_upload_fields || has_multi_value_fields
     */
    protected function evaluateIfCondition(string $expression, array $data): bool
    {
        $expression = trim($expression);

        if (str_contains($expression, '||')) {
            foreach (array_map('trim', explode('||', $expression)) as $part) {
                if ($this->evaluateIfCondition($part, $data)) {
                    return true;
                }
            }

            return false;
        }

        if (str_contains($expression, '&&')) {
            foreach (array_map('trim', explode('&&', $expression)) as $part) {
                if (! $this->evaluateIfCondition($part, $data)) {
                    return false;
                }
            }

            return true;
        }

        $key = ltrim($expression, '$');

        return ! empty($data[$key]);
    }

    protected function renderIfBody(string $body, bool $include): string
    {
        if (! $include) {
            return '';
        }

        $body = trim($body, "\r\n");

        return $body === '' ? '' : $body."\n";
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function processForeach(string $content, array $data): string
    {
        return (string) preg_replace_callback(
            '/@foreach\(\$(\w+)\s+as\s+\$(\w+)\)(.*?)@endforeach/s',
            function (array $matches) use ($data): string {
                $source = $data[$matches[1]] ?? [];
                $itemVar = $matches[2];
                $template = $matches[3];
                $result = '';

                foreach (Collection::make($source) as $item) {
                    $scope = array_merge($data, [$itemVar => $item]);
                    $chunk = $this->processIf($template, $scope);
                    if (is_string($item) || is_int($item)) {
                        $chunk = str_replace('{{ $'.$itemVar.' }}', (string) $item, $chunk);
                    }
                    $result .= $this->replaceExpressions($chunk, $scope);
                }

                return $result;
            },
            $content,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function replaceExpressions(string $content, array $data): string
    {
        $content = (string) preg_replace_callback(
            '/\{\{\s*\$(\w+)(?:->([\w]+))?\s*\}\}/',
            static function (array $matches) use ($data): string {
                $value = $data[$matches[1]] ?? null;
                if (isset($matches[2])) {
                    if (is_object($value)) {
                        $value = $value->{$matches[2]} ?? '';
                    } elseif (is_array($value)) {
                        $value = $value[$matches[2]] ?? '';
                    }
                }

                return (string) $value;
            },
            $content,
        );

        return (string) preg_replace_callback(
            '/\{\!\!\s*\$(\w+)\s*\!\!\}/',
            static function (array $matches) use ($data): string {
                return (string) ($data[$matches[1]] ?? '');
            },
            $content,
        );
    }
}
