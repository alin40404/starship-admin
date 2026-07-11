<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AttachmentRow } from '#/api/system/attachment';

import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

import { useAccess } from '@vben/access';
import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { IconifyIcon, Plus } from '@vben/icons';
import { $t } from '@vben/locales';
import { usePreferences } from '@vben/preferences';

import { useDebounceFn, useElementSize } from '@vueuse/core';
import { Button, Input, message, Tooltip } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { fetchAttachmentPickerPage, uploadAttachment } from '#/api/system/attachment';
import { ATTACHMENT_PERMISSIONS } from '#/constants/attachment-permissions';
import AttachmentFlowView from '#/views/resource/attachment/modules/flow-view.vue';

import { usePickerColumns } from './picker-data';
import type { AttachmentAcceptMode } from './types';
import { isAttachmentAcceptable, resolveMimeFilter, resolveUploadAccept } from './utils';

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

const { isMobile } = usePreferences();
const { hasAccessByCodes } = useAccess();

const canUpload = computed(() =>
  hasAccessByCodes([ATTACHMENT_PERMISSIONS.UPLOAD]),
);
const uploadAccept = computed(() => resolveUploadAccept(props.accept));
const uploading = ref(false);

const fileNameQuery = ref('');
const selectedRows = ref<AttachmentRow[]>([]);
const flowRows = ref<AttachmentRow[]>([]);
const flowTotal = ref(0);
const flowPage = ref(1);
const flowLoadingMore = ref(false);
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
const flowHasMore = computed(() => flowRows.value.length < flowTotal.value);
const hasSelection = computed(() => selectedRows.value.length > 0);

const contentWrapClass = computed(() =>
  isMobile.value
    ? 'attachment-picker-body attachment-picker-body--mobile bg-card flex min-h-0 flex-1 flex-col overflow-hidden p-2'
    : 'attachment-picker-body bg-card flex h-[min(76vh,720px)] min-h-[460px] flex-col overflow-hidden p-3',
);

const layoutClass = computed(() => ({
  'attachment-picker-layout--flow': isFlowMode.value,
  'attachment-picker-layout--mobile': isMobile.value,
}));

type PopupApi = {
  close: () => void;
  open: () => void;
};

function getActivePopupApi(): PopupApi {
  return (isMobile.value ? drawerApi : modalApi) as PopupApi;
}

function closePopup() {
  getActivePopupApi().close();
}

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

async function isGridReady() {
  return typeof gridApi.grid?.commitProxy === 'function';
}

async function waitForGridReady(maxAttempts = 20) {
  for (let i = 0; i < maxAttempts; i++) {
    if (await isGridReady()) {
      return true;
    }
    await nextTick();
  }
  return false;
}

async function refreshGrid() {
  if (!(await waitForGridReady())) {
    return;
  }
  await gridApi.query();
  await nextTick();
  restoreInitialSelection();
  syncFlowTeleportTarget();
}

function selectUploadedRows(uploadedRows: AttachmentRow[]) {
  const acceptableRows = filterPickerRows(uploadedRows);
  if (acceptableRows.length === 0) {
    return;
  }

  const uploadedIds = new Set(acceptableRows.map((row) => row.id));
  const tableData = isFlowMode.value
    ? flowRows.value
    : ((gridApi?.grid?.getData?.() ?? []) as AttachmentRow[]);
  const matched = tableData.filter((row) => uploadedIds.has(row.id));
  const rowsToSelect = matched.length > 0 ? matched : acceptableRows;

  if (props.multiple) {
    const existing = selectedRows.value.filter((row) => !uploadedIds.has(row.id));
    selectedRows.value = [...existing, ...rowsToSelect];
    if (!isFlowMode.value) {
      for (const row of matched) {
        gridApi?.grid?.setCheckboxRow?.(row, true);
      }
    }
    return;
  }

  const row = rowsToSelect[rowsToSelect.length - 1];
  if (!row) {
    return;
  }
  selectedRows.value = [row];
  if (!isFlowMode.value && matched.length > 0) {
    gridApi?.grid?.setRadioRow?.(row);
  }
}

