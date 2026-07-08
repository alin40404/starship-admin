<script lang="ts" setup>
import type { ExceptionLogRow } from '#/api/monitor';
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Alert, Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteExceptionLogs,
  deleteExceptionLog,
  fetchExceptionLogPage,
} from '#/api/monitor';
import { MONITOR_EXCEPTION_LOG_PERMISSIONS } from '#/constants/monitor-permissions';

import { useColumns, useGridFormSchema } from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const selectedRows = ref<ExceptionLogRow[]>([]);

const { hasAccessByCodes } = useAccess();

const access = {
  can: (code: string) =>
    hasAccessByCodes([MONITOR_EXCEPTION_LOG_PERMISSIONS.MANAGE, code]),
};

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as ExceptionLogRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<ExceptionLogRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row.id);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onDelete(row: ExceptionLogRow) {
  await deleteExceptionLog(row.id);
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
    content: `确定删除选中的 ${ids.length} 条异常日志吗？`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      await batchDeleteExceptionLogs(ids);
      message.success('已批量删除');
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<ExceptionLogRow>({
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
    columns: useColumns(onActionClick, access) as VxeTableGridOptions<ExceptionLogRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          await fetchExceptionLogPage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
            exception_class: formValues?.exception_class || undefined,
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<ExceptionLogRow>,
});
</script>

<template>
  <Page auto-content-height>
    <div class="flex h-full min-h-0 flex-col">
      <Alert
        class="mb-4 shrink-0"
        message="列表仅展示异常摘要；完整堆栈请按 trace_id 在服务器 storage/logs/laravel.log 中检索。"
        show-icon
        type="warning"
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
                v-if="access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.BATCH_DELETE)"
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
