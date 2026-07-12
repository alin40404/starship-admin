<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { InboxMessageRow } from '#/api/inbox';

import { ref } from 'vue';

import { Page } from '@vben/common-ui';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteInboxMessages,
  deleteInboxMessage,
  fetchInboxMessagePage,
  markAllInboxMessagesRead,
  markInboxMessageRead,
} from '#/api/inbox';
import { $t } from '#/locales';

import { useColumns, useGridFormSchema } from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();

async function onActionClick({ code, row }: OnActionClickParams<InboxMessageRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row.id);
    if (row.is_read === 0) {
      await markInboxMessageRead(row.id);
      message.success($t('inbox.markReadDone'));
      onRefresh();
    }
    return;
  }
  if (code === 'markRead') {
    await markInboxMessageRead(row.id);
    message.success($t('inbox.markReadDone'));
    onRefresh();
    return;
  }
  if (code === 'delete') {
    onDelete(row);
  }
}

const columns = useColumns(onActionClick);

const [CrudView, crudApi] = useResponsiveCrud<InboxMessageRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<InboxMessageRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          await fetchInboxMessagePage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
            category: formValues?.category || undefined,
            type: formValues?.type || undefined,
            is_read:
              formValues?.is_read === 0 || formValues?.is_read === 1
                ? formValues.is_read
                : undefined,
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<InboxMessageRow>,
});

const { clearSelection, reload, selectedRows } = crudApi;

function onRefresh() {
  reload();
}

async function onDelete(row: InboxMessageRow) {
  await deleteInboxMessage(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

async function onMarkAllRead() {
  await markAllInboxMessagesRead();
  message.success($t('inbox.markAllReadDone'));
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: $t('inbox.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeleteInboxMessages(ids);
      message.success($t('crud.batchDeleted', [ids.length]));
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
          <Button danger @click="onBatchDelete">
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button v-else @click="onMarkAllRead">
          {{ $t('inbox.markAllRead') }}
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