async function onUploadSuccess(uploadedRows: AttachmentRow[] = []) {
  await refreshGrid();
  selectUploadedRows(uploadedRows);
}

async function uploadPickerFiles(files: File[]) {
  if (files.length === 0) {
    return;
  }

  uploading.value = true;
  const uploadedRows: AttachmentRow[] = [];
  try {
    for (const file of files) {
      uploadedRows.push(await uploadAttachment(file));
    }
    if (uploadedRows.length === 0) {
      message.error($t('resource.attachment.field.uploadNoUrl'));
      return;
    }
    message.success($t('resource.attachment.field.uploadSuccess'));
    await onUploadSuccess(uploadedRows);
  } catch {
    message.error($t('resource.attachment.field.uploadFailed'));
  } finally {
    uploading.value = false;
  }
}

function onOpenUpload() {
  if (!canUpload.value) {
    message.warning($t('resource.attachment.field.noUploadPermission'));
    return;
  }
  if (uploading.value) {
    return;
  }

  const input = document.createElement('input');
  input.type = 'file';
  input.multiple = true;
  if (uploadAccept.value) {
    input.accept = uploadAccept.value;
  }
  input.style.display = 'none';
  input.addEventListener(
    'change',
    () => {
      const files = input.files ? [...input.files] : [];
      input.remove();
      void uploadPickerFiles(files);
    },
    { once: true },
  );
  document.body.appendChild(input);
  input.click();
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
    message.warning(
      props.multiple
        ? $t('resource.attachment.picker.selectAtLeastOne')
        : $t('resource.attachment.picker.selectOne'),
    );
    return;
  }
  emit('confirm', props.multiple ? urls : [urls[0]!]);
  closePopup();
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
    message.success($t('resource.attachment.linkCopied'));
  } catch {
    message.error($t('resource.attachment.copyFailed'));
  }
}

function onActionClick({ code, row }: OnActionClickParams<AttachmentRow>) {
  if (code === 'open') {
    onOpenAttachment(row);
  } else if (code === 'copy') {
    void onCopyLink(row);
  }
}

function filterPickerRows(rows: AttachmentRow[]) {
  if (props.accept !== 'image') {
    return rows;
  }
  return rows.filter((row) => isAttachmentAcceptable(row, props.accept));
}

function buildPickerQueryParams(
  pageNum: number,
  pageSize: number,
  sort?: { field?: string; order?: null | string },
) {
  const order = sort?.order;
  return {
    page: pageNum,
    per_page: pageSize,
    file_name: fileNameQuery.value.trim() || undefined,
    mime_type: resolveMimeFilter(props.accept),
    sort_field: sort?.field,
    sort_order: order === 'asc' || order === 'desc' ? order : undefined,
  };
}

function getGridPageSize() {
  const pager = gridApi?.grid?.getProxyInfo?.()?.pager;
  return pager?.pageSize ?? 20;
}

