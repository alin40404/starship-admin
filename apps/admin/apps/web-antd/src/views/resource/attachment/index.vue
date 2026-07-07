<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AttachmentRow } from '#/api/system/attachment';

import { computed, nextTick, onMounted, ref, watch } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenModal } from '@vben/common-ui';
import { IconifyIcon, Plus } from '@vben/icons';
import { usePreferences } from '@vben/preferences';

import { Button, message, Modal, Tooltip } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteAttachments,
  deleteAttachment,
  fetchAttachmentCategories,
  fetchAttachmentPage,
} from '#/api/system/attachment';
import { ATTACHMENT_PERMISSIONS } from '#/constants/attachment-permissions';

import { buildCategoryTagOptions, useColumns, useGridFormSchema } from './data';
import AttachmentFlowView from './modules/flow-view.vue';
import AttachmentMobileBody from './modules/mobile-body.vue';
import UploadForm from './modules/upload-form.vue';

const ALL_CATEGORY_KEY = '__all__';
const ATTACHMENT_VIEW_MODE_KEY = 'attachment-view-mode';

type AttachmentViewMode = 'flow' | 'list';

const { hasAccessByCodes } = useAccess();
const { isMobile } = usePreferences();

const attachmentAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const activeCategory = ref(ALL_CATEGORY_KEY);
const categoryOptions = ref<{ label: string; value: string }[]>([]);
const selectedRows = ref<AttachmentRow[]>([]);
const flowRows = ref<AttachmentRow[]>([]);
const viewMode = ref<AttachmentViewMode>(
  (localStorage.getItem(ATTACHMENT_VIEW_MODE_KEY) as AttachmentViewMode) ||
    'list',
);

const selectedFlowIds = computed({
  get: () => selectedRows.value.map((row) => row.id),
  set: (ids: number[]) => {
    selectedRows.value = flowRows.value.filter((row) => ids.includes(row.id));
  },
});

const isFlowMode = computed(() => viewMode.value === 'flow');
const layoutRef = ref<HTMLElement>();
const flowTeleportTarget = ref<HTMLElement | null>(null);
const mobileBodyRef = ref<InstanceType<typeof AttachmentMobileBody> | null>(null);

async function syncFlowTeleportTarget() {
  if (isMobile.value) {
    flowTeleportTarget.value = null;
    return;
  }
  await nextTick();
  await nextTick();
  if (!isFlowMode.value || !layoutRef.value) {
    flowTeleportTarget.value = null;
    return;
  }
  flowTeleportTarget.value = layoutRef.value.querySelector(
    '.vxe-grid--layout-body-content-wrapper',
  ) as HTMLElement | null;
}

watch(viewMode, (mode) => {
  localStorage.setItem(ATTACHMENT_VIEW_MODE_KEY, mode);
  clearSelection();
  syncFlowTeleportTarget();
});

watch(isMobile, () => {
  syncFlowTeleportTarget();
});

watch(flowRows, () => {
  if (isFlowMode.value) {
    syncFlowTeleportTarget();
  }
});

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

function syncGridColumns() {
  gridApi.setGridOptions({
    columns: columns.value as VxeTableGridOptions<AttachmentRow>['columns'],
  } as VxeTableGridOptions<AttachmentRow>);
}

function syncSelection() {
  if (isMobile.value || isFlowMode.value) {
    return;
  }
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as AttachmentRow[];
}

function setViewMode(mode: AttachmentViewMode) {
  if (viewMode.value === mode) {
    return;
  }
  viewMode.value = mode;
  if (!isMobile.value) {
    onRefresh();
  }
}

function clearSelection() {
  if (isMobile.value) {
    mobileBodyRef.value?.clearSelection();
    selectedRows.value = [];
    return;
  }
  if (!isFlowMode.value) {
    gridApi?.grid?.clearCheckboxRow?.();
  }
  selectedRows.value = [];
}

function onRefresh() {
  if (isMobile.value) {
    mobileBodyRef.value?.reload();
    return;
  }
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
  uploadFormModalApi.open();
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
      await performDelete(row);
    },
  });
}

async function performDelete(row: AttachmentRow) {
  await deleteAttachment(row.id);
  message.success('已删除');
  clearSelection();
  onRefresh();
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
  syncGridColumns();
}

onMounted(() => {
  loadCategoryOptions();
  syncFlowTeleportTarget();
});

const columns = computed(() =>
  useColumns(onActionClick, attachmentAccess, {
    categoryTagOptions: buildCategoryTagOptions(categoryOptions.value),
    showCategoryColumn: activeCategory.value === ALL_CATEGORY_KEY,
  }),
);

