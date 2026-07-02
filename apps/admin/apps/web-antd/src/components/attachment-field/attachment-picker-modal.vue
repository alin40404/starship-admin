<script lang="ts" setup>
import type { VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AttachmentRow } from '#/api/system/attachment';

import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { useElementSize } from '@vueuse/core';
import { message } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  fetchAttachmentPickerCategories,
  fetchAttachmentPickerPage,
} from '#/api/system/attachment';
import { buildCategoryTagOptions } from '#/views/resource/attachment/data';

import { usePickerColumns, usePickerGridFormSchema } from './picker-data';
import type { AttachmentAcceptMode } from './types';
import { isAttachmentAcceptable, resolveMimeFilter } from './utils';

const ALL_CATEGORY_KEY = '__all__';

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

const activeCategory = ref(ALL_CATEGORY_KEY);
const categoryOptions = ref<{ label: string; value: string }[]>([]);
const selectedRows = ref<AttachmentRow[]>([]);
const gridWrapRef = useTemplateRef<HTMLElement>('gridWrapRef');
const { height: gridWrapHeight } = useElementSize(gridWrapRef);

const categoryTagOptions = computed(() =>
  buildCategoryTagOptions(categoryOptions.value),
);

function resolveCategoryFilter() {
  if (activeCategory.value === ALL_CATEGORY_KEY) {
    return undefined;
  }
  return activeCategory.value;
}

function syncSelection() {
  if (props.multiple) {
    const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
    selectedRows.value = records as AttachmentRow[];
    return;
  }
  const record = gridApi?.grid?.getRadioRecord?.();
  selectedRows.value = record ? [record as AttachmentRow] : [];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  gridApi?.grid?.clearRadioRow?.();
  selectedRows.value = [];
}

function onCategoryChange(value: string) {
  if (activeCategory.value === value) {
    return;
  }
  activeCategory.value = value;
  clearSelection();
  syncGridColumns();
  void refreshGrid();
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

function syncGridColumns() {
  gridApi.setGridOptions({
    checkboxConfig: props.multiple
      ? {
          checkMethod: ({ row }: { row: AttachmentRow }) =>
            isAttachmentAcceptable(row, props.accept),
          highlight: true,
        }
      : undefined,
    columns: [
      buildSelectionColumn(),
      ...usePickerColumns({
        categoryTagOptions: categoryTagOptions.value,
        showCategoryColumn: activeCategory.value === ALL_CATEGORY_KEY,
      }),
    ],
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
}

function restoreInitialSelection() {
  if (props.initialUrls.length === 0) {
    return;
  }
  const tableData = (gridApi?.grid?.getData?.() ?? []) as AttachmentRow[];
  for (const row of tableData) {
    if (!row.file_url || !props.initialUrls.includes(row.file_url)) {
      continue;
    }
    if (props.multiple) {
      gridApi?.grid?.setCheckboxRow?.(row, true);
    } else {
      gridApi?.grid?.setRadioRow?.(row);
      break;
    }
  }
  syncSelection();
}

async function loadCategories() {
  const rows = await fetchAttachmentPickerCategories();
  categoryOptions.value = rows.map((item) => ({
    label: item.label,
    value: item.value,
  }));
  syncGridColumns();
}

const [Grid, gridApi] = useVbenVxeGrid<AttachmentRow>({
  class: 'h-full min-h-0',
  gridClass: 'p-0',
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
    radioChange: syncSelection,
  },
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: usePickerGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: '查询' },
  },
  gridOptions: {
    columns: [
      buildSelectionColumn(),
      ...usePickerColumns({ showCategoryColumn: true }),
    ],
    height: 360,
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) => {
          const result = await fetchAttachmentPickerPage({
            page: page.currentPage,
            per_page: page.pageSize,
            file_name: formValues?.file_name || undefined,
            category: resolveCategoryFilter(),
            mime_type: resolveMimeFilter(props.accept),
          });
          if (props.accept === 'image') {
            return {
              ...result,
              data: result.data.filter((row) =>
                isAttachmentAcceptable(row, props.accept),
              ),
            };
          }
          return result;
        },
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { refresh: true, search: true },
  } as VxeTableGridOptions<AttachmentRow>,
});