async function loadMoreFlow() {
  if (!isFlowMode.value || flowLoadingMore.value || !flowHasMore.value) {
    return;
  }
  flowLoadingMore.value = true;
  try {
    const proxyInfo = gridApi?.grid?.getProxyInfo?.();
    const result = await fetchAttachmentPickerPage(
      buildPickerQueryParams(
        flowPage.value + 1,
        getGridPageSize(),
        proxyInfo?.sort,
      ),
    );
    const data = filterPickerRows(result.data ?? []);
    flowRows.value = [...flowRows.value, ...data];
    flowPage.value += 1;
    flowTotal.value = result.total;
  } finally {
    flowLoadingMore.value = false;
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
          const pageSize = page.pageSize;
          const pageNum = isFlowMode.value ? 1 : page.currentPage;
          const result = await fetchAttachmentPickerPage(
            buildPickerQueryParams(pageNum, pageSize, sort),
          );
          const data = filterPickerRows(result.data ?? []);
          if (isFlowMode.value) {
            flowRows.value = data;
            flowPage.value = 1;
            flowTotal.value = result.total;
          }
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
  [gridWrapHeight, isFlowMode, isMobile],
  async ([height, flow]) => {
    if (!(await isGridReady())) {
      return;
    }
    gridApi.setGridOptions({
      height:
        flow && !isMobile.value
          ? 'auto'
          : Math.max(Math.floor(height), isMobile.value ? 200 : 240),
    });
  },
  { immediate: true },
);

watch(flowRows, () => {
  if (isFlowMode.value) {
    syncFlowTeleportTarget();
  }
});

function handlePopupOpenChange(isOpen: boolean) {
  emit('update:open', isOpen);
  if (!isOpen) {
    fileNameQuery.value = '';
    clearSelection();
    flowRows.value = [];
    flowTotal.value = 0;
    flowPage.value = 1;
    return;
  }
  syncGridColumns();
}

function handlePopupOpened() {
  void refreshGrid();
}

const [Modal, modalApi] = useVbenModal({
  destroyOnClose: true,
  fullscreenButton: false,
  onOpenChange: handlePopupOpenChange,
  onOpened: handlePopupOpened,
});

const [Drawer, drawerApi] = useVbenDrawer({
  destroyOnClose: true,
  onOpenChange: handlePopupOpenChange,
  onOpened: handlePopupOpened,
  showCancelButton: false,
  showConfirmButton: false,
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
    const api = getActivePopupApi();
    if (isOpen) {
      api.open();
    } else {
      api.close();
    }
  },
);
</script>

<template>
  <Modal
    v-if="!isMobile"
    class="w-[1180px] max-w-[96vw]"
    content-class="flex min-h-0 flex-1 flex-col overflow-hidden !p-0"
    :title="$t('resource.attachment.picker.title')"
  >
    <div :class="contentWrapClass">
      <div
        ref="gridWrapRef"
        class="attachment-picker-layout min-h-0 flex-1"
        :class="layoutClass"
      >
        <div
          ref="layoutRef"
          class="attachment-picker-layout__inner h-full min-h-0"
          :class="{ 'attachment-picker-layout__inner--flow': isFlowMode }"
        >
          <Grid>
            <template #toolbar-actions>
              <div class="attachment-picker-toolbar-actions">
                <div class="attachment-picker-search">
                  <Input
                    v-model:value="fileNameQuery"
                    allow-clear
                    class="attachment-picker-search__input"
                    :placeholder="$t('resource.attachment.picker.searchPlaceholder')"
                  />
                </div>
                <Button
                  v-if="canUpload"
                  class="ml-2 shrink-0"
                  :loading="uploading"
                  type="primary"
                  @click="onOpenUpload"
                >
                  <Plus class="size-4" />
                  {{ $t('resource.attachment.picker.upload') }}
                </Button>
              </div>
            </template>
            <template #toolbar-tools>
              <div class="attachment-view-toggle ml-2">
                <Tooltip :title="$t('resource.attachment.listView')">
                  <button
                    class="attachment-view-toggle__btn"
                    :class="{ 'is-active': viewMode === 'list' }"
                    type="button"
                    @click="setViewMode('list')"
                  >
                    <IconifyIcon class="size-4" icon="lucide:list" />
                  </button>
                </Tooltip>
                <Tooltip :title="$t('resource.attachment.flowView')">
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
              :has-more="flowHasMore"
              :loading-more="flowLoadingMore"
              show-actions
              :show-copy="false"
              :rows="flowRows"
              :total="flowTotal"
              @load-more="loadMoreFlow"
              @open="onOpenAttachment"
            />
          </Teleport>
        </div>
      </div>
    </div>

    <template #footer>
      <Button @click="closePopup()">{{ $t('common.cancel') }}</Button>
      <Button
        type="primary"
        class="attachment-picker-confirm"
        :class="{ 'is-inactive': !hasSelection }"
        @click="handleConfirm"
      >
        {{ $t('common.confirm') }}
      </Button>
    </template>
  </Modal>

  <Drawer
    v-else
    class="h-full max-h-[100dvh] w-full"
    content-class="flex min-h-0 flex-1 flex-col overflow-hidden !p-0"
    :title="$t('resource.attachment.picker.title')"
  >
    <div :class="contentWrapClass">
      <div
        ref="gridWrapRef"
        class="attachment-picker-layout min-h-0 flex-1"
        :class="layoutClass"
      >
        <div
          ref="layoutRef"
          class="attachment-picker-layout__inner h-full min-h-0"
          :class="{ 'attachment-picker-layout__inner--flow': isFlowMode }"
        >
          <Grid>
            <template #toolbar-actions>
              <div class="attachment-picker-toolbar-actions">
                <div class="attachment-picker-search">
                  <Input
                    v-model:value="fileNameQuery"
                    allow-clear
                    class="attachment-picker-search__input"
                    :placeholder="$t('resource.attachment.picker.searchPlaceholder')"
                  />
                </div>
                <Button
                  v-if="canUpload"
                  class="ml-2 shrink-0"
                  :loading="uploading"
                  type="primary"
                  @click="onOpenUpload"
                >
                  <Plus class="size-4" />
                  {{ $t('resource.attachment.picker.upload') }}
                </Button>
              </div>
            </template>
            <template #toolbar-tools>
              <div class="attachment-view-toggle ml-2">
                <Tooltip :title="$t('resource.attachment.listView')">
                  <button
                    class="attachment-view-toggle__btn"
                    :class="{ 'is-active': viewMode === 'list' }"
                    type="button"
                    @click="setViewMode('list')"
                  >
                    <IconifyIcon class="size-4" icon="lucide:list" />
                  </button>
                </Tooltip>
                <Tooltip :title="$t('resource.attachment.flowView')">
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
              :has-more="flowHasMore"
              :loading-more="flowLoadingMore"
              show-actions
              :show-copy="false"
              :rows="flowRows"
              :total="flowTotal"
              @load-more="loadMoreFlow"
              @open="onOpenAttachment"
            />
          </Teleport>
        </div>
      </div>
    </div>

    <template #footer>
      <Button @click="closePopup()">{{ $t('common.cancel') }}</Button>
      <Button
        type="primary"
        class="attachment-picker-confirm"
        :class="{ 'is-inactive': !hasSelection }"
        @click="handleConfirm"
      >
        {{ $t('common.confirm') }}
      </Button>
    </template>
  </Drawer>
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
  display: none !important;
}

