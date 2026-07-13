import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { ProductsRow } from '#/api/generator/products';

import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import { GENERATOR_PRODUCTS_PERMISSIONS } from '#/constants/products-permissions';
import { DICT_TYPE_CODE, mapDictItemOptions } from '#/views/platform/dict/i18n';

import { fetchCategoryOptionsTree, mapCategoryForTreeSelect } from '#/api/system/category';
import { fetchProductsPage } from '#/api/generator/products';


export type ProductsAccessChecker = {
  can: (code: string) => boolean;
  canAny: (codes: string[]) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: $t('generator.products.columns.name') },
      fieldName: 'name',
      label: $t('generator.products.columns.name'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: $t('generator.products.columns.code') },
      fieldName: 'code',
      label: $t('generator.products.columns.code'),
    },
    {
      component: 'Select',
      componentProps: { class: 'w-full', placeholder: $t('generator.products.columns.tag') },
      fieldName: 'tag',
      label: $t('generator.products.columns.tag'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [
        { label: $t('system.shared.status.normal'), value: 'normal' },
        { label: $t('system.shared.status.hidden'), value: 'hidden' },
        ],
        placeholder: $t('generator.products.columns.status'),
      },
      fieldName: 'status',
      label: $t('generator.products.columns.status'),
    },
  ];
}

