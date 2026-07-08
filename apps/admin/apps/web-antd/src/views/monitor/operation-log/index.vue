<script lang="ts" setup>
import type { OperationLogRow } from '#/api/monitor';
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Alert, Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteOperationLogs,
  deleteOperationLog,
  fetchOperationLogPage,
} from '#/api/monitor';
import { MONITOR_OPERATION_LOG_PERMISSIONS } from '#/constants/monitor-permissions';

import { useColumns, useGridFormSchema } from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const selectedRows = ref<OperationLogRow[]>([]);

const { hasAccessByCodes } = useAccess();

const access = {
  can: (code: string) =>
    hasAccessByCodes([MONITOR_OPERATION_LOG_PERMISSIONS.MANAGE, code]),
};

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as OperationLogRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<OperationLogRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row.id);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onDelete(row: OperationLogRow) {
  await deleteOperationLog(row.id);
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
    content: `确定删除选中的 ${ids.length} 条操作日志吗？`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      await batchDeleteOperationLogs(ids);
      message.success('已批量删除');
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<OperationLogRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: '查询' },
  },
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: useColumns(onActionClick, access) as VxeTableGridOptions<OperationLogRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          await fetchOperationLogPage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
            module: formValues?.module || undefined,
            method: formValues?.method || undefined,
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<OperationLogRow>,
});
</script>

<template>
  <Page auto-content-height>
    <div class="flex h-full min-h-0 flex-col">
      <Alert
        class="mb-4 shrink-0"
        message="记录后台写操作（新增、更新、删除等），敏感字段已脱敏。"
        show-icon
        type="info"
      />
      <DetailDrawer ref="detailDrawerRef" />
      <div class="min-h-0 flex-1">
        <Grid class="h-full">
          <template #toolbar-actions>
            <div
              v-if="selectedRows.length > 0"
              class="vxe-grid-batch-toolbar"
            >
              <Button
                v-if="access.can(MONITOR_OPERATION_LOG_PERMISSIONS.BATCH_DELETE)"
                danger
                @click="onBatchDelete"
              >
                批量删除（{{ selectedRows.length }}）
              </Button>
              <Button class="ml-2" type="link" @click="clearSelection">
                取消选择
              </Button>
            </div>
          </template>
        </Grid>
      </div>
    </div>
  </Page>
</template>