.attachment-flow-panel {
  flex: 1 1 0;
  min-height: 0;
  order: 2;
  padding: 8px 2px 8px;
}

.attachment-picker-search {
  display: flex;
  align-items: center;
  min-width: 0;
}

.attachment-picker-toolbar-actions {
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

.attachment-picker-body--mobile {
  height: 100%;
}

.attachment-picker-layout--mobile.attachment-picker-layout--flow
  .attachment-flow-panel {
  padding: 0;
}

.attachment-picker-layout--mobile.attachment-picker-layout--flow
  :deep(.attachment-flow-view__main) {
  flex: 1;
  min-height: 0;
}

.attachment-picker-layout--mobile.attachment-picker-layout--flow
  :deep(.attachment-flow-grid) {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  padding: 0;
}

.attachment-picker-layout--mobile.attachment-picker-layout--flow
  :deep(.attachment-flow-item) {
  aspect-ratio: 1;
}

.attachment-picker-layout--mobile:not(.attachment-picker-layout--flow)
  :deep(.h-full.rounded-md.bg-card) {
  flex: 1;
  min-height: 0;
}

.attachment-picker-layout--mobile .attachment-picker-toolbar-actions {
  flex: 1;
  min-width: 0;
}

.attachment-picker-layout--mobile .attachment-picker-search {
  flex: 1;
  min-width: 0;
}

.attachment-picker-layout--mobile .attachment-picker-search__input {
  width: 100%;
  max-width: none;
}

.attachment-picker-layout--mobile :deep(.vxe-grid--pager-wrapper) {
  flex-shrink: 0;
}
</style>
