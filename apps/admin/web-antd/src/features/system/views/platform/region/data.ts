import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { AreaTreeNode } from '#/features/system/api/region';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { REGION_PERMISSIONS } from '#/features/system/constants/region-permissions';
import { $t } from '#/locales';

export type AreaAccessChecker = {
  can: (code: string) => boolean;
};

export function getAreaLevelOptions() {
  return [
    { label: $t('platform.region.levelOptions.province'), value: 1 },
    { label: $t('platform.region.levelOptions.city'), value: 2 },
    { label: $t('platform.region.levelOptions.district'), value: 3 },
  ] as const;
}

export function getAreaLevelTagOptions() {
  return [
    {
      color: 'processing',
      label: $t('platform.region.levelOptions.province'),
      value: 1,
    },
    {
      color: 'success',
      label: $t('platform.region.levelOptions.city'),
      value: 2,
    },
    {
      color: 'default',
      label: $t('platform.region.levelOptions.district'),
      value: 3,
    },
  ];
}

export function formatAreaLevel(level: AreaTreeNode['level']) {
  if (level === 1) return $t('platform.region.levelOptions.province');
  if (level === 2) return $t('platform.region.levelOptions.city');
  if (level === 3) return $t('platform.region.levelOptions.district');
  return TABLE_CELL_EMPTY;
}

export function useAreaGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.region.searchName'),
      },
      fieldName: 'name',
      label: $t('platform.region.name'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...getAreaLevelOptions()],
        placeholder: $t('platform.region.searchLevel'),
      },
      fieldName: 'level',
      label: $t('platform.region.level'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.region.searchPinyin'),
      },
      fieldName: 'pinyin',
      label: $t('platform.region.pinyin'),
    },
  ];
}

export function useAreaColumns(
  onActionClick: OnActionClickFn<AreaTreeNode>,
  access: AreaAccessChecker,
): VxeTableGridColumns<AreaTreeNode> {
  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    {
      align: 'left',
      field: 'name',
      minWidth: 180,
      title: $t('platform.region.name'),
      treeNode: true,
    },
    {
      align: 'left',
      field: 'shortname',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 100,
      title: $t('platform.region.shortname'),
    },
    {
      align: 'center',
      cellRender: { name: 'CellTag', options: getAreaLevelTagOptions() },
      field: 'level',
      title: $t('platform.region.level'),
      width: 88,
    },
    {
      align: 'left',
      field: 'mergename',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 220,
      title: $t('platform.region.mergename'),
    },
    {
      align: 'left',
      field: 'pinyin',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: $t('platform.region.pinyin'),
    },
    {
      field: 'code',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 90,
      title: $t('platform.region.code'),
    },
    {
      field: 'zip',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 90,
      title: $t('platform.region.zip'),
    },
    {
      align: 'center',
      field: 'first',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      title: $t('platform.region.first'),
      width: 80,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('platform.region.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'append',
            show: () => access.can(REGION_PERMISSIONS.APPEND),
            text: $t('system.shared.appendChild'),
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(REGION_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(REGION_PERMISSIONS.DELETE),
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

export function buildAreaFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      controlClass: 'w-full min-w-0',
      fieldName: 'pid',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('platform.region.parent'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'name',
      label: $t('platform.region.name'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'shortname',
      label: $t('platform.region.shortname'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'mergename',
      label: $t('platform.region.mergename'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...getAreaLevelOptions()],
        placeholder: $t('platform.region.levelAuto'),
      },
      fieldName: 'level',
      label: $t('platform.region.level'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: true,
        placeholder: $t('platform.region.pinyinAuto'),
        readonly: true,
      },
      fieldName: 'pinyin',
      label: $t('platform.region.pinyin'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'code',
      label: $t('platform.region.areaCode'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'zip',
      label: $t('platform.region.zip'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: true,
        placeholder: $t('platform.region.firstAuto'),
        readonly: true,
      },
      fieldName: 'first',
      label: $t('platform.region.first'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'lng',
      label: $t('platform.region.lng'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'lat',
      label: $t('platform.region.lat'),
    },
  ];
}
