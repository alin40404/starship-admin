<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ExceptionLogRow } from '#/features/system/api/monitor';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Alert, Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteExceptionLogs,
  deleteExceptionLog,
  fetchExceptionLogPage,
} from '#/features/system/api/monitor';
import { MONITOR_EXCEPTION_LOG_PERMISSIONS } from '#/features/system/constants/monitor-permissions';
import { $t } from '#/locales';

import {
  buildExceptionLogMobileCrudConfig,
  useColumns,
  useGridFormSchema,
} from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();

const { hasAccessByCodes } = useAccess();

const access = {
  can: (code: string) =>
    hasAccessByCodes([MONITOR_EXCEPTION_LOG_PERMISSIONS.MANAGE, code]),
};

function onActionClick({ code, row }: OnActionClickParams<ExceptionLogRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row.id, row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

const columns = useColumns(onActionClick, access);

const [CrudView, crudApi] = useResponsiveCrud<ExceptionLogRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<ExceptionLogRow>['columns'],
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
  mobile: buildExceptionLogMobileCrudConfig({
    columns,
    query: async ({ filters, keyword, page, pageSize }) => {
      const result = await fetchExceptionLogPage({
        page,
        per_page: pageSize,
        keyword: keyword || undefined,
        exception_class: filters?.exception_class || undefined,
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

async function onDelete(row: ExceptionLogRow) {
  await deleteExceptionLog(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: $t('monitor.exceptionLog.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeleteExceptionLogs(ids);
      message.success($t('crud.batchDeleted'));
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
        :message="$t('monitor.exceptionLog.hint')"
        show-icon
        type="warning"
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
                v-if="access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.BATCH_DELETE)"
                danger
                @click="onBatchDelete"
              >
                {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
              </Button>
              <Button class="ml-2" type="link" @click="clearSelection">
                {{ $t('system.shared.clearSelection') }}
              </Button>
            </div>
          </template>
        </CrudView>
      </div>
    </div>
  </Page>
</template>
