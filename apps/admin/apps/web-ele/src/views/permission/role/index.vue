<script lang="ts" setup>
import type { MenuNode } from '#/api/system/menu';
import type {
  ExtendedVxeGridApi,
  OnActionClickParams,
  VxeTableGridOptions,
} from '#/adapter/vxe-table';
import type { RoleRow, RoleSavePayload } from '#/api/system/role';

import { computed, nextTick, ref } from 'vue';

import { Page, useVbenDrawer } from '@vben/common-ui';

import { ElMessage, ElMessageBox } from 'element-plus';
import { ElScrollbar, ElTree } from 'element-plus';

import { useVbenForm } from '#/adapter/form';
import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  createRole,
  deleteRole,
  fetchRoleDetail,
  fetchRolePage,
  syncRoleMenus,
  updateRole,
} from '#/api/system/role';
import { fetchMenuTree } from '#/api/system/menu';

import { buildRoleFormSchema, useColumns, useGridFormSchema } from './data';

const editingId = ref<number | null>(null);
/** 抽屉：新增 / 编辑 / 仅查看（超级管理员） */
const drawerMode = ref<'create' | 'edit' | 'view'>('create');

const menuTree = ref<MenuNode[]>([]);
const menuTreeRef = ref<InstanceType<typeof ElTree>>();
const treeRenderKey = ref(0);

function typeLabel(t: number) {
  if (t === 1) return '目录';
  if (t === 2) return '菜单';
  if (t === 3) return '按钮';
  return String(t);
}

function toElTree(
  nodes: MenuNode[],
  treeDisabled: boolean,
): { id: number; label: string; disabled?: boolean; children?: any[] }[] {
  return nodes.map((n) => ({
    id: n.id,
    label: `${n.name} (${typeLabel(n.type)})`,
    disabled: treeDisabled,
    children: n.children?.length ? toElTree(n.children, treeDisabled) : undefined,
  }));
}

const menuTreeData = computed(() =>
  toElTree(menuTree.value, drawerMode.value === 'view'),
);

const roleFormSchema = computed(() =>
  buildRoleFormSchema({
    isEdit: editingId.value !== null,
    readonly: drawerMode.value === 'view',
  }),
);

const [Form, formApi] = useVbenForm({
  schema: roleFormSchema as any,
  showDefaultActions: false,
});

function collectMenuIdsFromTree(): number[] {
  const tree = menuTreeRef.value as undefined | {
    getCheckedKeys: (leafOnly?: boolean) => number[];
    getHalfCheckedKeys: () => number[];
  };
  if (!tree) {
    return [];
  }
  const checked = tree.getCheckedKeys(false) as number[];
  const half = tree.getHalfCheckedKeys() as number[];
  return [...new Set([...checked, ...half])];
}

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    if (drawerMode.value === 'view') {
      return;
    }
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const values = (await formApi.getValues()) as RoleSavePayload;
    drawerApi.lock();
    try {
      const menuIds = collectMenuIdsFromTree();
      if (!editingId.value) {
        const created = await createRole(values);
        await syncRoleMenus(created.id, menuIds);
        ElMessage.success('创建成功');
      } else {
        await updateRole(editingId.value, values);
        await syncRoleMenus(editingId.value, menuIds);
        ElMessage.success('已保存');
      }
      drawerApi.close();
      gridApi.query();
    } catch {
      drawerApi.unlock();
    } finally {
      drawerApi.unlock();
    }
  },
});

let gridApi: ExtendedVxeGridApi<RoleRow>;

