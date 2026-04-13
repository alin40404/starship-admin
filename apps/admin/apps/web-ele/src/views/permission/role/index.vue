<script lang="ts" setup>
import type { MenuNode } from '#/api/system/menu';
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { RoleRow, RoleSavePayload } from '#/api/system/role';

import { computed, nextTick, ref } from 'vue';

import { Page, useVbenDrawer, useVbenModal } from '@vben/common-ui';

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

const menuTree = ref<MenuNode[]>([]);
const menuTreeRef = ref<InstanceType<typeof ElTree>>();
const assignRoleId = ref<number | null>(null);
const treeRenderKey = ref(0);

function toElTree(nodes: MenuNode[]): { id: number; label: string; children?: any[] }[] {
  return nodes.map((n) => ({
    id: n.id,
    label: `${n.name} (${typeLabel(n.type)})`,
    children: n.children?.length ? toElTree(n.children) : undefined,
  }));
}

function typeLabel(t: number) {
  if (t === 1) return '目录';
  if (t === 2) return '菜单';
  if (t === 3) return '按钮';
  return String(t);
}

const roleFormSchema = computed(() =>
  buildRoleFormSchema(editingId.value !== null),
);

const [Form, formApi] = useVbenForm({
  schema: roleFormSchema as any,
  showDefaultActions: false,
});

const [Drawer, drawerApi] = useVbenDrawer({
  appendToMain: true,
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const values = (await formApi.getValues()) as RoleSavePayload;
    drawerApi.lock();
    try {
      if (!editingId.value) {
        await createRole(values);
        ElMessage.success('创建成功');
      } else {
        await updateRole(editingId.value, values);
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

const [MenuModal, menuModalApi] = useVbenModal({
  async onConfirm() {
    if (!assignRoleId.value || !menuTreeRef.value) {
      return;
    }
    const tree = menuTreeRef.value as {
      getCheckedKeys: (leafOnly?: boolean) => number[];
      getHalfCheckedKeys: () => number[];
    };
    const checked = tree.getCheckedKeys(false) as number[];
    const half = tree.getHalfCheckedKeys() as number[];
    const menuIds = [...new Set([...checked, ...half])];
    await syncRoleMenus(assignRoleId.value, menuIds);
    ElMessage.success('菜单权限已更新');
    menuModalApi.close();
  },
});

function onActionClick({ code, row }: OnActionClickParams<RoleRow>) {
  if (code === 'edit') {
    openEdit(row);
  } else if (code === 'menus') {
    openAssignMenus(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

function openCreate() {
  editingId.value = null;
  formApi.resetForm();
  drawerApi.setState({ title: '新增角色' }).open();
  nextTick(() => {
    formApi.setValues({
      code: '',
      data_scope: 1,
      name: '',
      remark: '',
      sort: 0,
      status: 1,
    });
  });
}

function openEdit(row: RoleRow) {
  editingId.value = row.id;
  formApi.resetForm();
  drawerApi.setState({ title: '编辑角色' }).open();
  nextTick(() => {
    formApi.setValues({
      code: row.code,
      data_scope: row.data_scope,
      name: row.name,
      remark: row.remark ?? '',
      sort: row.sort,
      status: row.status,
    });
  });
}

async function openAssignMenus(row: RoleRow) {
  assignRoleId.value = row.id;
  menuTree.value = await fetchMenuTree();
  const detail = await fetchRoleDetail(row.id);
  treeRenderKey.value += 1;
  menuModalApi.setState({ title: `分配菜单 — ${row.name}` }).open();
  await nextTick();
  menuTreeRef.value?.setCheckedKeys(detail.menu_ids, false);
}

async function onDelete(row: RoleRow) {
  await ElMessageBox.confirm(`确定删除角色「${row.name}」？`, '提示', {
    type: 'warning',
  });
  await deleteRole(row.id);
  ElMessage.success('已删除');
  gridApi.query();
}

const [Grid, gridApi] = useVbenVxeGrid({
  formOptions: {
    schema: useGridFormSchema(),
    submitButtonOptions: { content: '查询' },
    submitOnChange: false,
  },
  gridOptions: {
    columns: useColumns(onActionClick),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) => {
          return await fetchRolePage({
            page: page.currentPage,
            per_page: page.pageSize,
            ...formValues,
          });
        },
      },
    },
    rowConfig: {
      keyField: 'id',
    },
    toolbarConfig: {
      custom: true,
      refresh: true,
      search: true,
      zoom: true,
    },
  } as VxeTableGridOptions<RoleRow>,
});

</script>

<template>
  <Page auto-content-height>
    <Drawer class="w-[520px]">
      <Form />
    </Drawer>

    <MenuModal class="w-[520px]">
      <ElScrollbar max-height="420px">
        <ElTree
          :key="treeRenderKey"
          ref="menuTreeRef"
          :data="toElTree(menuTree)"
          show-checkbox
          node-key="id"
          default-expand-all
          highlight-current
        />
      </ElScrollbar>
    </MenuModal>

    <Grid>
      <template #toolbar-tools>
        <ElButton type="primary" @click="openCreate">新增角色</ElButton>
      </template>
    </Grid>
  </Page>
</template>
