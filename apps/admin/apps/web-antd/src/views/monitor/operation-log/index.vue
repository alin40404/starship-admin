<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { OperationLogRow } from '#/api/monitor';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Alert, Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteOperationLogs,
  deleteOperationLog,
  fetchOperationLogPage,
} from '#/api/monitor';
import { MONITOR_OPERATION_LOG_PERMISSIONS } from '#/constants/monitor-permissions';

import {
  buildOperationLogMobileCrudConfig,
  useColumns,
  useGridFormSchema,
} from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();

const { hasAccessByCodes } = useAccess();

const access = {
  can: (code: string) =>
    hasAccessByCodes([MONITOR_OPERATION_LOG_PERMISSIONS.MANAGE, code]),
};

function onActionClick({ code, row }: OnActionClickParams<OperationLogRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row.id);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

const columns = useColumns(onActionClick, access);

const [CrudView, crudApi] = useResponsiveCrud<OperationLogRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: '查询' },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<OperationLogRow>['columns'],
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
  mobile: buildOperationLogMobileCrudConfig({
    columns,
    query: async ({ filters, keyword, page, pageSize }) => {
      const result = await fetchOperationLogPage({
        page,
        per_page: pageSize,
        keyword: keyword || undefined,
        module: filters?.module || undefined,
        method: filters?.method || undefined,
      });
      return {
        data: result.data,
        total: result.total,
      };
    },
  }),
});

const { clearSelection, reload, selectedRows } = crudApi;

function onRefresh() {
  reload();
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
        <CrudView class="h-full">
          <template #toolbar-actions>
            <div
              v-show="selectedRows.length > 0"
              :class="{ 'vxe-grid-batch-toolbar': selectedRows.length > 0 }"
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
        </CrudView>
      </div>
    </div>
  </Page>
</template>
