<?php

namespace Modules\Generator\App\Support;

/**
 * 生成器支持的表单组件清单（与 web-antd adapter/component 对齐）。
 */
final class FormComponentCatalog
{
    /** @return list<string> */
    public static function all(): array
    {
        return [
            'Input',
            'InputPassword',
            'InputNumber',
            'Textarea',
            'Select',
            'RadioGroup',
            'Checkbox',
            'CheckboxGroup',
            'Switch',
            'Rate',
            'DatePicker',
            'TimePicker',
            'RangePicker',
            'AutoComplete',
            'Mentions',
            'IconPicker',
            'TreeSelect',
            'ApiSelect',
            'ApiTreeSelect',
            'ApiCascader',
            'PictureUploadField',
            'AttachmentField',
            'RichEditor',
        ];
    }
}
