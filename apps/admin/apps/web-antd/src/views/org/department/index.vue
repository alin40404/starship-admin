<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { DepartmentTreeNode } from '#/api/system/department';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenDrawer } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  deleteDepartment,
  fetchDepartmentTree,
  updateDepartmentStatus,
} from '#/api/system/department';
import { DEPARTMENT_PERMISSIONS } from '#/constants/department-permissions';

import { useColumns } from './data';
import DepartmentForm from './modules/form.vue';

const { hasAccessByCodes } = useAccess();

const deptAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const [DepartmentFormDrawer, deptFormDrawerApi] = useVbenDrawer({
  connectedComponent: DepartmentForm,
  destroyOnClose: true,
});

const selectedRows = ref<DepartmentTreeNode[]>([]);

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as DepartmentTreeNode[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onCreate() {
  deptFormDrawerApi.setData({}).open();
}

function onAppend(row: DepartmentTreeNode) {
  deptFormDrawerApi.setData({ parent_id: row.id }).open();
}

function onEdit(row: DepartmentTreeNode) {
  deptFormDrawerApi.setData(row).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<DepartmentTreeNode>) {
  if (code === 'append') onAppend(row);
  else if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onDeptStatusToggle(nextStatus: number, row: DepartmentTreeNode) {
  return new Promise<boolean>((resolve) => {
    const label = nextStatus === 1 ? '启用' : '禁用';
    Modal.confirm({
      content: `确定将「${row.name}」的状态切换为【${label}】吗？`,
      okText: '确定',
      cancelText: '取消',
      title: '切换状态',
      async onOk() {
        try {
          await updateDepartmentStatus(row.id, nextStatus as 0 | 1);
          message.success('状态已更新');
          onRefresh();
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

function onDelete(row: DepartmentTreeNode) {
  Modal.confirm({
    content: `确定删除「${row.name}」吗？存在下级部门或管理员时将无法删除。`,
    okText: '删除',
    okType: 'danger',
    title: '删除部门',
    async onOk() {
      await deleteDepartment(row.id);
      message.success('已删除');
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: `确定删除选中的 ${ids.length} 个部门吗？存在下级部门或管理员时将无法删除。`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      const results = await Promise.allSettled(
        ids.map((id) => deleteDepartment(id)),
      );
      const failed = results.filter((r) => r.status === 'rejected').length;
      if (failed > 0) {
        message.warning(`已删除 ${ids.length - failed} 个，${failed} 个删除失败`);
      } else {
        message.success('已批量删除');
      }
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: 0 | 1) {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  const label = status === 1 ? '启用' : '禁用';
  Modal.confirm({
    content: `确定将选中的 ${ids.length} 个部门批量【${label}】吗？`,
    okText: '确定',
    cancelText: '取消',
    title: `批量${label}`,
    async onOk() {
      await Promise.all(ids.map((id) => updateDepartmentStatus(id, status)));
      message.success(`已批量${label}`);
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid({
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: useColumns(onActionClick, onDeptStatusToggle, deptAccess),
    height: 'auto',
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async () => fetchDepartmentTree(true),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, zoom: true },
    treeConfig: {
      childrenField: 'children',
      rowField: 'id',
      transform: false,
    },
  } as VxeTableGridOptions<DepartmentTreeNode>,
});
</script>

<template>
  <Page auto-content-height>
    <DepartmentFormDrawer @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <template v-if="selectedRows.length > 0">
          <Button
            v-if="deptAccess.can(DEPARTMENT_PERMISSIONS.DELETE)"
            danger
            @click="onBatchDelete"
          >
            批量删除（{{ selectedRows.length }}）
          </Button>
          <Button
            v-if="deptAccess.can(DEPARTMENT_PERMISSIONS.STATUS)"
            class="org-batch-btn-enable ml-2"
            @click="onBatchUpdateStatus(1)"
          >
            批量启用
          </Button>
          <Button
            v-if="deptAccess.can(DEPARTMENT_PERMISSIONS.STATUS)"
            class="org-batch-btn-disable ml-2"
            @click="onBatchUpdateStatus(0)"
          >
            批量禁用
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            取消选择
          </Button>
        </template>
        <Button
          v-else-if="deptAccess.can(DEPARTMENT_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增部门
        </Button>
      </template>
    </Grid>
  </Page>
</template>

<style lang="scss">
.vxe-grid .org-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .org-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}
</style>
