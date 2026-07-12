import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { RoleRow } from '#/api/system/role';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { ROLE_PERMISSIONS } from '#/constants/role-permissions';
import { $t } from '#/locales';

/** 与后端 data_scope 1–5 一致：序号与选项顺序相同（1全部 2本部门 3本部门及下级 4仅本人 5自定义） */
export const DATA_SCOPE_CUSTOM = 5;

/** 角色权限树顶部「全部」行图标 */
export const ROLE_PERMISSION_CHECK_ALL_ICON = 'lucide:layout-grid';

type DeptCascaderOption = {
  children?: DeptCascaderOption[];
  label: string;
  value: number;
};

/** 部门 ID 列表 → Cascader 多选路径 */
export function deptIdsToCascaderPaths(
  deptIds: number[],
  options: DeptCascaderOption[],
): number[][] {
  function findPath(
    nodes: DeptCascaderOption[],
    targetId: number,
    path: number[] = [],
  ): number[] | null {
    for (const node of nodes) {
      const next = [...path, node.value];
      if (node.value === targetId) {
        return next;
      }
      if (node.children?.length) {
        const found = findPath(node.children, targetId, next);
        if (found) {
          return found;
        }
      }
    }
    return null;
  }

  return deptIds
    .map((id) => findPath(options, id))
    .filter((path): path is number[] => path !== null);
}

/** Cascader 多选路径 → 部门 ID 列表（取每条路径末级） */
export function cascaderPathsToDeptIds(paths: number[][]): number[] {
  return [
    ...new Set(
      paths
        .map((path) => path[path.length - 1])
        .filter((id): id is number => id != null),
    ),
  ];
}

export function getDataScopeOptions() {
  return [
    { label: $t('permission.role.dataScopeOptions.all'), value: 1 },
    { label: $t('permission.role.dataScopeOptions.dept'), value: 2 },
    {
      label: $t('permission.role.dataScopeOptions.deptAndChild'),
      value: 3,
    },
    { label: $t('permission.role.dataScopeOptions.self'), value: 4 },
    { label: $t('permission.role.dataScopeOptions.custom'), value: 5 },
  ];
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('permission.role.searchName'),
      },
      fieldName: 'name',
      label: $t('permission.role.name'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('permission.role.code'),
      },
      fieldName: 'code',
      label: $t('permission.role.code'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [
          { label: $t('system.shared.status.enable'), value: 1 },
          { label: $t('system.shared.status.disable'), value: 0 },
        ],
        placeholder: $t('permission.role.status'),
      },
      fieldName: 'status',
      label: $t('permission.role.status'),
    },
  ];
}

export type RoleAccessChecker = {
  can: (code: string) => boolean;
  canAny: (codes: string[]) => boolean;
};

export function buildRoleMobileCrudConfig(params: {
  columns: CrudGridColumn<RoleRow>[];
  query: MobileCrudListConfig<RoleRow>['query'];
}): MobileCrudListConfig<RoleRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useGridFormSchema(),
      keywordFields: ['name', 'code'],
      placeholder: $t('permission.role.searchKeyword'),
    },
    selection: {
      checkable: (row) => row.code !== 'super_admin',
      enabled: true,
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<RoleRow>,
  onStatusBeforeChange: (newVal: number, row: RoleRow) => Promise<boolean>,
  access: RoleAccessChecker,
): CrudGridColumn<RoleRow>[] {
  const canStatus = access.can(ROLE_PERMISSIONS.STATUS);
  const dataScopeOptions = getDataScopeOptions();

  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    {
      field: 'name',
      minWidth: 140,
      sortable: true,
      title: $t('permission.role.name'),
    },
    {
      field: 'code',
      minWidth: 140,
      sortable: true,
      title: $t('permission.role.code'),
    },
    {
      field: 'data_scope',
      formatter: ({ cellValue }) =>
        dataScopeOptions.find((o) => o.value === cellValue)?.label ??
        String(cellValue ?? TABLE_CELL_EMPTY),
      minWidth: 120,
      sortable: true,
      title: $t('permission.role.dataScope'),
    },
    {
      field: 'status',
      sortable: true,
      title: $t('permission.role.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: RoleRow) {
            if (row.code === 'super_admin') {
              return false;
            }
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: (row: RoleRow) => ({
            disabled: row.code === 'super_admin' || !canStatus,
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      field: 'remark',
      minWidth: 160,
      sortable: false,
      title: $t('permission.role.remark'),
    },
    {
      field: 'updated_at',
      minWidth: 170,
      sortable: true,
      title: $t('permission.role.updatedAt'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('permission.role.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'view',
            show: (row: RoleRow) =>
              row.code === 'super_admin' &&
              access.can(ROLE_PERMISSIONS.QUERY),
            text: $t('system.shared.view'),
            type: 'link',
          },
          {
            code: 'edit',
            show: (row: RoleRow) =>
              row.code !== 'super_admin' &&
              access.can(ROLE_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: (row: RoleRow) =>
              row.code !== 'super_admin' &&
              access.can(ROLE_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 180,
    },
  ] as CrudGridColumn<RoleRow>[];
}

export function buildRoleFormSchema(params: {
  isEdit: boolean;
  lockBuiltin: boolean;
  /** 查看模式：全部只读（如超级管理员） */
  readonly?: boolean;
}): VbenFormSchema[] {
  const { isEdit, lockBuiltin, readonly = false } = params;
  const disabled = readonly || lockBuiltin;
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled,
      },
      fieldName: 'name',
      label: $t('permission.role.name'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: disabled || isEdit,
      },
      fieldName: 'code',
      label: $t('permission.role.code'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: !readonly,
        class: 'w-full',
        disabled,
        options: getDataScopeOptions(),
        placeholder: $t('permission.role.dataScope'),
      },
      defaultValue: 1,
      fieldName: 'data_scope',
      label: $t('permission.role.dataScope'),
      rules: readonly ? undefined : 'selectRequired',
    },
    {
      component: 'Input',
      controlClass: 'w-full min-w-0',
      defaultValue: [],
      dependencies: {
        show(values) {
          return Number(values.data_scope) === DATA_SCOPE_CUSTOM;
        },
        triggerFields: ['data_scope'],
      },
      /** 数据部门：由表单插槽渲染 Cascader，与数据范围同一行 */
      fieldName: 'dept_ids',
      formItemClass: 'role-dept-field w-full min-w-0 items-start',
      label: $t('permission.role.dataDept'),
      modelPropName: 'modelValue',
      wrapperClass: 'block w-full min-w-0',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        disabled,
        optionType: 'button',
        options: [
          { label: $t('system.shared.status.enable'), value: 1 },
          { label: $t('system.shared.status.disable'), value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      label: $t('permission.role.status'),
    },
    {
      component: 'Textarea',
      componentProps: {
        class: 'w-full',
        disabled,
        rows: 2,
      },
      fieldName: 'remark',
      formItemClass: 'col-span-full sm:col-span-2 w-full items-start',
      label: $t('permission.role.remark'),
    },
    /** 角色权限：由表单插槽渲染 Vben Tree，值参与 getValues */
    {
      component: 'Input',
      controlClass: 'w-full min-w-0',
      defaultValue: [],
      fieldName: 'menu_ids',
      formItemClass: 'col-span-full sm:col-span-2 w-full min-w-0 items-start',
      label: $t('permission.role.permissions'),
      modelPropName: 'modelValue',
    },
  ];
}