const [Grid, gridApi] = useVbenVxeGrid<AttachmentRow>({
  showSearchForm: false,
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
    columns: columns.value as VxeTableGridOptions<AttachmentRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          const result = await fetchAttachmentPage({
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
          flowRows.value = result.data ?? [];
          return result;
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
        <AttachmentMobileBody
          v-if="isMobile"
          ref="mobileBodyRef"
          v-model:selected-rows="selectedRows"
          v-model:view-mode="viewMode"
          :active-category="activeCategory"
          :can-batch-delete="
            attachmentAccess.can(ATTACHMENT_PERMISSIONS.BATCH_DELETE)
          "
          :can-delete="attachmentAccess.can(ATTACHMENT_PERMISSIONS.DELETE)"
          :can-upload="attachmentAccess.can(ATTACHMENT_PERMISSIONS.UPLOAD)"
          :columns="columns"
          :resolve-category-filter="resolveCategoryFilter"
          @batch-delete="onBatchDelete"
          @copy="onCopyLink"
          @delete="performDelete"
          @open="onOpen"
          @upload="onUpload"
        />

        <div
          v-else
          ref="layoutRef"
          class="attachment-layout min-h-0 flex-1"
          :class="{ 'attachment-layout--flow': isFlowMode }"
        >
          <Grid>
            <template #toolbar-actions>
              <div class="attachment-toolbar-actions">
                <div
                  v-show="selectedRows.length > 0"
                  class="attachment-toolbar-actions__panel"
                  :class="{ 'vxe-grid-batch-toolbar': selectedRows.length > 0 }"
                >
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
                </div>
                <Button
                  v-show="
                    selectedRows.length === 0 &&
                    attachmentAccess.can(ATTACHMENT_PERMISSIONS.UPLOAD)
                  "
                  class="attachment-toolbar-actions__panel"
                  type="primary"
                  @click="onUpload"
                >
                  <Plus class="size-4" />
                  上传附件
                </Button>
              </div>
            </template>
            <template #toolbar-tools>
              <div class="attachment-view-toggle ml-2">
                <Tooltip title="列表视图">
                  <button
                    class="attachment-view-toggle__btn"
                    :class="{ 'is-active': viewMode === 'list' }"
                    type="button"
                    @click="setViewMode('list')"
                  >
                    <IconifyIcon class="size-4" icon="lucide:list" />
                  </button>
                </Tooltip>
                <Tooltip title="流式视图">
                  <button
                    class="attachment-view-toggle__btn"
                    :class="{ 'is-active': viewMode === 'flow' }"
                    type="button"
                    @click="setViewMode('flow')"
                  >
                    <IconifyIcon class="size-4" icon="lucide:layout-grid" />
                  </button>
                </Tooltip>
              </div>
            </template>
          </Grid>

          <Teleport v-if="isFlowMode && flowTeleportTarget" :to="flowTeleportTarget">
            <AttachmentFlowView
              v-model:selected-ids="selectedFlowIds"
              class="attachment-flow-panel"
              :can-delete="attachmentAccess.can(ATTACHMENT_PERMISSIONS.DELETE)"
              :rows="flowRows"
              @copy="onCopyLink"
              @delete="onDelete"
              @open="onOpen"
            />
          </Teleport>
        </div>
      </main>
    </div>
  </Page>
</template>

<style scoped>
.attachment-layout {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.attachment-layout :deep(.h-full.rounded-md.bg-card) {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
}

.attachment-layout--flow :deep(.vxe-grid) {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
}

.attachment-layout--flow :deep(.vxe-grid--layout-body-wrapper) {
  flex: 1;
  min-height: 0;
}

.attachment-layout--flow :deep(.vxe-grid--layout-body-content-wrapper) {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
  overflow: hidden;
}

.attachment-layout :deep(.vxe-grid--toolbar-wrapper) {
  flex-shrink: 0;
  /* 固定高度，避免批量切换时 flex 内容区重算导致上下跳动 */
  height: calc(34px + 1.2em);
  overflow: hidden;
}

.attachment-layout :deep(.vxe-toolbar) {
  flex-wrap: nowrap;
  align-items: center;
  height: 100%;
}

.attachment-layout :deep(.vxe-grid-batch-toolbar) {
  flex-wrap: nowrap;
}

/* 桌面端：工具区占位隐藏，避免 display:none 后工具栏变矮 */
@media (min-width: 768px) {
  .attachment-layout :deep(.vxe-toolbar:has(.vxe-grid-batch-toolbar) .vxe-tools--operate),
  .attachment-layout :deep(.vxe-toolbar:has(.vxe-grid-batch-toolbar) .vxe-tools--wrapper) {
    display: flex !important;
    visibility: hidden;
    pointer-events: none;
  }
}

.attachment-layout--flow :deep(.vxe-grid--toolbar-wrapper) {
  order: 1;
}

.attachment-toolbar-actions {
  display: flex;
  align-items: center;
  height: 34px;
  flex-wrap: nowrap;
  overflow: hidden;
}

.attachment-toolbar-actions__panel {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  height: 34px;
}

.attachment-layout--flow :deep(.vxe-grid--table-container) {
  display: none !important;
}

.attachment-layout--flow :deep(.vxe-grid--pager-wrapper) {
  flex-shrink: 0;
  order: 3;
}

.attachment-flow-panel {
  flex: 1 1 0;
  min-height: 0;
  order: 2;
  padding: 0 2px 8px;
}

.attachment-view-toggle {
  display: inline-flex;
  overflow: hidden;
  border: 1px solid hsl(var(--border));
  border-radius: 6px;
}

.attachment-view-toggle__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  background: transparent;
  border: 0;
  transition:
    background-color 0.2s,
    color 0.2s;
}

.attachment-view-toggle__btn + .attachment-view-toggle__btn {
  border-left: 1px solid hsl(var(--border));
}

.attachment-view-toggle__btn:hover {
  background: hsl(var(--accent));
  color: hsl(var(--foreground));
}

.attachment-view-toggle__btn.is-active {
  background: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
}

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
  border-radius: var(--radius);
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
