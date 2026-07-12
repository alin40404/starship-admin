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
      reload();
    }
    return;
  }
  if (code === 'markRead') {
    await markInboxMessageRead(row.id);
    message.success($t('inbox.markReadDone'));
    reload();
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

async function onDelete(row: InboxMessageRow) {
  await deleteInboxMessage(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  reload();
}

async function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: $t('crud.batchDeleteConfirm', [ids.length, $t('inbox.entity')]),
    onOk: async () => {
      await batchDeleteInboxMessages(ids);
      message.success($t('crud.batchDeleted', [ids.length]));
      clearSelection();
      reload();
    },
    title: $t('crud.batchDelete'),
  });
}

async function onMarkAllRead() {
  await markAllInboxMessagesRead();
  message.success($t('inbox.markAllReadDone'));
  reload();
}
</script>

<template>
  <Page auto-content-height>
    <CrudView :title="$t('inbox.entity')">
      <template #toolbar-actions>
        <Button @click="onMarkAllRead">
          {{ $t('inbox.markAllRead') }}
        </Button>
        <Button
          v-if="selectedRows.length > 0"
          danger
          type="primary"
          @click="onBatchDelete"
        >
          {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
        </Button>
      </template>
    </CrudView>
    <DetailDrawer ref="detailDrawerRef" />
  </Page>
</template>
