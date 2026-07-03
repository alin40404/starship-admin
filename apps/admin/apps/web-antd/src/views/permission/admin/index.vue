<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AdminRow } from '#/api/system/admin';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenDrawer } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteAdmins,
  batchUpdateAdminStatus,
  deleteAdmin,
  fetchAdminPage,
  updateAdminStatus,
} from '#/api/system/admin';
import { ADMIN_PERMISSIONS } from '#/constants/admin-permissions';

import { useColumns, useGridFormSchema } from './data';
import AdminForm from './modules/form.vue';

const { hasAccessByCodes } = useAccess();

const adminAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const [AdminFormDrawer, adminFormDrawerApi] = useVbenDrawer({
  connectedComponent: AdminForm,
  destroyOnClose: true,
});

const selectedRows = ref<AdminRow[]>([]);

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as AdminRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onCreate() {
  adminFormDrawerApi.setData({}).open();
}

function onEdit(row: AdminRow) {
  adminFormDrawerApi.setData(row).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<AdminRow>) {
  if (code === 'edit') {
    onEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onAdminStatusToggle(nextStatus: number, row: AdminRow) {
  if (row.is_super === 1) {
    return false;
  }
  return new Promise<boolean>((resolve) => {
    const label = nextStatus === 1 ? '启用' : '禁用';
    Modal.confirm({
      content: `确定将「${row.username}」的状态切换为【${label}】吗？`,
      okText: '确定',
      cancelText: '取消',
      title: '切换状态',
      async onOk() {
        try {
          await updateAdminStatus(row.id, nextStatus as 0 | 1);
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

async function onDelete(row: AdminRow) {
  await deleteAdmin(row.id);
  message.success('已删除');
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: `确定删除选中的 ${ids.length} 条管理员吗？删除后不可恢复。`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      await batchDeleteAdmins(ids);
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
    content: `确定将选中的 ${ids.length} 条管理员批量【${label}】吗？`,
    okText: '确定',
    cancelText: '取消',
    title: `批量${label}`,
    async onOk() {
      await batchUpdateAdminStatus(ids, status);
      message.success(`已批量${label}`);
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<AdminRow>({
  formOptions: {
    commonConfig: {
      componentProps: {
        class: 'w-full',
      },
    },
    fieldMappingTime: [
      [
        'updatedTime',
        ['updated_start', 'updated_end'],
        ['YYYY-MM-DD', 'YYYY-MM-DD'],
      ],
    ],
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
      checkMethod: ({ row }) => (row as AdminRow).is_super !== 1,
      highlight: true,
    },
    columns: useColumns(onActionClick, onAdminStatusToggle, adminAccess),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchAdminPage({
            page: page.currentPage,
            per_page: page.pageSize,
            sort_field: sort?.field,
            sort_order:
              order === 'asc' || order === 'desc' ? order : undefined,
            dept_id: formValues?.dept_id,
            email: formValues?.email || undefined,
            mobile: formValues?.mobile || undefined,
            name: formValues?.name || undefined,
            updated_end: formValues?.updated_end || undefined,
            updated_start: formValues?.updated_start || undefined,
            username: formValues?.username || undefined,
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
  } as VxeTableGridOptions<AdminRow>,
});
</script>

<template>
  <Page auto-content-height>
    <AdminFormDrawer class="w-[560px]" @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
          <Button
            v-if="adminAccess.can(ADMIN_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            批量删除（{{ selectedRows.length }}）
          </Button>
          <Button
            v-if="adminAccess.can(ADMIN_PERMISSIONS.BATCH_STATUS)"
            class="admin-batch-btn-enable ml-2"
            @click="onBatchUpdateStatus(1)"
          >
            批量启用
          </Button>
          <Button
            v-if="adminAccess.can(ADMIN_PERMISSIONS.BATCH_STATUS)"
            class="admin-batch-btn-disable ml-2"
            @click="onBatchUpdateStatus(0)"
          >
            批量禁用
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            取消选择
          </Button>
        </div>
        <Button
          v-else-if="adminAccess.can(ADMIN_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增管理员
        </Button>
      </template>
    </Grid>
  </Page>
</template>

<style lang="scss">
.vxe-grid .admin-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .admin-batch-btn-enable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #73d13d;
  border-color: #73d13d;
}

.vxe-grid .admin-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}

.vxe-grid .admin-batch-btn-disable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #ffa940;
  border-color: #ffa940;
}
</style>
