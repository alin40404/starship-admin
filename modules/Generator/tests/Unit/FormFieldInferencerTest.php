<?php

namespace Modules\Generator\Tests\Unit;

use Modules\Generator\App\Support\EnumFieldResolver;
use Modules\Generator\App\Support\FormFieldInferencer;
use Modules\Generator\App\Support\RelationFieldResolver;
use Modules\Generator\App\Support\UploadFieldResolver;
use Modules\Generator\App\Services\SchemaReader;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormFieldInferencerTest extends TestCase
{
    #[Test]
    public function infers_enum_column_as_select(): void
    {
        $inferencer = $this->makeInferencer();
        $result = $inferencer->infer([
            'name' => 'type',
            'type' => "enum('hot','recommend')",
            'nullable' => false,
            'key' => '',
            'default' => null,
            'extra' => '',
            'comment' => '类型: hot=热门,recommend=推荐',
        ], 'demo_items', [], ['required']);

        $this->assertSame('RadioGroup', $result['form_component']);
        $this->assertSame('enum', $result['form_options']['kind']);
        $this->assertContains('in:hot,recommend', $result['validation_rules']);
    }

    #[Test]
    public function infers_image_field_as_picture_upload(): void
    {
        $inferencer = $this->makeInferencer();
        $result = $inferencer->infer([
            'name' => 'cover',
            'type' => 'varchar(255)',
            'nullable' => true,
            'key' => '',
            'default' => null,
            'extra' => '',
            'comment' => '封面图',
        ], 'demo_items', [], []);

        $this->assertSame('PictureUploadField', $result['form_component']);
        $this->assertSame('image', $result['form_options']['kind']);
    }

    #[Test]
    public function upload_field_resolver_detects_attachment_names(): void
    {
        $result = UploadFieldResolver::resolve('attachment', 'varchar(500)');
        $this->assertSame('AttachmentField', $result['component']);
    }

    #[Test]
    public function enum_field_resolver_parses_mysql_enum(): void
    {
        $values = EnumFieldResolver::parseEnumColumnType("enum('normal','hidden')");
        $this->assertSame(['normal', 'hidden'], $values);
    }

    protected function makeInferencer(): FormFieldInferencer
    {
        $schemaReader = new SchemaReader();

        return new FormFieldInferencer(
            $schemaReader,
            new RelationFieldResolver($schemaReader, static fn (): null => null),
        );
    }
}
