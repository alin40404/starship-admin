<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { RoleRow } from '#/api/system/role';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenDrawer } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteRoles,
  batchUpdateRoleStatus,
  deleteRole,
  fetchRolePage,
  updateRoleStatus,
} from '#/api/system/role';
import { ROLE_PERMISSIONS } from '#/constants/role-permissions';

import { useColumns, useGridFormSchema } from './data';
import RoleForm from './modules/form.vue';

const { hasAccessByCodes } = useAccess();

const roleAccess = {
  can: (code: string) => hasAccessByCodes([code]),
  canAny: (codes: string[]) => hasAccessByCodes(codes),
};

const [RoleFormDrawer, roleFormDrawerApi] = useVbenDrawer({
  connectedComponent: RoleForm,
  destroyOnClose: true,
});

const selectedRows = ref<RoleRow[]>([]);

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as RoleRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onCreate() {
  roleFormDrawerApi.setData({}).open();
}

function onEdit(row: RoleRow) {
  roleFormDrawerApi.setData(row).open();
}

/** 超级管理员：只读查看（与 web-ele 一致） */
function onView(row: RoleRow) {
  roleFormDrawerApi.setData({ ...row, _mode: 'view' as const }).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<RoleRow>) {
  if (code === 'view') {
    onView(row);
  } else if (code === 'edit') {
    onEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onRoleStatusToggle(nextStatus: number, row: RoleRow) {
  if (row.code === 'super_admin') {
    return false;
  }
  return new Promise<boolean>((resolve) => {
    const label = nextStatus === 1 ? '启用' : '禁用';
    Modal.confirm({
      content: `确定将「${row.name}」的状态切换为【${label}】吗？`,
      okText: '确定',
      cancelText: '取消',
      title: '切换状态',
      async onOk() {
        try {
          await updateRoleStatus(row.id, nextStatus as 0 | 1);
          message.success('状态已更新');
          resolve(true);
        } catch {
          resolve(false);
        }
      },
      onCancel() {
        resolve(false);
      },
    });
  });
}

async function onDelete(row: RoleRow) {
  await deleteRole(row.id);
  message.success('已删除');
  clearSelection();
  gridApi.query();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: `确定删除选中的 ${ids.length} 条角色吗？删除后不可恢复。`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      await batchDeleteRoles(ids);
      message.success('已批量删除');
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: 0 | 1) {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  const label = status === 1 ? '启用' : '禁用';
  Modal.confirm({
    content: `确定将选中的 ${ids.length} 条角色批量【${label}】吗？`,
    okText: '确定',
    cancelText: '取消',
    title: `批量${label}`,
    async onOk() {
      await batchUpdateRoleStatus(ids, status);
      message.success(`已批量${label}`);
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<RoleRow>({
  formOptions: {
    commonConfig: {
      componentProps: {
        class: 'w-full',
      },
    },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: {
      content: '查询',
    },
  },
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
  },
  gridOptions: {
    checkboxConfig: {
      checkMethod: ({ row }) => (row as RoleRow).code !== 'super_admin',
      highlight: true,
    },
    columns: useColumns(onActionClick, onRoleStatusToggle, roleAccess),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchRolePage({
            page: page.currentPage,
            per_page: page.pageSize,
            code: formValues?.code || undefined,
            name: formValues?.name || undefined,
            sort_field: sort?.field,
            sort_order:
              order === 'asc' || order === 'desc' ? order : undefined,
            status:
              formValues?.status === undefined || formValues?.status === null
                ? undefined
                : formValues.status,
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
</script>

<template>
  <Page auto-content-height>
    <RoleFormDrawer class="max-w-[720px] w-[92vw]" @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
          <Button
            v-if="roleAccess.can(ROLE_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            批量删除（{{ selectedRows.length }}）
          </Button>
          <Button
            v-if="roleAccess.can(ROLE_PERMISSIONS.BATCH_STATUS)"
            class="role-batch-btn-enable ml-2"
            @click="onBatchUpdateStatus(1)"
          >
            批量启用
          </Button>
          <Button
            v-if="roleAccess.can(ROLE_PERMISSIONS.BATCH_STATUS)"
            class="role-batch-btn-disable ml-2"
            @click="onBatchUpdateStatus(0)"
          >
            批量禁用
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            取消选择
          </Button>
        </div>
        <Button
          v-else-if="roleAccess.can(ROLE_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增角色
        </Button>
      </template>
    </Grid>
  </Page>
</template>

<style lang="scss">
/** 批量启用 / 批量禁用（工具栏） */
.vxe-grid .role-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .role-batch-btn-enable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #73d13d;
  border-color: #73d13d;
}

.vxe-grid .role-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}

.vxe-grid .role-batch-btn-disable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #ffa940;
  border-color: #ffa940;
}
</style>
