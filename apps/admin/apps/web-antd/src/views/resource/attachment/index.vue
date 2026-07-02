<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AttachmentRow } from '#/api/system/attachment';

import { computed, onMounted, ref } from 'vue';

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

const activeCategoryLabel = computed(() => {
  if (activeCategory.value === ALL_CATEGORY_KEY) {
    return '全部';
  }
  return (
    categoryOptions.value.find((item) => item.value === activeCategory.value)
      ?.label ?? activeCategory.value
  );
});

function syncGridColumns() {
  gridApi.setGridOptions({
    columns: useColumns(onActionClick, attachmentAccess, {
      showCategoryColumn: activeCategory.value === ALL_CATEGORY_KEY,
    }),
  } as VxeTableGridOptions<AttachmentRow>);
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
  syncGridColumns();
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
        class="attachment-category-panel flex shrink-0 flex-col overflow-hidden border-b border-border md:w-[168px] md:border-b-0 md:border-r"
      >
        <div class="attachment-category-header">附件分类</div>
        <div class="attachment-category-tabs">
          <button
            class="attachment-category-item"
            :class="{ 'is-active': activeCategory === ALL_CATEGORY_KEY }"
            type="button"
            @click="onCategoryChange(ALL_CATEGORY_KEY)"
          >
            全部
          </button>
          <button
            v-for="item in categoryOptions"
            :key="item.value"
            class="attachment-category-item"
            :class="{ 'is-active': activeCategory === item.value }"
            type="button"
            @click="onCategoryChange(item.value)"
          >
            {{ item.label }}
          </button>
        </div>
      </aside>

      <main class="flex min-h-0 flex-1 flex-col overflow-hidden">
        <div class="min-h-0 flex-1">
          <Grid>
            <template #toolbar-actions>
              <div class="flex flex-wrap items-center gap-3">
                <span class="text-muted-foreground hidden text-sm sm:inline">
                  当前分类：
                  <span class="text-foreground font-medium">{{
                    activeCategoryLabel
                  }}</span>
                </span>
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
                  v-else-if="
                    attachmentAccess.can(ATTACHMENT_PERMISSIONS.UPLOAD)
                  "
                  type="primary"
                  @click="onUpload"
                >
                  <Plus class="size-4" />
                  上传附件
                </Button>
              </div>
            </template>
          </Grid>
        </div>
      </main>
    </div>
  </Page>
</template>

<style scoped>
.attachment-category-header {
  display: flex;
  height: 44px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-bottom: 1px solid hsl(var(--border));
  background-color: hsl(var(--accent));
  padding: 0 12px;
  font-size: 14px;
  font-weight: 500;
  color: hsl(var(--foreground));
}

.attachment-category-tabs {
  display: flex;
  flex: 1;
  flex-direction: row;
  gap: 8px;
  overflow-x: auto;
  overscroll-behavior-x: contain;
  padding: 8px;
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

.attachment-category-item {
  flex-shrink: 0;
  scroll-snap-align: start;
  border: 1px solid hsl(var(--border));
  border-radius: 9999px;
  background: transparent;
  padding: 6px 14px;
  font-size: 14px;
  line-height: 1.25;
  color: hsl(var(--muted-foreground));
  transition:
    background-color 0.2s,
    border-color 0.2s,
    color 0.2s;
  white-space: nowrap;
  cursor: pointer;
}

.attachment-category-item:hover {
  border-color: hsl(var(--primary) / 0.35);
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

.attachment-category-item.is-active {
  border-color: hsl(var(--primary) / 0.25);
  background-color: hsl(var(--primary) / 0.08);
  color: hsl(var(--primary));
  font-weight: 500;
}

@media (min-width: 768px) {
  .attachment-category-header {
    justify-content: flex-start;
  }

  .attachment-category-tabs {
    flex-direction: column;
    gap: 0;
    overflow-x: visible;
    overflow-y: auto;
    padding: 0;
  }

  .attachment-category-item {
    width: 100%;
    border: 0;
    border-left: 2px solid transparent;
    border-radius: 0;
    padding: 10px 12px;
    text-align: left;
    color: hsl(var(--foreground));
  }

  .attachment-category-item:hover {
    border-color: transparent;
    border-left-color: hsl(var(--border));
    background-color: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
  }

  .attachment-category-item.is-active {
    border-color: transparent;
    border-left-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.08);
    color: hsl(var(--primary));
  }
}
</style>
