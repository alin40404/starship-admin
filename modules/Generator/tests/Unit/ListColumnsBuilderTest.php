<?php

namespace Modules\Generator\Tests\Unit;

use Modules\Generator\App\Services\CodegenService;
use PHPUnit\Framework\TestCase;

class ListColumnsBuilderTest extends TestCase
{
    public function test_builds_multi_image_column_with_csv_helpers(): void
    {
        $body = $this->invokeBuildListColumnsBody([
            (object) [
                'column_name' => 'images',
                'column_type' => 'varchar(500)',
                'form_component' => 'PictureUploadField',
                'form_options' => ['kind' => 'image'],
            ],
        ]);

        $this->assertStringContainsString("getSrc: (row: DemoRow) => firstCsvValue(row.images)", $body);
        $this->assertStringContainsString("getExtraCount: (row: DemoRow) => csvExtraCount(row.images)", $body);
        $this->assertStringContainsString("name: 'CellImage'", $body);
    }

    public function test_builds_attachment_column_with_cell_file(): void
    {
        $body = $this->invokeBuildListColumnsBody([
            (object) [
                'column_name' => 'attachment',
                'column_type' => 'varchar(255)',
                'form_component' => 'AttachmentField',
                'form_options' => ['kind' => 'attachment'],
            ],
        ]);

        $this->assertStringContainsString("name: 'CellFile'", $body);
        $this->assertStringContainsString('firstCsvValue', $body);
        $this->assertStringContainsString('csvExtraCount', $body);
        $this->assertStringContainsString('extractFileNameFromUrl', $body);
    }

    public function test_builds_rich_editor_column_with_html_summary(): void
    {
        $body = $this->invokeBuildListColumnsBody([
            (object) [
                'column_name' => 'content',
                'column_type' => 'text',
                'form_component' => 'RichEditor',
                'form_options' => null,
            ],
        ]);

        $this->assertStringContainsString('stripHtmlSummary(cellValue, 48)', $body);
    }

    public function test_builds_icon_picker_column_with_cell_icon(): void
    {
        $body = $this->invokeBuildListColumnsBody([
            (object) [
                'column_name' => 'icon',
                'column_type' => 'varchar(100)',
                'form_component' => 'IconPicker',
                'form_options' => null,
            ],
        ]);

        $this->assertStringContainsString("name: 'CellIcon'", $body);
        $this->assertStringContainsString('getIcon: (row: DemoRow) => row.icon', $body);
    }

    /**
     * @param  list<object>  $listColumns
     */
    private function invokeBuildListColumnsBody(array $listColumns): string
    {
        $service = (new \ReflectionClass(CodegenService::class))->newInstanceWithoutConstructor();

        $method = (new \ReflectionClass($service))->getMethod('buildListColumnsBody');
        $method->setAccessible(true);

        return $method->invoke(
            $service,
            collect($listColumns),
            'Demo',
            'DEMO_PERMISSIONS',
            'demo.entity',
            null,
        );
    }
}
