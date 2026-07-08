<script lang="ts" setup>
import type { LoginLogRow } from '#/api/monitor';
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteLoginLogs,
  deleteLoginLog,
  fetchLoginLogPage,
} from '#/api/monitor';
import { MONITOR_LOGIN_LOG_PERMISSIONS } from '#/constants/monitor-permissions';

import { useColumns, useGridFormSchema } from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const selectedRows = ref<LoginLogRow[]>([]);

const { hasAccessByCodes } = useAccess();

const access = {
  can: (code: string) =>
    hasAccessByCodes([MONITOR_LOGIN_LOG_PERMISSIONS.MANAGE, code]),
};

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as LoginLogRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<LoginLogRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row.id);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onDelete(row: LoginLogRow) {
  await deleteLoginLog(row.id);
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
    content: `确定删除选中的 ${ids.length} 条登录日志吗？`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      await batchDeleteLoginLogs(ids);
      message.success('已批量删除');
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<LoginLogRow>({
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
    columns: useColumns(onActionClick, access) as VxeTableGridOptions<LoginLogRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          await fetchLoginLogPage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
            action: formValues?.action || undefined,
            status:
              formValues?.status === 0 || formValues?.status === 1
                ? formValues.status
                : undefined,
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<LoginLogRow>,
});
</script>

<template>
  <Page auto-content-height>
    <DetailDrawer ref="detailDrawerRef" />
    <Grid>
      <template #toolbar-actions>
        <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
          <Button
            v-if="access.can(MONITOR_LOGIN_LOG_PERMISSIONS.BATCH_DELETE)"
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
  </Page>
</template>
