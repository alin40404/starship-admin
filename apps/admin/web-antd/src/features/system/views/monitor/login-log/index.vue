<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { LoginLogRow } from '#/features/system/api/monitor';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteLoginLogs,
  deleteLoginLog,
  fetchLoginLogPage,
} from '#/features/system/api/monitor';
import { MONITOR_LOGIN_LOG_PERMISSIONS } from '#/features/system/constants/monitor-permissions';
import { $t } from '#/locales';

import {
  buildLoginLogMobileCrudConfig,
  useColumns,
  useGridFormSchema,
} from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();

const { hasAccessByCodes } = useAccess();

const access = {
  can: (code: string) =>
    hasAccessByCodes([MONITOR_LOGIN_LOG_PERMISSIONS.MANAGE, code]),
};

function onActionClick({ code, row }: OnActionClickParams<LoginLogRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row.id, row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

const columns = useColumns(onActionClick, access);

const [CrudView, crudApi] = useResponsiveCrud<LoginLogRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<LoginLogRow>['columns'],
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
  mobile: buildLoginLogMobileCrudConfig({
    columns,
    query: async ({ filters, keyword, page, pageSize }) => {
      const result = await fetchLoginLogPage({
        page,
        per_page: pageSize,
        keyword: keyword || undefined,
        action: filters?.action || undefined,
        status:
          filters?.status === 0 || filters?.status === 1
            ? filters.status
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

function onRefresh() {
  reload();
}

async function onDelete(row: LoginLogRow) {
  await deleteLoginLog(row.id);
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
    content: $t('monitor.loginLog.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeleteLoginLogs(ids);
      message.success($t('crud.batchDeleted'));
      clearSelection();
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <DetailDrawer ref="detailDrawerRef" />
    <CrudView>
      <template #toolbar-actions>
        <div
          v-show="selectedRows.length > 0"
          :class="{ 'vxe-grid-batch-toolbar': selectedRows.length > 0 }"
        >
          <Button
            v-if="access.can(MONITOR_LOGIN_LOG_PERMISSIONS.BATCH_DELETE)"
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
  </Page>
</template>
