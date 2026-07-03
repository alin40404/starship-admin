<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AttachmentRow } from '#/api/system/attachment';

import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';

import { useDebounceFn, useElementSize } from '@vueuse/core';
import { Button, Input, message, Tooltip } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { fetchAttachmentPickerPage } from '#/api/system/attachment';
import AttachmentFlowView from '#/views/resource/attachment/modules/flow-view.vue';

import { usePickerColumns } from './picker-data';
import type { AttachmentAcceptMode } from './types';
import { isAttachmentAcceptable, resolveMimeFilter } from './utils';

const ATTACHMENT_PICKER_VIEW_MODE_KEY = 'attachment-picker-view-mode';

type AttachmentViewMode = 'flow' | 'list';

const props = withDefaults(
  defineProps<{
    accept?: AttachmentAcceptMode;
    initialUrls?: string[];
    multiple?: boolean;
    open?: boolean;
  }>(),
  {
    accept: 'all',
    initialUrls: () => [],
    multiple: false,
    open: false,
  },
);

const emit = defineEmits<{
  confirm: [urls: string[]];
  'update:open': [open: boolean];
}>();

const fileNameQuery = ref('');
const selectedRows = ref<AttachmentRow[]>([]);
const flowRows = ref<AttachmentRow[]>([]);
const layoutRef = ref<HTMLElement>();
const flowTeleportTarget = ref<HTMLElement | null>(null);
const gridWrapRef = useTemplateRef<HTMLElement>('gridWrapRef');
const { height: gridWrapHeight } = useElementSize(gridWrapRef);

const viewMode = ref<AttachmentViewMode>(
  (localStorage.getItem(ATTACHMENT_PICKER_VIEW_MODE_KEY) as AttachmentViewMode) ||
    'list',
);

const selectedFlowIds = computed({
  get: () => selectedRows.value.map((row) => row.id),
  set: (ids: number[]) => {
    const normalizedIds = props.multiple ? ids : ids.slice(-1);
    selectedRows.value = flowRows.value.filter((row) =>
      normalizedIds.includes(row.id),
    );
  },
});

const isFlowMode = computed(() => viewMode.value === 'flow');
const hasSelection = computed(() => selectedRows.value.length > 0);

