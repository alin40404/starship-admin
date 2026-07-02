<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AttachmentRow } from '#/api/system/attachment';

import { onMounted, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenModal } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteAttachments,
  deleteAttachment,
  fetchAttachmentCategories,
  fetchAttachmentPage,
} from '#/api/system/attachment';
import { ATTACHMENT_PERMISSIONS } from '#/constants/attachment-permissions';

import { useColumns, useGridFormSchema } from './data';
import UploadForm from './modules/upload-form.vue';

const { hasAccessByCodes } = useAccess();

const attachmentAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const categoryOptions = ref<{ label: string; value: string }[]>([]);
const selectedRows = ref<AttachmentRow[]>([]);

const [UploadFormModal, uploadFormModalApi] = useVbenModal({
  connectedComponent: UploadForm,
  destroyOnClose: true,
});

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as AttachmentRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onRefresh() {
  gridApi.query();
}

function onUpload() {
  uploadFormModalApi.setData({}).open();
}

function onActionClick({ code, row }: OnActionClickParams<AttachmentRow>) {
  if (code === 'open') onOpen(row);
  else if (code === 'copy') onCopyLink(row);
  else if (code === 'delete') onDelete(row);
}

function onOpen(row: AttachmentRow) {
  if (!row.file_url) return;
  window.open(row.file_url, '_blank', 'noopener,noreferrer');
}

async function onCopyLink(row: AttachmentRow) {
  if (!row.file_url) return;
  try {
    await navigator.clipboard.writeText(row.file_url);
    message.success('链接已复制');
  } catch {
    message.error('复制失败，请手动复制');
  }
}

function onDelete(row: AttachmentRow) {
  Modal.confirm({
    content: `确定删除「${row.file_name}」吗？将同时删除存储中的物理文件。`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '删除附件',
    async onOk() {
      await deleteAttachment(row.id);
      message.success('已删除');
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: `确定删除选中的 ${ids.length} 个附件吗？将同时删除存储中的物理文件。`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      const result = await batchDeleteAttachments(ids);
      message.success(`已删除 ${result.deleted} 个附件`);
      clearSelection();
      onRefresh();
    },
  });
}

async function loadCategoryOptions() {
  const rows = await fetchAttachmentCategories();
  categoryOptions.value = rows.map((item) => ({
    label: item.label,
    value: item.value,
  }));
  gridApi.formApi?.setState({
    schema: useGridFormSchema(categoryOptions.value),
  });
}

onMounted(() => {
  loadCategoryOptions();
});

const [Grid, gridApi] = useVbenVxeGrid<AttachmentRow>({
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
    columns: useColumns(onActionClick, attachmentAccess),
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchAttachmentPage({
            page: page.currentPage,
            per_page: page.pageSize,
            file_name: formValues?.file_name || undefined,
            file_ext: formValues?.file_ext || undefined,
            category: formValues?.category || undefined,
            storage: formValues?.storage || undefined,
            sort_field: sort?.field,
            sort_order:
              order === 'asc' || order === 'desc' ? order : undefined,
          });
        },
      },
      sort: true,
    },
    rowConfig: { keyField: 'id' },
    sortConfig: { remote: true },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<AttachmentRow>,
});
</script>

<template>
  <Page auto-content-height>
    <UploadFormModal @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <template v-if="selectedRows.length > 0">
          <Button
            v-if="attachmentAccess.can(ATTACHMENT_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            批量删除（{{ selectedRows.length }}）
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            取消选择
          </Button>
        </template>
        <Button
          v-else-if="attachmentAccess.can(ATTACHMENT_PERMISSIONS.UPLOAD)"
          type="primary"
          @click="onUpload"
        >
          <Plus class="size-4" />
          上传附件
        </Button>
      </template>
    </Grid>
  </Page>
</template>
