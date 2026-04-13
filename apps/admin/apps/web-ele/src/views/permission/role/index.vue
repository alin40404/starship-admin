<script lang="ts" setup>
import type { MenuNode } from '#/api/system/menu';
import type {
  ExtendedVxeGridApi,
  OnActionClickParams,
  VxeTableGridOptions,
} from '#/adapter/vxe-table';
import type { RoleRow, RoleSavePayload } from '#/api/system/role';

import { computed, nextTick, ref } from 'vue';

import { Page, Tree, useVbenDrawer } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';

import { ElMessage, ElMessageBox } from 'element-plus';

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
const loadingPermissions = ref(false);

function normalizeMenuIcon(icon?: string | null) {
  if (!icon) return '';
  // 已是 iconify 格式或远程图片，直接使用
  if (icon.includes(':') || icon.startsWith('http')) return icon;
  // 兼容后端存储的 Element Plus 图标名（如 UserFilled -> ep:user-filled）
  const kebab = icon
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .replace(/([A-Z])([A-Z][a-z])/g, '$1-$2')
    .toLowerCase();
  return `ep:${kebab}`;
}

function toElTree(
  nodes: MenuNode[],
): {
  id: number;
  meta: { icon: string; title: string; type: 'button' | 'directory' | 'menu' };
  children?: any[];
}[] {
  return nodes.map((n) => ({
    id: n.id,
    meta: {
      icon: normalizeMenuIcon(n.icon),
      title: n.name,
      type: n.type === 3 ? 'button' : n.type === 1 ? 'directory' : 'menu',
    },
    children: n.children?.length ? toElTree(n.children) : undefined,
  }));
}

const menuTreeData = computed(() => toElTree(menuTree.value));

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

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    if (drawerMode.value === 'view') {
      return;
    }
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const values = (await formApi.getValues()) as RoleSavePayload & {
      menu_ids?: number[];
    };
    const menuIds = values.menu_ids ?? [];
    const { menu_ids: _menuIds, ...payload } = values;
    drawerApi.lock();
    try {
      if (!editingId.value) {
        const created = await createRole(payload);
        await syncRoleMenus(created.id, menuIds);
        ElMessage.success('创建成功');
      } else {
        await updateRole(editingId.value, payload);
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
    loadingPermissions.value = true;
    menuTree.value = await fetchMenuTree();
    await nextTick();
    formApi.setValues({
      code: '',
      data_scope: 1,
      menu_ids: [],
      name: '',
      remark: '',
      sort: 0,
      status: 1,
    });
  } finally {
    loadingPermissions.value = false;
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
    loadingPermissions.value = true;
    menuTree.value = await fetchMenuTree();
    const detail = await fetchRoleDetail(row.id);
    await nextTick();
    formApi.setValues({
      code: detail.code,
      data_scope: detail.data_scope,
      menu_ids: detail.menu_ids,
      name: detail.name,
      remark: detail.remark ?? '',
      sort: detail.sort,
      status: detail.status,
    });
  } finally {
    loadingPermissions.value = false;
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
    loadingPermissions.value = true;
    menuTree.value = await fetchMenuTree();
    const detail = await fetchRoleDetail(row.id);
    await nextTick();
    formApi.setValues({
      code: detail.code,
      data_scope: detail.data_scope,
      menu_ids: detail.menu_ids,
      name: detail.name,
      remark: detail.remark ?? '',
      sort: detail.sort,
      status: detail.status,
    });
  } finally {
    loadingPermissions.value = false;
    drawerApi.setState({ loading: false });
  }
}

function getNodeClass(node: { value?: { meta?: { type?: string } } }) {
  if (node.value?.meta?.type === 'button') {
    return 'inline-flex';
  }
  return '';
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
      <Form>
        <template #menu_ids="slotProps">
          <Tree
            class="permission-tree w-full"
            :tree-data="menuTreeData"
            :disabled="drawerMode === 'view'"
            :default-expanded-level="2"
            :get-node-class="getNodeClass"
            :loading="loadingPermissions"
            value-field="id"
            label-field="meta.title"
            icon-field="meta.icon"
            multiple
            bordered
            v-bind="slotProps"
          >
            <template #node="{ value }">
              <IconifyIcon v-if="value.meta?.icon" :icon="value.meta.icon" />
              {{ value.meta?.title }}
            </template>
          </Tree>
        </template>
      </Form>
    </Drawer>

    <Grid>
      <template #toolbar-actions>
        <ElButton type="primary" @click="openCreate">新增角色</ElButton>
      </template>
    </Grid>
  </Page>
</template>

<style lang="css" scoped>
:deep(.permission-tree.ant-tree) {
  min-height: 260px;
  width: 100%;
}

:deep(.permission-tree .ant-tree-treenode) {
  width: 100%;
}

:deep(.permission-tree .ant-tree-node-content-wrapper) {
  width: calc(100% - 24px);
}

:deep(.ant-tree-title) {
  .tree-actions {
    margin-left: 1.25rem;
    display: none;
  }
}

:deep(.ant-tree-title:hover) {
  .tree-actions {
    margin-left: 1.25rem;
    display: flex;
    flex: 1 1 auto;
    justify-content: flex-end;
  }
}

:deep(.inline-flex) {
  margin-right: 1.25rem;
  display: inline-flex;
  min-width: 6rem;
}
</style>
