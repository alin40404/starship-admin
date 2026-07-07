import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { DemoArticleRow } from '#/api/system/demo-article';

import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import { SYSTEM_DEMO_ARTICLES_PERMISSIONS } from '#/constants/demo-article-permissions';

export type DemoArticleAccessChecker = {
  can: (code: string) => boolean;
  canAny: (codes: string[]) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '标题' },
      fieldName: 'title',
      label: '标题',
    },
    {
      component: 'Select',
      componentProps: { class: 'w-full', placeholder: '状态:1启用,0禁用' },
      fieldName: 'status',
      label: '状态:1启用,0禁用',
    },
  ];
}

export function buildDemoArticleMobileCrudConfig(params: {
  columns: CrudGridColumn<DemoArticleRow>[];
  query: MobileCrudListConfig<DemoArticleRow>['query'];
}): MobileCrudListConfig<DemoArticleRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useGridFormSchema(),
      keywordFields: ['title'],
      placeholder: '搜索演示文章',
    },
    selection: {
      enabled: true,
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<DemoArticleRow>,
  onStatusBeforeChange: (newVal: number, row: DemoArticleRow) => Promise<boolean>,
  access: DemoArticleAccessChecker,
): CrudGridColumn<DemoArticleRow>[] {
  const canUpdate = access.can(SYSTEM_DEMO_ARTICLES_PERMISSIONS.UPDATE);

  return [
    { align: 'center', fixed: 'left', type: 'checkbox', width: 48 },
    {
      align: 'left',
      field: 'title',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: '标题',
    },
    {
      align: 'left',
      field: 'author',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: '作者',
    },
    {
      align: 'left',
      field: 'content',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: '内容',
    },
    {
      field: 'status',
      sortable: true,
      title: '状态:1启用,0禁用',
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: DemoArticleRow) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({ disabled: !canUpdate }),
        },
        name: 'CellSwitch',
      },
    },
    {
      align: 'left',
      field: 'sort',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: '排序',
    },
    {
      align: 'left',
      field: 'remark',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: '备注',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'title',
          nameTitle: '演示文章',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(SYSTEM_DEMO_ARTICLES_PERMISSIONS.UPDATE),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(SYSTEM_DEMO_ARTICLES_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 140,
    },
  ] as CrudGridColumn<DemoArticleRow>[];
}

export function buildDemoArticleFormSchema(readonly = false): VbenFormSchema[] {
  const disabled = readonly;
  return [

    {
      component: '{{ $column->form_component === 'Switch' ? 'RadioGroup' : $column->form_component }}',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'title',
      label: '标题',
    },

    {
      component: '{{ $column->form_component === 'Switch' ? 'RadioGroup' : $column->form_component }}',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'author',
      label: '作者',
    },

    {
      component: '{{ $column->form_component === 'Switch' ? 'RadioGroup' : $column->form_component }}',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'content',
      label: '内容',
    },

    {
      component: '{{ $column->form_component === 'Switch' ? 'RadioGroup' : $column->form_component }}',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'status',
      label: '状态:1启用,0禁用',
    },

    {
      component: '{{ $column->form_component === 'Switch' ? 'RadioGroup' : $column->form_component }}',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'sort',
      label: '排序',
    },

    {
      component: '{{ $column->form_component === 'Switch' ? 'RadioGroup' : $column->form_component }}',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'remark',
      label: '备注',
    },

  ];
}
