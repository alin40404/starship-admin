import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { AreaTreeNode } from '#/api/system/region';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { REGION_PERMISSIONS } from '#/constants/region-permissions';
import { $t } from '#/locales';

export type AreaAccessChecker = {
  can: (code: string) => boolean;
};

export function getAreaLevelOptions() {
  return [
    { label: $t('system.platform.region.levelOptions.province'), value: 1 },
    { label: $t('system.platform.region.levelOptions.city'), value: 2 },
    { label: $t('system.platform.region.levelOptions.district'), value: 3 },
  ] as const;
}

export function getAreaLevelTagOptions() {
  return [
    {
      color: 'processing',
      label: $t('system.platform.region.levelOptions.province'),
      value: 1,
    },
    {
      color: 'success',
      label: $t('system.platform.region.levelOptions.city'),
      value: 2,
    },
    {
      color: 'default',
      label: $t('system.platform.region.levelOptions.district'),
      value: 3,
    },
  ];
}

export function formatAreaLevel(level: AreaTreeNode['level']) {
  if (level === 1) return $t('system.platform.region.levelOptions.province');
  if (level === 2) return $t('system.platform.region.levelOptions.city');
  if (level === 3) return $t('system.platform.region.levelOptions.district');
  return TABLE_CELL_EMPTY;
}

export function useAreaGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('system.platform.region.searchName'),
      },
      fieldName: 'name',
      label: $t('system.platform.region.name'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...getAreaLevelOptions()],
        placeholder: $t('system.platform.region.searchLevel'),
      },
      fieldName: 'level',
      label: $t('system.platform.region.level'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('system.platform.region.searchPinyin'),
      },
      fieldName: 'pinyin',
      label: $t('system.platform.region.pinyin'),
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
      title: $t('system.platform.region.name'),
      treeNode: true,
    },
    {
      align: 'left',
      field: 'shortname',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 100,
      title: $t('system.platform.region.shortname'),
    },
    {
      align: 'center',
      cellRender: { name: 'CellTag', options: getAreaLevelTagOptions() },
      field: 'level',
      title: $t('system.platform.region.level'),
      width: 88,
    },
    {
      align: 'left',
      field: 'mergename',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 220,
      title: $t('system.platform.region.mergename'),
    },
    {
      align: 'left',
      field: 'pinyin',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: $t('system.platform.region.pinyin'),
    },
    {
      field: 'code',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 90,
      title: $t('system.platform.region.code'),
    },
    {
      field: 'zip',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 90,
      title: $t('system.platform.region.zip'),
    },
    {
      align: 'center',
      field: 'first',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      title: $t('system.platform.region.first'),
      width: 80,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('system.platform.region.entity'),
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
      label: $t('system.platform.region.parent'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'name',
      label: $t('system.platform.region.name'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'shortname',
      label: $t('system.platform.region.shortname'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'mergename',
      label: $t('system.platform.region.mergename'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...getAreaLevelOptions()],
        placeholder: $t('system.platform.region.levelAuto'),
      },
      fieldName: 'level',
      label: $t('system.platform.region.level'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: true,
        placeholder: $t('system.platform.region.pinyinAuto'),
        readonly: true,
      },
      fieldName: 'pinyin',
      label: $t('system.platform.region.pinyin'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'code',
      label: $t('system.platform.region.areaCode'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'zip',
      label: $t('system.platform.region.zip'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: true,
        placeholder: $t('system.platform.region.firstAuto'),
        readonly: true,
      },
      fieldName: 'first',
      label: $t('system.platform.region.first'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'lng',
      label: $t('system.platform.region.lng'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'lat',
      label: $t('system.platform.region.lat'),
    },
  ];
}
