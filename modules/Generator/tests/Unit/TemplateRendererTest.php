<?php

namespace Modules\Generator\Tests\Unit;

use Modules\Generator\App\Services\TemplateRenderer;
use PHPUnit\Framework\TestCase;

class TemplateRendererTest extends TestCase
{
    public function test_processes_compound_or_condition_in_if(): void
    {
        $renderer = new TemplateRenderer;
        $content = <<<'STUB'
@if($has_upload_fields || $has_multi_value_fields)
upload-or-multi
@endif
@if($has_upload_fields)
upload-only
@endif
STUB;

        $result = (new \ReflectionClass($renderer))
            ->getMethod('processIf')
            ->invoke($renderer, $content, [
                'has_upload_fields' => false,
                'has_multi_value_fields' => true,
            ]);

        $this->assertStringContainsString('upload-or-multi', $result);
        $this->assertStringNotContainsString('@if', $result);
        $this->assertStringNotContainsString('upload-only', $result);
    }

    public function test_processes_if_else_with_compound_condition(): void
    {
        $renderer = new TemplateRenderer;
        $content = <<<'STUB'
@if($has_upload_fields || $has_multi_value_fields)
yes
@else
no
@endif
STUB;

        $result = (new \ReflectionClass($renderer))
            ->getMethod('processIf')
            ->invoke($renderer, $content, [
                'has_upload_fields' => true,
                'has_multi_value_fields' => false,
            ]);

        $this->assertSame("yes\n", $result);
    }

    public function test_processes_nested_if_blocks_innermost_first(): void
    {
        $renderer = new TemplateRenderer;
        $content = <<<'STUB'
@if($has_picture_upload_fields)
const singlePictureUploadFieldNames = ['cover'] as const;
@if($has_multi_picture_upload_fields)
const multiPictureUploadFieldNames = ['images'] as const;
@endif
@endif
@if($has_multi_attachment_fields)
const multiAttachmentFieldNames = ['attachments'] as const;
@endif
STUB;

        $result = (new \ReflectionClass($renderer))
            ->getMethod('processIf')
            ->invoke($renderer, $content, [
                'has_picture_upload_fields' => true,
                'has_multi_picture_upload_fields' => true,
                'has_multi_attachment_fields' => true,
            ]);

        $this->assertStringContainsString("const singlePictureUploadFieldNames = ['cover'] as const;", $result);
        $this->assertStringContainsString("const multiPictureUploadFieldNames = ['images'] as const;", $result);
        $this->assertStringContainsString("const multiAttachmentFieldNames = ['attachments'] as const;", $result);
        $this->assertStringNotContainsString('@if', $result);
        $this->assertStringNotContainsString('@endif', $result);
    }

    public function test_processes_nested_if_when_inner_condition_is_false(): void
    {
        $renderer = new TemplateRenderer;
        $content = <<<'STUB'
@if($has_picture_upload_fields)
single
@if($has_multi_picture_upload_fields)
multi
@endif
@endif
STUB;

        $result = (new \ReflectionClass($renderer))
            ->getMethod('processIf')
            ->invoke($renderer, $content, [
                'has_picture_upload_fields' => true,
                'has_multi_picture_upload_fields' => false,
            ]);

        $this->assertStringContainsString('single', $result);
        $this->assertStringNotContainsString('multi', $result);
        $this->assertStringNotContainsString('@if', $result);
        $this->assertStringNotContainsString('@endif', $result);
    }
}