watch(
  gridWrapHeight,
  (height) => {
    gridApi.setGridOptions({
      height: Math.max(Math.floor(height), 240),
    });
  },
  { immediate: true },
);

watch(
  () => props.multiple,
  () => {
    syncGridColumns();
  },
);

function handleConfirm() {
  const urls = selectedRows.value
    .map((row) => row.file_url)
    .filter((url): url is string => Boolean(url));
  if (urls.length === 0) {
    message.warning(props.multiple ? '请至少选择一个附件' : '请选择一个附件');
    return;
  }
  if (!props.multiple && urls.length > 1) {
    emit('confirm', [urls[0]!]);
  } else {
    emit('confirm', urls);
  }
  modalApi.close();
}

const [Modal, modalApi] = useVbenModal({
  confirmText: '确定',
  fullscreenButton: false,
  onConfirm: handleConfirm,
  onOpenChange(isOpen: boolean) {
    emit('update:open', isOpen);
    if (!isOpen) {
      activeCategory.value = ALL_CATEGORY_KEY;
      clearSelection();
      return;
    }
    void loadCategories().then(() => refreshGrid());
  },
});

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
    class="w-[920px] max-w-[96vw]"
    content-class="flex min-h-0 flex-1 flex-col overflow-hidden !p-0"
    title="选择附件"
  >
    <div
      class="bg-card flex h-[min(72vh,640px)] min-h-[420px] flex-col overflow-hidden md:flex-row"
    >
      <aside
        class="attachment-picker-panel flex shrink-0 flex-col overflow-hidden border-b border-border md:w-[168px] md:border-b-0 md:border-r"
      >
        <div class="attachment-picker-header">附件分类</div>
        <div class="attachment-picker-tabs">
          <button
            class="attachment-picker-item"
            :class="{ 'is-active': activeCategory === ALL_CATEGORY_KEY }"
            type="button"
            @click="onCategoryChange(ALL_CATEGORY_KEY)"
          >
            全部
          </button>
          <button
            v-for="item in categoryOptions"
            :key="item.value"
            class="attachment-picker-item"
            :class="{ 'is-active': activeCategory === item.value }"
            type="button"
            @click="onCategoryChange(item.value)"
          >
            {{ item.label }}
          </button>
        </div>
      </aside>

      <main class="flex min-h-0 flex-1 flex-col overflow-hidden p-3">
        <div ref="gridWrapRef" class="min-h-0 flex-1">
          <Grid />
        </div>
      </main>
    </div>
  </Modal>
</template>

<style scoped>
.attachment-picker-header {
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

.attachment-picker-tabs {
  display: flex;
  flex: 1;
  flex-direction: row;
  gap: 8px;
  overflow-x: auto;
  padding: 8px;
}

.attachment-picker-item {
  flex-shrink: 0;
  border: 1px solid hsl(var(--border));
  border-radius: 9999px;
  background: transparent;
  padding: 6px 14px;
  font-size: 14px;
  color: hsl(var(--muted-foreground));
  white-space: nowrap;
  cursor: pointer;
}

.attachment-picker-item.is-active {
  border-color: hsl(var(--primary) / 0.25);
  background-color: hsl(var(--primary) / 0.08);
  color: hsl(var(--primary));
  font-weight: 500;
}

@media (min-width: 768px) {
  .attachment-picker-header {
    justify-content: flex-start;
  }

  .attachment-picker-tabs {
    flex-direction: column;
    gap: 0;
    overflow-y: auto;
    padding: 0;
  }

  .attachment-picker-item {
    width: 100%;
    border: 0;
    border-left: 2px solid transparent;
    border-radius: 0;
    padding: 10px 12px;
    text-align: left;
    color: hsl(var(--foreground));
  }

  .attachment-picker-item.is-active {
    border-left-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.08);
    color: hsl(var(--primary));
  }
}
</style>