function onActionClick({ code, row }: OnActionClickParams<RoleRow>) {
  if (code === 'view') {
    void openView(row);
  } else if (code === 'edit') {
    void openEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onRoleStatusToggle(row: RoleRow, nextStatus: number) {
  const label = nextStatus === 1 ? '启用' : '禁用';
  await ElMessageBox.confirm(
    `确定将角色「${row.name}」的状态切换为【${label}】吗？`,
    '切换状态',
    { type: 'warning' },
  );
  await updateRole(row.id, { status: nextStatus });
  ElMessage.success('状态已更新');
  gridApi.query();
}

async function openCreate() {
  drawerMode.value = 'create';
  editingId.value = null;
  drawerApi.setState({
    loading: true,
    showConfirmButton: true,
    title: '新增角色',
  });
  drawerApi.open();
  formApi.resetForm();
  try {
    menuTree.value = await fetchMenuTree();
    treeRenderKey.value += 1;
    await nextTick();
    formApi.setValues({
      code: '',
      data_scope: 1,
      name: '',
      remark: '',
      sort: 0,
      status: 1,
    });
    await nextTick();
    menuTreeRef.value?.setCheckedKeys([], false);
  } finally {
    drawerApi.setState({ loading: false });
  }
}

async function openEdit(row: RoleRow) {
  drawerMode.value = 'edit';
  editingId.value = row.id;
  drawerApi.setState({
    loading: true,
    showConfirmButton: true,
    title: '编辑角色',
  });
  drawerApi.open();
  formApi.resetForm();
  try {
    menuTree.value = await fetchMenuTree();
    const detail = await fetchRoleDetail(row.id);
    treeRenderKey.value += 1;
    await nextTick();
    formApi.setValues({
      code: detail.code,
      data_scope: detail.data_scope,
      name: detail.name,
      remark: detail.remark ?? '',
      sort: detail.sort,
      status: detail.status,
    });
    await nextTick();
    menuTreeRef.value?.setCheckedKeys(detail.menu_ids, false);
  } finally {
    drawerApi.setState({ loading: false });
  }
}

async function openView(row: RoleRow) {
  drawerMode.value = 'view';
  editingId.value = row.id;
  drawerApi.setState({
    loading: true,
    showConfirmButton: false,
    title: '查看角色',
  });
  drawerApi.open();
  formApi.resetForm();
  try {
    menuTree.value = await fetchMenuTree();
    const detail = await fetchRoleDetail(row.id);
    treeRenderKey.value += 1;
    await nextTick();
    formApi.setValues({
      code: detail.code,
      data_scope: detail.data_scope,
      name: detail.name,
      remark: detail.remark ?? '',
      sort: detail.sort,
      status: detail.status,
    });
    await nextTick();
    menuTreeRef.value?.setCheckedKeys(detail.menu_ids, false);
  } finally {
    drawerApi.setState({ loading: false });
  }
}

async function onDelete(row: RoleRow) {
  await ElMessageBox.confirm(`确定删除角色「${row.name}」？`, '提示', {
    type: 'warning',
  });
  await deleteRole(row.id);
  ElMessage.success('已删除');
  gridApi.query();
}

const [Grid, gridApiInstance] = useVbenVxeGrid({
  formOptions: {
    schema: useGridFormSchema(),
    submitButtonOptions: { content: '查询' },
    submitOnChange: false,
  },
  gridOptions: {
    columns: useColumns(onActionClick, onRoleStatusToggle),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchRolePage({
            page: page.currentPage,
            per_page: page.pageSize,
            sort_field: sort?.field,
            sort_order:
              order === 'asc' || order === 'desc' ? order : undefined,
            name: formValues?.name || undefined,
            code: formValues?.code || undefined,
            status: formValues?.status,
          });
        },
      },
      sort: true,
    },
    rowConfig: {
      keyField: 'id',
    },
    sortConfig: {
      remote: true,
    },
    toolbarConfig: {
      custom: true,
      refresh: true,
      search: true,
      zoom: true,
    },
  } as VxeTableGridOptions<RoleRow>,
});

gridApi = gridApiInstance;

</script>

<template>
  <Page auto-content-height>
    <Drawer class="w-[640px]">
      <div class="flex min-h-0 flex-1 flex-col gap-4">
        <Form class="shrink-0" />
        <div class="border-border flex min-h-0 flex-1 flex-col border-t pt-4">
          <div class="text-foreground mb-2 text-sm font-medium">授权</div>
          <ElScrollbar class="min-h-[280px]" max-height="420px">
            <ElTree
              :key="treeRenderKey"
              ref="menuTreeRef"
              :data="menuTreeData"
              show-checkbox
              node-key="id"
              default-expand-all
              highlight-current
            />
          </ElScrollbar>
        </div>
      </div>
    </Drawer>

    <Grid>
      <template #toolbar-actions>
        <ElButton type="primary" @click="openCreate">新增角色</ElButton>
      </template>
    </Grid>
  </Page>
</template>