async function syncFlowTeleportTarget() {
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

function syncSelection() {
  if (isFlowMode.value) {
    return;
  }
  if (props.multiple) {
    const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
    selectedRows.value = records as AttachmentRow[];
    return;
  }
  const record = gridApi?.grid?.getRadioRecord?.();
  selectedRows.value = record ? [record as AttachmentRow] : [];
}

function clearSelection() {
  if (!isFlowMode.value) {
    gridApi?.grid?.clearCheckboxRow?.();
    gridApi?.grid?.clearRadioRow?.();
  }
  selectedRows.value = [];
}

function setViewMode(mode: AttachmentViewMode) {
  if (viewMode.value === mode) {
    return;
  }
  viewMode.value = mode;
  localStorage.setItem(ATTACHMENT_PICKER_VIEW_MODE_KEY, mode);
  clearSelection();
  void refreshGrid();
  syncFlowTeleportTarget();
}

function buildSelectionColumn() {
  return props.multiple
    ? {
        align: 'center' as const,
        fixed: 'left' as const,
        type: 'checkbox' as const,
        width: 48,
      }
    : {
        align: 'center' as const,
        fixed: 'left' as const,
        type: 'radio' as const,
        width: 48,
      };
}

function buildPickerColumns() {
  return [
    buildSelectionColumn(),
    ...usePickerColumns({
      onActionClick,
      showCategoryColumn: true,
    }),
  ];
}

function syncGridColumns() {
  gridApi.setGridOptions({
    checkboxConfig: props.multiple
      ? {
          checkMethod: ({ row }: { row: AttachmentRow }) =>
            isAttachmentAcceptable(row, props.accept),
          highlight: true,
        }
      : undefined,
    columns: buildPickerColumns(),
    radioConfig: props.multiple
      ? undefined
      : {
          checkMethod: ({ row }: { row: AttachmentRow }) =>
            isAttachmentAcceptable(row, props.accept),
          highlight: true,
          trigger: 'row',
        },
  } as VxeTableGridOptions<AttachmentRow>);
}

async function refreshGrid() {
  await gridApi.query();
  await nextTick();
  restoreInitialSelection();
  syncFlowTeleportTarget();
}

const debouncedRefreshGrid = useDebounceFn(() => {
  void refreshGrid();
}, 400);

watch(fileNameQuery, () => {
  debouncedRefreshGrid();
});

function restoreInitialSelection() {
  if (props.initialUrls.length === 0) {
    return;
  }
  const tableData = isFlowMode.value
    ? flowRows.value
    : ((gridApi?.grid?.getData?.() ?? []) as AttachmentRow[]);
  const matched = tableData.filter(
    (row) => row.file_url && props.initialUrls.includes(row.file_url),
  );
  if (props.multiple) {
    if (isFlowMode.value) {
      selectedRows.value = matched;
    } else {
      for (const row of matched) {
        gridApi?.grid?.setCheckboxRow?.(row, true);
      }
    }
  } else {
    const row = matched[0];
    if (!row) {
      return;
    }
    if (isFlowMode.value) {
      selectedRows.value = [row];
    } else {
      gridApi?.grid?.setRadioRow?.(row);
    }
  }
  syncSelection();
}

function confirmWithRows(rows: AttachmentRow[]) {
  const urls = rows
    .map((row) => row.file_url)
    .filter((url): url is string => Boolean(url));
  if (urls.length === 0) {
    message.warning(props.multiple ? '请至少选择一个附件' : '请选择一个附件');
    return;
  }
  emit('confirm', props.multiple ? urls : [urls[0]!]);
  modalApi.close();
}

function handleConfirm() {
  if (!hasSelection.value) {
    return;
  }
  confirmWithRows(selectedRows.value);
}

function onOpenAttachment(row: AttachmentRow) {
  if (!row.file_url) {
    return;
  }
  window.open(row.file_url, '_blank', 'noopener,noreferrer');
}

async function onCopyLink(row: AttachmentRow) {
  if (!row.file_url) {
    return;
  }
  try {
    await navigator.clipboard.writeText(row.file_url);
    message.success('链接已复制');
  } catch {
    message.error('复制失败，请手动复制');
  }
}

function onActionClick({ code, row }: OnActionClickParams<AttachmentRow>) {
  if (code === 'open') {
    onOpenAttachment(row);
  } else if (code === 'copy') {
    void onCopyLink(row);
  }
}

const [Grid, gridApi] = useVbenVxeGrid<AttachmentRow>({
  class: 'h-full min-h-0',
  gridClass: 'p-0',
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
    radioChange: syncSelection,
  },
  gridOptions: {
    columns: buildPickerColumns(),
    height: 360,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }) => {
          const order = sort?.order;
          const result = await fetchAttachmentPickerPage({
            page: page.currentPage,
            per_page: page.pageSize,
            file_name: fileNameQuery.value.trim() || undefined,
            mime_type: resolveMimeFilter(props.accept),
            sort_field: sort?.field,
            sort_order:
              order === 'asc' || order === 'desc' ? order : undefined,
          });
          const data =
            props.accept === 'image'
              ? result.data.filter((row) =>
                  isAttachmentAcceptable(row, props.accept),
                )
              : result.data;
          flowRows.value = data;
          return props.accept === 'image' ? { ...result, data } : result;
        },
      },
      sort: true,
    },
    rowConfig: { keyField: 'id' },
    sortConfig: { remote: true },
    toolbarConfig: { refresh: true },
  } as VxeTableGridOptions<AttachmentRow>,
});