export function buildProductsMobileCrudConfig(params: {
  columns: CrudGridColumn<ProductsRow>[];
  query: MobileCrudListConfig<ProductsRow>['query'];
}): MobileCrudListConfig<ProductsRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useGridFormSchema(),
      keywordFields: ['name', 'code'],
      placeholder: $t('generator.products.searchPlaceholder'),
    },
    selection: {
      enabled: true,
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<ProductsRow>,
  onStatusBeforeChange: (newVal: ProductsRow['status'], row: ProductsRow) => Promise<boolean>,
  access: ProductsAccessChecker,
): CrudGridColumn<ProductsRow>[] {
  const canUpdate = access.can(GENERATOR_PRODUCTS_PERMISSIONS.UPDATE);

  return [
    { align: 'center', fixed: 'left', type: 'checkbox', width: 48 },
    {
      align: 'left',
      field: 'name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.name'),
    },
    {
      align: 'left',
      field: 'code',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.code'),
    },
    {
      align: 'left',
      field: 'secret',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.secret'),
    },
    {
      align: 'left',
      field: 'price',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.price'),
    },
    {
      align: 'left',
      field: 'stock',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.stock'),
    },
    {
      align: 'left',
      field: 'sort',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.sort'),
    },
    {
      align: 'left',
      field: 'product_type',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.product_type'),
    },
    {
      align: 'left',
      field: 'flags',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.flags'),
    },
    {
      align: 'left',
      field: 'is_featured',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.is_featured'),
    },
    {
      align: 'left',
      field: 'score',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.score'),
    },
    {
      align: 'left',
      field: 'category_id',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.category_id'),
    },
    {
      align: 'left',
      field: 'parent_id',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.parent_id'),
    },
    {
      align: 'center',
      field: 'cover',
      cellRender: {
        name: 'CellImage',
        props: { height: 40, preview: true, width: 40 },
      },
      title: $t('generator.products.columns.cover'),
      width: 80,
    },
    {
      align: 'left',
      field: 'attachment',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.attachment'),
    },
    {
      align: 'left',
      field: 'brochure',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.brochure'),
    },
    {
      align: 'left',
      field: 'summary',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.summary'),
    },
    {
      align: 'left',
      field: 'content',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.content'),
    },
    {
      align: 'left',
      field: 'icon',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.icon'),
    },
    {
      align: 'left',
      field: 'keywords',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.keywords'),
    },
    {
      align: 'left',
      field: 'tag',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.tag'),
    },
    {
      align: 'left',
      field: 'color',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.color'),
    },
    {
      field: 'published_at',
      formatter: ({ row }: { row: ProductsRow }) =>
        row.published_at ? formatDateTime(row.published_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      sortable: true,
      title: $t('generator.products.columns.published_at'),
    },
    {
      align: 'left',
      field: 'sale_date',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.sale_date'),
    },
    {
      align: 'left',
      field: 'sale_time',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.sale_time'),
    },
    {
      align: 'left',
      field: 'remark',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('generator.products.columns.remark'),
    },
    {
      field: 'status',
      sortable: true,
      title: $t('generator.products.columns.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: ProductsRow) {
            return onStatusBeforeChange(newVal as ProductsRow['status'], row);
          },
          getSwitchProps: () => ({
            checkedChildren: $t('system.shared.status.normal'),
            checkedValue: 'normal',
            disabled: !canUpdate,
            unCheckedChildren: $t('system.shared.status.hidden'),
            unCheckedValue: 'hidden',
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('generator.products.title'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(GENERATOR_PRODUCTS_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(GENERATOR_PRODUCTS_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 140,
    },
  ] as CrudGridColumn<ProductsRow>[];
}

async function loadCategoryIdOptions() {
  const data = await fetchCategoryOptionsTree();
  return mapCategoryForTreeSelect(data);
}

async function loadParentIdOptions() {
  const page = await fetchProductsPage({ page: 1, per_page: 500 });
  return page.data.map((row) => ({
    label: String(row.name ?? row.id),
    value: row.id,
    children: [],
  }));
}



export type ProductsFormSchemaParams = {
  readonly?: boolean;
  onImageChange?: (event: unknown) => void;
  onImageUpload?: (options: unknown) => Promise<void> | void;
  onAttachmentUpload?: (options: unknown) => Promise<void> | void;
  onFileUpload?: (options: unknown) => Promise<void> | void;

};

export function buildProductsFormSchema(
  params: ProductsFormSchemaParams = {},
): VbenFormSchema[] {
  const readonly = params.readonly ?? false;
  const disabled = readonly;
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'name',
      label: $t('generator.products.columns.name'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'code',
      label: $t('generator.products.columns.code'),
    },
    {
      component: 'InputPassword',
      componentProps: { class: 'w-full', disabled, autocomplete: 'new-password' },
      fieldName: 'secret',
      label: $t('generator.products.columns.secret'),
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', disabled, min: 0 },
      defaultValue: 0,
      fieldName: 'price',
      label: $t('generator.products.columns.price'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', disabled, min: 0 },
      defaultValue: 0,
      fieldName: 'stock',
      label: $t('generator.products.columns.stock'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', disabled, min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: $t('generator.products.columns.sort'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        disabled,
        optionType: 'button',
        options: [
          { label: '实物', value: 'physical' },
          { label: '虚拟', value: 'virtual' },
        ],
      },
      fieldName: 'product_type',
      label: $t('generator.products.columns.product_type'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'CheckboxGroup',
      componentProps: {
        class: 'w-full',
        disabled,
        options: [
          { label: '热门', value: 'hot' },
          { label: '新品', value: 'new' },
          { label: '推荐', value: 'recommend' },
        ],
      },
      fieldName: 'flags',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('generator.products.columns.flags'),
    },
    {
      component: 'Switch',
      componentProps: { class: 'w-auto', disabled },
      defaultValue: false,
      fieldName: 'is_featured',
      label: $t('generator.products.columns.is_featured'),
    },
    {
      component: 'Rate',
      componentProps: {
        disabled,
        count: 5,
      },
      defaultValue: 0,
      fieldName: 'score',
      label: $t('generator.products.columns.score'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: loadCategoryIdOptions,
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
        placeholder: $t('generator.products.columns.category_id'),
        disabled,
      },
      fieldName: 'category_id',
      label: $t('generator.products.columns.category_id'),
    },
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: loadParentIdOptions,
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
        placeholder: $t('generator.products.columns.parent_id'),
        disabled,
      },
      defaultValue: 0,
      fieldName: 'parent_id',
      label: $t('generator.products.columns.parent_id'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'PictureUploadField',
      componentProps: {
        customRequest: params.onImageUpload,
        handleChange: params.onImageChange,
        maxCount: 1,
        disabled,
      },
      fieldName: 'cover',
      formItemClass: 'picture-upload-form-item items-start',
      label: $t('generator.products.columns.cover'),
    },
    {
      component: 'AttachmentField',
      componentProps: {
        customRequest: params.onAttachmentUpload,
        maxCount: 1,
        disabled,
      },
      fieldName: 'attachment',
      formItemClass: 'col-span-full items-start',
      label: $t('generator.products.columns.attachment'),
    },
    {
      component: 'Upload',
      componentProps: {
        customRequest: params.onFileUpload,
        maxCount: 1,
        disabled,
      },
      fieldName: 'brochure',
      formItemClass: 'col-span-full items-start',
      label: $t('generator.products.columns.brochure'),
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', disabled, rows: 4 },
      formItemClass: 'col-span-full w-full items-start',
      fieldName: 'summary',
      label: $t('generator.products.columns.summary'),
    },
    {
      component: 'RichEditor',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'content',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('generator.products.columns.content'),
    },
    {
      component: 'IconPicker',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'icon',
      label: $t('generator.products.columns.icon'),
    },
    {
      component: 'AutoComplete',
      componentProps: {
        class: 'w-full',
        disabled,
        options: [
          { label: '热门', value: '热门' },
          { label: '新品', value: '新品' },
          { label: '限时', value: '限时' },
          { label: '包邮', value: '包邮' },
        ],
      },
      fieldName: 'keywords',
      label: $t('generator.products.columns.keywords'),
    },
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: mapDictItemOptions(DICT_TYPE_CODE.productTag, []),
        placeholder: $t('generator.products.columns.tag'),
        disabled,
      },
      fieldName: 'tag',
      label: $t('generator.products.columns.tag'),
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        disabled,
        optionType: 'button',
        options: [
          { label: '红', value: 'red' },
          { label: '蓝', value: 'blue' },
          { label: '绿', value: 'green' },
        ],
      },
      fieldName: 'color',
      label: $t('generator.products.columns.color'),
    },
    {
      component: 'DatePicker',
      componentProps: {
        class: 'w-full',
        disabled,
        showTime: true,
        valueFormat: 'YYYY-MM-DD HH:mm:ss',
      },
      fieldName: 'published_at',
      label: $t('generator.products.columns.published_at'),
    },
    {
      component: 'DatePicker',
      componentProps: {
        class: 'w-full',
        disabled,
        showTime: false,
        valueFormat: 'YYYY-MM-DD',
      },
      fieldName: 'sale_date',
      label: $t('generator.products.columns.sale_date'),
    },
    {
      component: 'TimePicker',
      componentProps: {
        class: 'w-full',
        disabled,
        valueFormat: 'HH:mm:ss',
      },
      fieldName: 'sale_time',
      label: $t('generator.products.columns.sale_time'),
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', disabled, rows: 4 },
      formItemClass: 'col-span-full w-full items-start',
      fieldName: 'remark',
      label: $t('generator.products.columns.remark'),
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        disabled,
        optionType: 'button',
        options: [
        { label: $t('system.shared.status.normal'), value: 'normal' },
        { label: $t('system.shared.status.hidden'), value: 'hidden' },
        ],
      },
      defaultValue: 'normal',
      fieldName: 'status',
      label: $t('generator.products.columns.status'),
    },
  ];
}
