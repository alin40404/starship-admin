<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ExceptionLogRow } from '#/features/system/api/monitor';

import { computed, onMounted, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Alert, Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteExceptionLogs,
  batchUpdateExceptionLogRead,
  deleteExceptionLog,
  fetchExceptionLogPage,
  markAllExceptionLogsRead,
} from '#/features/system/api/monitor';
import { MONITOR_EXCEPTION_LOG_PERMISSIONS } from '#/features/system/constants/monitor-permissions';
import { refreshExceptionLogMenuBadge } from '#/features/system/utils/exception-log-menu-badge';
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
            is_read:
              formValues?.is_read === 0 || formValues?.is_read === 1
                ? formValues.is_read
                : undefined,
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
        is_read:
          filters?.is_read === 0 || filters?.is_read === 1
            ? filters.is_read
            : undefined,
      });
      return {
        data: result.data,
        total: result.total,
      };
    },
  }),
});

const { clearSelection, reload, selectedRows } = crudApi;

const selectedUnreadIds = computed(() =>
  selectedRows.value.filter((row) => row.is_read !== 1).map((row) => row.id),
);
const selectedReadIds = computed(() =>
  selectedRows.value.filter((row) => row.is_read === 1).map((row) => row.id),
);

async function syncMenuBadge() {
  await refreshExceptionLogMenuBadge((code) => access.can(code));
}

function onRefresh() {
  reload();
  void syncMenuBadge();
}

onMounted(() => {
  void syncMenuBadge();
});

async function onDelete(row: ExceptionLogRow) {
  await deleteExceptionLog(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

async function onMarkAllRead() {
  await markAllExceptionLogsRead();
  message.success($t('monitor.exceptionLog.markAllReadDone'));
  clearSelection();
  onRefresh();
}

async function onMarkSelectedRead() {
  const ids = selectedUnreadIds.value;
  if (ids.length === 0) {
    message.info($t('monitor.exceptionLog.markSelectedReadEmpty'));
    return;
  }
  await batchUpdateExceptionLogRead(ids, 1);
  message.success($t('monitor.exceptionLog.markSelectedReadDone'));
  clearSelection();
  onRefresh();
}

async function onMarkSelectedUnread() {
  const ids = selectedReadIds.value;
  if (ids.length === 0) {
    message.info($t('monitor.exceptionLog.markSelectedUnreadEmpty'));
    return;
  }
  await batchUpdateExceptionLogRead(ids, 0);
  message.success($t('monitor.exceptionLog.markSelectedUnreadDone'));
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
      <DetailDrawer ref="detailDrawerRef" @read="onRefresh" />
      <div class="min-h-0 flex-1">
        <CrudView class="h-full">
          <template #toolbar-actions>
            <div
              v-if="selectedRows.length > 0"
              class="vxe-grid-batch-toolbar"
            >
              <Button
                v-if="access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.QUERY)"
                :disabled="selectedUnreadIds.length === 0"
                @click="onMarkSelectedRead"
              >
                {{ $t('monitor.exceptionLog.markSelectedRead') }}
                <template v-if="selectedUnreadIds.length > 0">
                  ({{ selectedUnreadIds.length }})
                </template>
              </Button>
              <Button
                v-if="access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.QUERY)"
                class="ml-2"
                :disabled="selectedReadIds.length === 0"
                @click="onMarkSelectedUnread"
              >
                {{ $t('monitor.exceptionLog.markSelectedUnread') }}
                <template v-if="selectedReadIds.length > 0">
                  ({{ selectedReadIds.length }})
                </template>
              </Button>
              <Button
                v-if="access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.BATCH_DELETE)"
                class="ml-2"
                danger
                @click="onBatchDelete"
              >
                {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
              </Button>
              <Button class="ml-2" type="link" @click="clearSelection">
                {{ $t('system.shared.clearSelection') }}
              </Button>
            </div>
            <Button
              v-else-if="access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.QUERY)"
              @click="onMarkAllRead"
            >
              {{ $t('monitor.exceptionLog.markAllRead') }}
            </Button>
          </template>
        </CrudView>
      </div>
    </div>
  </Page>
</template>
