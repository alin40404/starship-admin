<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { LoginLogRow } from '#/api/monitor';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteLoginLogs,
  deleteLoginLog,
  fetchLoginLogPage,
} from '#/api/monitor';
import { MONITOR_LOGIN_LOG_PERMISSIONS } from '#/constants/monitor-permissions';

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
    detailDrawerRef.value?.open(row.id);
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
    submitButtonOptions: { content: '查询' },
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
</script>

<template>
  <Page auto-content-height>
    <DetailDrawer ref="detailDrawerRef" />
    <CrudView>
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
    </CrudView>
  </Page>
</template>
