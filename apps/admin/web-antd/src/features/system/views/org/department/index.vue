<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { DepartmentTreeNode } from '#/features/system/api/department';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteDepartments,
  batchUpdateDepartmentStatus,
  deleteDepartment,
  fetchDepartmentTree,
  updateDepartmentSort,
  updateDepartmentStatus,
} from '#/features/system/api/department';
import { DEPARTMENT_PERMISSIONS } from '#/features/system/constants/department-permissions';
import { $t } from '#/locales';

import { useColumns } from './data';
import DepartmentForm from './modules/form.vue';

type DepartmentFormExpose = {
  open: (data?: DepartmentTreeNode | { parent_id?: number }) => void;
};

const { hasAccessByCodes } = useAccess();

const deptAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const deptFormRef = ref<DepartmentFormExpose>();
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
  deptFormRef.value?.open({});
}

function onAppend(row: DepartmentTreeNode) {
  deptFormRef.value?.open({ parent_id: row.id });
}

function onEdit(row: DepartmentTreeNode) {
  deptFormRef.value?.open(row);
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
    const label =
      nextStatus === 1
        ? $t('system.shared.status.enable')
        : $t('system.shared.status.disable');
    Modal.confirm({
      content: $t('system.shared.toggleStatusConfirm', [row.name, label]),
      okText: $t('common.confirm'),
      cancelText: $t('common.cancel'),
      title: $t('system.shared.toggleStatusTitle'),
      async onOk() {
        try {
          await updateDepartmentStatus(row.id, nextStatus as 0 | 1);
          message.success($t('crud.statusUpdated'));
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

async function onDelete(row: DepartmentTreeNode) {
  await deleteDepartment(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: $t('org.department.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      const result = await batchDeleteDepartments(ids);
      if (result.skipped > 0) {
        message.warning(
          $t('org.department.batchDeletePartial', [
            result.deleted,
            result.skipped,
          ]),
        );
      } else {
        message.success($t('crud.batchDeleted'));
      }
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: 0 | 1) {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  const label =
    status === 1
      ? $t('system.shared.status.enable')
      : $t('system.shared.status.disable');
  Modal.confirm({
    content: $t('org.department.batchStatusConfirm', [ids.length, label]),
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchSet', [label]),
    async onOk() {
      await batchUpdateDepartmentStatus(ids, status);
      message.success($t('system.shared.batchStatusDone', [label]));
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid({
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
    async editClosed({
      column,
      row,
    }: {
      column: { field: string };
      row: DepartmentTreeNode;
    }) {
      if (column.field !== 'sort') {
        return;
      }
      const grid = gridApi.grid;
      if (!grid?.isUpdateByRow?.(row)) {
        return;
      }

      const sort = Number(row.sort);
      if (!Number.isFinite(sort) || sort < 0 || !Number.isInteger(sort)) {
        message.warning($t('org.shared.sortInvalid'));
        await gridApi.reload();
        return;
      }

      gridApi.setLoading(true);
      try {
        await updateDepartmentSort(row.id, sort);
        message.success($t('org.shared.sortUpdated'));
        onRefresh();
      } catch {
        await gridApi.reload();
      } finally {
        gridApi.setLoading(false);
      }
    },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: useColumns(onActionClick, onDeptStatusToggle, deptAccess),
    editConfig: {
      beforeEditMethod({ column }) {
        return (
          column.field === 'sort' &&
          deptAccess.can(DEPARTMENT_PERMISSIONS.SORT)
        );
      },
      mode: 'cell',
      trigger: 'dblclick',
    },
    height: 'auto',
    keepSource: true,
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
      expandAll: true,
      rowField: 'id',
      transform: false,
    },
  } as VxeTableGridOptions<DepartmentTreeNode>,
});
</script>

<template>
  <Page auto-content-height>
    <DepartmentForm ref="deptFormRef" @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
          <Button
            v-if="deptAccess.can(DEPARTMENT_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>
          <Button
            v-if="deptAccess.can(DEPARTMENT_PERMISSIONS.BATCH_STATUS)"
            class="org-batch-btn-enable ml-2"
            @click="onBatchUpdateStatus(1)"
          >
            {{ $t('system.shared.batchEnable') }}
          </Button>
          <Button
            v-if="deptAccess.can(DEPARTMENT_PERMISSIONS.BATCH_STATUS)"
            class="org-batch-btn-disable ml-2"
            @click="onBatchUpdateStatus(0)"
          >
            {{ $t('system.shared.batchDisable') }}
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button
          v-else-if="deptAccess.can(DEPARTMENT_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          {{ $t('org.department.createDept') }}
        </Button>
      </template>
    </Grid>
  </Page>
</template>

<style lang="scss">
.vxe-grid .dept-sort-editable-col {
  cursor: pointer;
}

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
