import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { DepartmentTreeNode } from '#/api/system/department';
import { fetchAdminPage } from '#/api/system/admin';
import { fetchDepartmentOptionsTree, mapDeptForTreeSelect } from '#/api/system/department';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { DEPARTMENT_PERMISSIONS } from '#/constants/department-permissions';
import { $t } from '#/locales';

export type DepartmentAccessChecker = {
  can: (code: string) => boolean;
};

function getStatusRadioOptions() {
  return [
    { label: $t('system.shared.status.enable'), value: 1 },
    { label: $t('system.shared.status.disable'), value: 0 },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<DepartmentTreeNode>,
  onStatusBeforeChange: (
    newVal: number,
    row: DepartmentTreeNode,
  ) => Promise<boolean>,
  access: DepartmentAccessChecker,
): VxeTableGridColumns {
  const canStatus = access.can(DEPARTMENT_PERMISSIONS.STATUS);
  const canSort = access.can(DEPARTMENT_PERMISSIONS.SORT);

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
      title: $t('org.department.columnName'),
      treeNode: true,
    },
    {
      align: 'left',
      field: 'leader_name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 100,
      title: $t('org.department.leader'),
    },
    {
      field: 'phone',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: $t('org.department.phone'),
    },
    {
      align: 'center',
      className: canSort ? 'dept-sort-editable-col' : undefined,
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
      title: $t('org.shared.sort'),
      width: 100,
    },
    {
      field: 'status',
      title: $t('org.shared.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: DepartmentTreeNode) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({ disabled: !canStatus }),
        },
        name: 'CellSwitch',
      },
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('org.department.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'append',
            show: () => access.can(DEPARTMENT_PERMISSIONS.APPEND),
            text: $t('system.shared.appendChild'),
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(DEPARTMENT_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(DEPARTMENT_PERMISSIONS.DELETE),
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

async function loadAdminOptions() {
  const page = await fetchAdminPage({ page: 1, per_page: 500 });
  return page.data.map((a) => ({
    label: `${a.nickname || a.username} (${a.username})`,
    value: a.id,
  }));
}

export function buildDepartmentFormSchema(params: {
  parentTreeLoader: () => Promise<ReturnType<typeof mapDeptForTreeSelect>>;
}): VbenFormSchema[] {
  return [
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: params.parentTreeLoader,
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        placeholder: $t('org.department.parentPlaceholder'),
        showSearch: true,
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
      },
      defaultValue: 0,
      fieldName: 'parent_id',
      label: $t('org.department.parent'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'name',
      label: $t('org.department.columnName'),
      rules: 'required',
    },
    {
      component: 'ApiSelect',
      componentProps: {
        allowClear: true,
        api: loadAdminOptions,
        class: 'w-full',
        optionFilterProp: 'label',
        placeholder: $t('org.department.leaderPlaceholder'),
        showSearch: true,
      },
      fieldName: 'leader_id',
      label: $t('org.department.leader'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'phone',
      label: $t('org.department.phone'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'email',
      label: $t('org.shared.email'),
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: $t('org.shared.sort'),
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: getStatusRadioOptions(),
      },
      defaultValue: 1,
      fieldName: 'status',
      label: $t('org.shared.status'),
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', rows: 2 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('org.shared.remark'),
    },
  ];
}

export async function loadDepartmentTreeForParent(
  excludeId?: number,
): Promise<ReturnType<typeof mapDeptForTreeSelect>> {
  const tree = await fetchDepartmentOptionsTree(true);
  const rootOption = {
    label: $t('org.department.rootOption'),
    value: 0,
  };
  if (!excludeId) {
    return [rootOption, ...mapDeptForTreeSelect(tree)];
  }
  function filter(nodes: DepartmentTreeNode[]): DepartmentTreeNode[] {
    return nodes
      .filter((n) => n.id !== excludeId)
      .map((n) => ({
        ...n,
        children: n.children?.length ? filter(n.children) : [],
      }));
  }
  return [rootOption, ...mapDeptForTreeSelect(filter(tree))];
}
