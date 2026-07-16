import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { ArticleRow } from '#/features/cms/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { CMS_PERMISSIONS } from '#/features/cms/constants/cms-permissions';
import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

export type CmsAccessChecker = {
  can: (code: string) => boolean;
};

const ARTICLE_STATUS: Record<number, string> = {
  0: '草稿',
  1: '已发布',
};

function getStatusOptions() {
  return Object.entries(ARTICLE_STATUS).map(([value, label]) => ({
    label,
    value: Number(value),
  }));
}

export function useGridFormSchema(
  categoryOptions: { label: string; value: number }[],
): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '标题/标识' },
      fieldName: 'keyword',
      label: '关键词',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: categoryOptions,
      },
      fieldName: 'category_id',
      label: '栏目',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: getStatusOptions(),
      },
      fieldName: 'status',
      label: '状态',
    },
  ];
}

export function buildArticleFormSchema(
  categoryOptions: { label: string; value: number }[],
): VbenFormSchema[] {
  return [
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: categoryOptions,
      },
      fieldName: 'category_id',
      label: '栏目',
    },
    { component: 'Input', fieldName: 'title', label: '标题', rules: 'required' },
    { component: 'Input', fieldName: 'slug', label: '标识 slug', rules: 'required' },
    {
      component: 'Textarea',
      fieldName: 'summary',
      formItemClass: 'col-span-full',
      label: '摘要',
    },
    {
      component: 'Textarea',
      componentProps: { rows: 8 },
      fieldName: 'content',
      formItemClass: 'col-span-full',
      label: '正文',
    },
    { component: 'Input', fieldName: 'cover', label: '封面图 URL' },
    {
      component: 'RadioGroup',
      componentProps: { options: getStatusOptions() },
      defaultValue: 0,
      fieldName: 'status',
      label: '状态',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<ArticleRow>,
  access: CmsAccessChecker,
  onStatusBeforeChange: (newVal: number, row: ArticleRow) => Promise<boolean>,
): CrudGridColumn<ArticleRow>[] {
  return [
    { field: 'id', title: 'ID', width: 72 },
    { field: 'title', minWidth: 180, title: '标题' },
    { field: 'slug', minWidth: 140, title: '标识' },
    {
      field: 'status',
      formatter: ({ cellValue }) =>
        ARTICLE_STATUS[Number(cellValue)] ?? TABLE_CELL_EMPTY,
      title: '状态',
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: ArticleRow) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({
            disabled: !access.can(CMS_PERMISSIONS.ARTICLES_STATUS),
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      field: 'published_at',
      formatter: ({ row }: { row: ArticleRow }) =>
        row.published_at ? formatDateTime(row.published_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      title: '发布时间',
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: ArticleRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      title: '创建时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'title',
          nameTitle: '文章',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(CMS_PERMISSIONS.ARTICLES_UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            show: () => access.can(CMS_PERMISSIONS.ARTICLES_DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      field: 'operation',
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 140,
    },
  ];
}
