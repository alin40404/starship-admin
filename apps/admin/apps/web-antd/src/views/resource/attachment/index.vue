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

const ALL_CATEGORY_KEY = '__all__';

const { hasAccessByCodes } = useAccess();

const attachmentAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const activeCategory = ref(ALL_CATEGORY_KEY);
const categoryOptions = ref<{ label: string; value: string }[]>([]);
const selectedRows = ref<AttachmentRow[]>([]);

const [UploadFormModal, uploadFormModalApi] = useVbenModal({
  connectedComponent: UploadForm,
  destroyOnClose: true,
});

function resolveCategoryFilter() {
  if (activeCategory.value === ALL_CATEGORY_KEY) {
    return undefined;
  }
  return activeCategory.value;
}

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

function onCategoryChange(value: string) {
  if (activeCategory.value === value) {
    return;
  }
  activeCategory.value = value;
  clearSelection();
  onRefresh();
}

function onUpload() {
  const category =
    activeCategory.value === ALL_CATEGORY_KEY
      ? undefined
      : activeCategory.value;
  uploadFormModalApi.setData({ category }).open();
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
            category: resolveCategoryFilter(),
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
    <div
      class="bg-card flex h-full min-h-0 flex-col overflow-hidden rounded-lg border border-border md:min-h-[520px] md:flex-row"
    >
      <aside
        class="attachment-category-panel flex shrink-0 flex-col overflow-hidden border-b border-border md:w-[200px] md:border-b-0 md:border-r"
      >
        <div
          class="attachment-category-header flex h-11 shrink-0 items-center border-b border-border bg-accent px-3 text-sm font-medium text-foreground"
        >
          附件分类
        </div>
        <div
          class="attachment-category-tabs flex flex-1 flex-row gap-1 overflow-x-auto overscroll-x-contain p-2 md:flex-col md:overflow-x-visible md:overflow-y-auto md:p-3"
        >
        <button
          class="shrink-0 snap-start rounded-md px-3 py-2 text-center transition-colors md:w-full md:flex-none md:px-3 md:py-2.5 md:text-left"
          :class="
            activeCategory === ALL_CATEGORY_KEY
              ? 'bg-primary/10 text-primary font-medium'
              : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
          "
          type="button"
          @click="onCategoryChange(ALL_CATEGORY_KEY)"
        >
          <div class="whitespace-nowrap text-sm">全部</div>
        </button>
        <button
          v-for="item in categoryOptions"
          :key="item.value"
          class="shrink-0 snap-start rounded-md px-3 py-2 text-center transition-colors md:w-full md:flex-none md:px-3 md:py-2.5 md:text-left"
          :class="
            activeCategory === item.value
              ? 'bg-primary/10 text-primary font-medium'
              : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
          "
          type="button"
          @click="onCategoryChange(item.value)"
        >
          <div class="whitespace-nowrap text-sm">{{ item.label }}</div>
        </button>
        </div>
      </aside>

      <main class="flex min-h-0 flex-1 flex-col overflow-hidden">
        <div class="min-h-0 flex-1">
          <Grid>
            <template #toolbar-actions>
              <template v-if="selectedRows.length > 0">
                <Button
                  v-if="
                    attachmentAccess.can(ATTACHMENT_PERMISSIONS.BATCH_DELETE)
                  "
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
        </div>
      </main>
    </div>
  </Page>
</template>

<style scoped>
.attachment-category-tabs {
  -webkit-overflow-scrolling: touch;
  scroll-snap-type: x proximity;
  scrollbar-width: thin;
}

.attachment-category-tabs::-webkit-scrollbar {
  height: 4px;
}

.attachment-category-tabs::-webkit-scrollbar-thumb {
  border-radius: 9999px;
  background-color: hsl(var(--border));
}
</style>