watch(
  [gridWrapHeight, isFlowMode],
  ([height, flow]) => {
    gridApi.setGridOptions({
      height: flow ? 'auto' : Math.max(Math.floor(height), 240),
    });
  },
  { immediate: true },
);

watch(flowRows, () => {
  if (isFlowMode.value) {
    syncFlowTeleportTarget();
  }
});

const [Modal, modalApi] = useVbenModal({
  fullscreenButton: false,
  onOpenChange(isOpen: boolean) {
    emit('update:open', isOpen);
    if (!isOpen) {
      fileNameQuery.value = '';
      clearSelection();
      return;
    }
    syncGridColumns();
    void refreshGrid();
  },
});

watch(
  () => props.multiple,
  () => {
    syncGridColumns();
  },
  { immediate: true },
);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      modalApi.open();
    } else {
      modalApi.close();
    }
  },
);
</script>

<template>
  <Modal
    class="w-[1180px] max-w-[96vw]"
    content-class="flex min-h-0 flex-1 flex-col overflow-hidden !p-0"
    title="选择附件"
  >
    <div
      class="bg-card flex h-[min(76vh,720px)] min-h-[460px] flex-col overflow-hidden p-3"
    >
      <div
        ref="gridWrapRef"
        class="attachment-picker-layout min-h-0 flex-1"
        :class="{ 'attachment-picker-layout--flow': isFlowMode }"
      >
        <div
          ref="layoutRef"
          class="attachment-picker-layout__inner h-full min-h-0"
          :class="{ 'attachment-picker-layout__inner--flow': isFlowMode }"
        >
          <Grid>
            <template #toolbar-actions>
              <div class="attachment-picker-search">
                <Input
                  v-model:value="fileNameQuery"
                  allow-clear
                  class="attachment-picker-search__input"
                  placeholder="搜索文件名"
                />
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

          <Teleport
            v-if="isFlowMode && flowTeleportTarget"
            :to="flowTeleportTarget"
          >
            <AttachmentFlowView
              v-model:selected-ids="selectedFlowIds"
              class="attachment-flow-panel"
              show-actions
              :show-copy="false"
              :rows="flowRows"
              @open="onOpenAttachment"
            />
          </Teleport>
        </div>
      </div>
    </div>

    <template #footer>
      <Button @click="modalApi.close()">取消</Button>
      <Button
        type="primary"
        class="attachment-picker-confirm"
        :class="{ 'is-inactive': !hasSelection }"
        @click="handleConfirm"
      >
        确定
      </Button>
    </template>
  </Modal>
</template>

<style scoped>
.attachment-picker-confirm.is-inactive {
  cursor: not-allowed;
  background: #91caff;
  border-color: #91caff;
}

.attachment-picker-confirm.is-inactive:hover {
  background: #91caff !important;
  border-color: #91caff !important;
  color: #fff !important;
}
.attachment-picker-layout {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.attachment-picker-layout__inner {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.attachment-picker-layout__inner :deep(.h-full.rounded-md.bg-card) {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
}

.attachment-picker-layout--flow :deep(.vxe-grid) {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
}

.attachment-picker-layout--flow :deep(.vxe-grid--layout-body-wrapper) {
  flex: 1;
  min-height: 0;
}

.attachment-picker-layout--flow :deep(.vxe-grid--layout-body-content-wrapper) {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
  overflow: hidden;
}

.attachment-picker-layout--flow :deep(.vxe-grid--toolbar-wrapper) {
  flex-shrink: 0;
  order: 1;
}

.attachment-picker-layout--flow :deep(.vxe-grid--table-container) {
  display: none !important;
}

.attachment-picker-layout--flow :deep(.vxe-grid--pager-wrapper) {
  flex-shrink: 0;
  order: 3;
}

.attachment-flow-panel {
  flex: 1 1 0;
  min-height: 0;
  order: 2;
  padding: 0 2px 8px;
}

.attachment-picker-search {
  display: flex;
  align-items: center;
  min-width: 0;
}

.attachment-picker-search__input {
  width: 220px;
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
</style>
