<?php

namespace Modules\Generator\Tests\Unit;

use Modules\Generator\App\Support\FormSchemaBuilder;
use PHPUnit\Framework\TestCase;

class MultiAttachmentFieldNamesTest extends TestCase
{
    public function test_multi_attachment_field_names_without_max_count(): void
    {
        $builder = new FormSchemaBuilder;
        $names = $builder->multiAttachmentFieldNames(collect([
            (object) [
                'column_name' => 'attachments',
                'form_component' => 'AttachmentField',
                'form_options' => ['kind' => 'attachment'],
            ],
            (object) [
                'column_name' => 'attachment',
                'form_component' => 'AttachmentField',
                'form_options' => ['kind' => 'attachment', 'max_count' => 1],
            ],
            (object) [
                'column_name' => 'brochure',
                'form_component' => 'AttachmentField',
                'form_options' => ['kind' => 'attachment', 'max_count' => 1],
            ],
        ]));

        $this->assertSame(['attachments'], $names);
    }
}
