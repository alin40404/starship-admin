import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { CmsCategoryTreeNode } from '#/features/cms/api';
import { loadCmsCategoryTreeForParent } from '#/features/cms/api';
import { CMS_PERMISSIONS } from '#/features/cms/constants/cms-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { $t } from '#/locales';

export type CmsCategoryAccessChecker = {
  can: (code: string) => boolean;
};

export function getCategoryFlagOptions() {
  return [
    { label: $t('platform.category.flagOptions.hot'), value: 'hot' },
    { label: $t('platform.category.flagOptions.index'), value: 'index' },
    {
      label: $t('platform.category.flagOptions.recommend'),
      value: 'recommend',
    },
  ];
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.category.searchPlaceholder'),
      },
      fieldName: 'search',
      label: $t('platform.category.keyword'),
    },
  ];
}

export function buildFormSchema(params: {
  excludeId?: number;
  onImageChange?: (event: unknown) => void;
  onImageUpload?: (options: unknown) => Promise<void> | void;
}): VbenFormSchema[] {
  const { excludeId, onImageChange, onImageUpload } = params;

  return [
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: async () => loadCmsCategoryTreeForParent(excludeId),
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        placeholder: $t('platform.category.parentPlaceholder'),
        showSearch: true,
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
      },
      defaultValue: 0,
      fieldName: 'parent_id',
      label: $t('platform.category.parent'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.category.namePlaceholder'),
      },
      fieldName: 'name',
      label: $t('platform.category.name'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.category.nicknamePlaceholder'),
      },
      fieldName: 'nickname',
      label: $t('platform.category.nickname'),
      rules: 'required',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        mode: 'multiple',
        options: getCategoryFlagOptions(),
        placeholder: $t('platform.category.flagPlaceholder'),
      },
      fieldName: 'flag',
      label: $t('platform.category.flag'),
    },
    {
      component: 'PictureUploadField',
      componentProps: {
        customRequest: onImageUpload,
        handleChange: onImageChange,
        maxCount: 1,
      },
      fieldName: 'image',
      formItemClass: 'picture-upload-form-item items-start',
      label: $t('platform.category.image'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'keywords',
      label: $t('platform.category.keywords'),
    },
    {
      component: 'Textarea',
      componentProps: { rows: 2 },
      fieldName: 'description',
      formItemClass: 'items-start',
      label: $t('platform.category.description'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.category.diynamePlaceholder'),
      },
      fieldName: 'diyname',
      label: $t('platform.category.diyname'),
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: $t('platform.category.sort'),
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: [
          { label: $t('system.shared.status.normal'), value: 'normal' },
          { label: $t('system.shared.status.hidden'), value: 'hidden' },
        ],
      },
      defaultValue: 'normal',
      fieldName: 'status',
      label: $t('platform.category.status'),
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<CmsCategoryTreeNode>,
  onStatusBeforeChange: (
    newVal: CmsCategoryTreeNode['status'],
    row: CmsCategoryTreeNode,
  ) => Promise<boolean>,
  access: CmsCategoryAccessChecker,
): VxeTableGridColumns<CmsCategoryTreeNode> {
  const canStatus = access.can(CMS_PERMISSIONS.CATEGORIES_STATUS);
  const canSort = access.can(CMS_PERMISSIONS.CATEGORIES_SORT);

  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', title: 'ID', width: 72 },
    {
      align: 'left',
      field: 'name',
      minWidth: 180,
      title: $t('platform.category.name'),
      treeNode: true,
    },
    {
      align: 'left',
      field: 'nickname',
      minWidth: 120,
      title: $t('platform.category.nickname'),
    },
    {
      align: 'center',
      field: 'flag_labels',
      formatter: ({ cellValue }) =>
        Array.isArray(cellValue) && cellValue.length > 0
          ? cellValue.join('、')
          : TABLE_CELL_EMPTY,
      minWidth: 120,
      title: $t('platform.category.flag'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          getSrc: (row: CmsCategoryTreeNode) => row.image,
        },
        name: 'CellImage',
        props: { height: 40, width: 40 },
      },
      field: 'image',
      title: $t('platform.category.image'),
      width: 80,
    },
    {
      align: 'center',
      className: canSort ? 'category-sort-editable-col' : undefined,
      editRender: canSort
        ? {
            name: 'VxeNumberInput',
            props: {
              min: 0,
              type: 'integer',
            },
            events: {
              keydown(params, event: KeyboardEvent) {
                if (event.key !== 'Enter') {
                  return;
                }
                event.preventDefault();
                (event.target as HTMLElement | null)?.blur?.();
                void params.$table.clearEdit();
              },
            },
          }
        : undefined,
      field: 'sort',
      title: $t('platform.category.sort'),
      width: 100,
    },
    {
      field: 'status',
      title: $t('platform.category.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: CmsCategoryTreeNode) {
            return onStatusBeforeChange(
              newVal as CmsCategoryTreeNode['status'],
              row,
            );
          },
          getSwitchProps: () => ({
            checkedChildren: $t('system.shared.status.normal'),
            checkedValue: 'normal',
            disabled: !canStatus,
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
          nameTitle: $t('platform.category.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'append',
            show: () => access.can(CMS_PERMISSIONS.CATEGORIES_APPEND),
            text: $t('system.shared.appendChild'),
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(CMS_PERMISSIONS.CATEGORIES_UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(CMS_PERMISSIONS.CATEGORIES_DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 220,
    },
  ];
}
