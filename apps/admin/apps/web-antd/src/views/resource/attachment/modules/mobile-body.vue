<script lang="ts" setup>
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { AttachmentRow } from '#/api/system/attachment';

import { computed, nextTick, onMounted, provide, reactive, ref, watch } from 'vue';

import { IconifyIcon, Plus } from '@vben/icons';
import { $t } from '@vben/locales';

import { useVbenForm } from '#/adapter/form';
import {
  MobileCrudList,
  useMobileCrudList,
} from '#/adapter/mobile-list';
import { VxeToolbar } from '@vben/plugins/vxe-table';
import { fetchAttachmentPage } from '#/api/system/attachment';
import {
  Badge,
  Button,
  Drawer,
  Input,
  Space,
  Spin,
  Tooltip,
} from 'ant-design-vue';

import {
  ALL_ATTACHMENT_CATEGORY_KEY,
  buildAttachmentMobileCrudConfig,
  resolveAttachmentCategoryFilter,
} from '../data';
import AttachmentFlowView from './flow-view.vue';

type AttachmentViewMode = 'flow' | 'list';

const props = defineProps<{
  activeCategory: string;
  canBatchDelete: boolean;
  canDelete: boolean;
  canUpload: boolean;
  categoryOptions: { label: string; value: string }[];
  columns: CrudGridColumn<AttachmentRow>[];
  viewMode: AttachmentViewMode;
}>();

const emit = defineEmits<{
  batchDelete: [];
  categoryChange: [value: string];
  copy: [AttachmentRow];
  delete: [AttachmentRow];
  open: [AttachmentRow];
  upload: [];
  'update:viewMode': [AttachmentViewMode];
}>();

const selectedRows = defineModel<AttachmentRow[]>('selectedRows', {
  default: () => [],
});

const mobileListRef = ref<{
  clearSelection: () => void;
  reload: () => Promise<void>;
} | null>(null);
const filterDrawerOpen = ref(false);
const flowScrollBodyRef = ref<HTMLElement | null>(null);
const isFlowMode = computed(() => props.viewMode === 'flow');
const isMaximized = ref(false);

const listToolbarConfig = {
  custom: true,
  refresh: true,
  zoom: true,
};

const flowToolbarRefreshOptions = {
  queryMethod: () => reloadFlow(),
};

provide('$xeGrid', {
  isMaximized: () => isMaximized.value,
  triggerZoomEvent: () => {
    isMaximized.value = !isMaximized.value;
  },
});

const mobileConfig = computed(() => {
  const categoryFilter =
    props.activeCategory === ALL_ATTACHMENT_CATEGORY_KEY
      ? undefined
      : props.activeCategory;

  const base = buildAttachmentMobileCrudConfig({
    categoryOptions: props.categoryOptions,
    columns: props.columns,
    query: async ({ filters, keyword, page, pageSize, sortField, sortOrder }) => {
      const result = await fetchAttachmentPage({
        page,
        per_page: pageSize,
        keyword: keyword || undefined,
        file_name: filters?.file_name || undefined,
        file_ext: filters?.file_ext || undefined,
        storage: filters?.storage || undefined,
        category: resolveAttachmentCategoryFilter(filters?.category),
        sort_field: sortField,
        sort_order: sortOrder,
      });
      return {
        data: result.data ?? [],
        total: result.total,
      };
    },
  });

  const baseOperationClick = base.onOperationClick;
  return {
    ...base,
    onOperationClick: ({ code, row }: { code: string; row: AttachmentRow }) => {
      if (code === 'delete') {
        emit('delete', row);
        return;
      }
      baseOperationClick?.({ code, row });
    },
    search: {
      ...base.search,
      defaultFilters: {
        category: categoryFilter,
      },
      onFilterSubmit: (values: Record<string, any>) => {
        const nextCategory =
          values.category ?? ALL_ATTACHMENT_CATEGORY_KEY;
        if (nextCategory !== props.activeCategory) {
          emit('categoryChange', nextCategory);
        }
      },
    },
  };
});

const flowListConfig = computed(() => ({
  ...mobileConfig.value,
  paginationMode: 'loadMore' as const,
}));

const {
  activeFilterCount,
  clearSelection: clearFlowSelection,
  filterValues,
  handleFilterSubmit,
  handleKeywordSearch,
  hasMore: flowHasMore,
  keyword,
  list: flowList,
  loadData,
  loadMore: loadMoreFlow,
  loading,
  loadingMore: flowLoadingMore,
  reload: reloadFlow,
  selectAllButtonLabel,
  selectedRows: flowSelectedRows,
  toggleRowSelection,
  toggleSelectAll,
  total: flowTotal,
  isRowSelected,
} = useMobileCrudList(flowListConfig);

const selectedFlowIds = computed({
  get: () => flowSelectedRows.value.map((row: AttachmentRow) => row.id),
  set: (ids: number[]) => {
    const idSet = new Set(ids);
    for (const row of flowList.value) {
      const shouldSelect = idSet.has(row.id);
      if (isRowSelected(row) !== shouldSelect) {
        toggleRowSelection(row, shouldSelect);
      }
    }
  },
});

const searchPlaceholder = computed(
  () => mobileConfig.value.search?.placeholder ?? $t('common.search'),
);

const showFlowSearchBar = computed(
  () =>
    mobileConfig.value.selection?.enabled === true ||
    !!mobileConfig.value.search?.keywordFields?.length ||
    !!mobileConfig.value.search?.filterSchema?.length,
);

const hasSelection = computed(() => selectedRows.value.length > 0);

const [FilterForm, filterFormApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: { class: 'w-full' },
      labelClass: 'justify-start',
    },
    handleSubmit: async () => {
      const { valid } = await filterFormApi.validate();
      if (!valid) {
        return;
      }
      const values = await filterFormApi.getValues();
      handleFilterSubmit(values ?? {});
    },
    layout: 'vertical',
    schema: computed(() => mobileConfig.value.search?.filterSchema ?? []),
    showCollapseButton: false,
    showDefaultActions: false,
    submitOnChange: computed(
      () => mobileConfig.value.search?.submitOnChange ?? false,
    ),
    wrapperClass: 'grid-cols-1',
  }),
);

function setViewMode(mode: AttachmentViewMode) {
  if (props.viewMode === mode) {
    return;
  }
  emit('update:viewMode', mode);
}

function clearSelection() {
  selectedRows.value = [];
  mobileListRef.value?.clearSelection?.();
  clearFlowSelection();
}

async function reload() {
  if (isFlowMode.value) {
    await reloadFlow();
    return;
  }
  await mobileListRef.value?.reload?.();
}

async function handleFlowFilterReset() {
  await filterFormApi.resetForm();
  const values = await filterFormApi.getValues();
  handleFilterSubmit(values ?? {});
}

async function handleFlowFilterSearch() {
  const { valid } = await filterFormApi.validate();
  if (!valid) {
    return;
  }
  const values = await filterFormApi.getValues();
  handleFilterSubmit(values ?? {});
  filterDrawerOpen.value = false;
}

watch(filterDrawerOpen, async (open) => {
  if (!open) {
    return;
  }
  await nextTick();
  await filterFormApi.setValues(filterValues.value);
});

watch(
  () => props.viewMode,
  () => {
    clearSelection();
    reload();
  },
);

watch(
  flowSelectedRows,
  (rows) => {
    if (isFlowMode.value) {
      selectedRows.value = rows;
    }
  },
  { deep: true },
);

onMounted(() => {
  if (isFlowMode.value) {
    loadData();
  }
});

defineExpose({ clearSelection, reload });
</script>

<template>
  <div
    class="attachment-mobile-body flex h-full min-h-0 flex-1 flex-col overflow-hidden px-2 pb-2 pt-0"
    :class="{
      'mobile-crud-list--maximize': isMaximized,
    }"
  >
    <MobileCrudList
      v-if="!isFlowMode"
      ref="mobileListRef"
      v-model:selected-rows="selectedRows"
      class="h-full min-h-0 flex-1"
      :config="mobileConfig"
      :toolbar-config="listToolbarConfig"
    >
      <template #toolbar>
        <div
          v-if="hasSelection"
          class="vxe-grid-batch-toolbar"
        >
          <Button v-if="canBatchDelete" danger @click="emit('batchDelete')">
            批量删除（{{ selectedRows.length }}）
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            取消选择
          </Button>
        </div>
        <Button
          v-else-if="canUpload"
          class="attachment-toolbar-actions__panel"
          type="primary"
          @click="emit('upload')"
        >
          <Plus class="size-4" />
          上传附件
        </Button>
      </template>
      <template #toolbar-tools>
        <div
          v-if="!hasSelection"
          class="attachment-view-toggle ml-2"
        >
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
    </MobileCrudList>

    <div
      v-else
      class="attachment-mobile-flow mobile-crud-list flex h-full min-h-0 flex-1 flex-col overflow-hidden"
    >
      <VxeToolbar
        class="mobile-crud-list__toolbar mb-0 shrink-0"
        :refresh="true"
        :refresh-options="flowToolbarRefreshOptions"
        size="small"
        :zoom="true"
      >
        <template #buttons>
          <div
            v-if="hasSelection"
            class="vxe-grid-batch-toolbar"
          >
            <Button v-if="canBatchDelete" danger @click="emit('batchDelete')">
              批量删除（{{ selectedRows.length }}）
            </Button>
            <Button class="ml-2" type="link" @click="clearSelection">
              取消选择
            </Button>
          </div>
          <Button
            v-else-if="canUpload"
            class="attachment-toolbar-actions__panel"
            type="primary"
            @click="emit('upload')"
          >
            <Plus class="size-4" />
            上传附件
          </Button>
        </template>
        <template #tools>
          <div
            v-if="!hasSelection"
            class="attachment-view-toggle ml-2"
          >
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
      </VxeToolbar>

      <div v-if="showFlowSearchBar" class="mobile-crud-list__search shrink-0 pb-3">
        <Space.Compact block class="mobile-crud-list__search-compact w-full">
          <Button
            class="mobile-crud-list__search-btn mobile-crud-list__search-btn--select-all"
            :class="{
              'mobile-crud-list__search-btn--last':
                !mobileConfig.search?.keywordFields?.length &&
                !mobileConfig.search?.filterSchema?.length,
            }"
            size="small"
            @click="toggleSelectAll"
          >
            {{ selectAllButtonLabel }}
          </Button>
          <Input
            v-if="mobileConfig.search?.keywordFields?.length"
            v-model:value="keyword"
            allow-clear
            class="mobile-crud-list__search-input min-w-0 flex-1"
            :placeholder="searchPlaceholder"
            @press-enter="handleKeywordSearch"
          >
            <template #prefix>
              <IconifyIcon
                class="mobile-crud-list__search-prefix-icon"
                icon="lucide:search"
              />
            </template>
          </Input>
          <Badge
            v-if="mobileConfig.search?.filterSchema?.length"
            :count="activeFilterCount"
            :offset="[-19, 2]"
          >
            <Button
              class="mobile-crud-list__search-btn mobile-crud-list__search-btn--filter mobile-crud-list__search-btn--last"
              size="small"
              @click="filterDrawerOpen = true"
            >
              <IconifyIcon class="size-3.5" icon="lucide:sliders-horizontal" />
              <span>筛选</span>
            </Button>
          </Badge>
        </Space.Compact>
      </div>

      <div
        ref="flowScrollBodyRef"
        class="mobile-crud-list__body vxe-table-like-scrollbar min-h-0 flex-1 overflow-y-auto"
      >
        <Spin :spinning="loading" wrapper-class-name="mobile-crud-list__spin">
          <AttachmentFlowView
            v-if="flowList.length > 0 || !loading"
            v-model:selected-ids="selectedFlowIds"
            class="attachment-flow-panel"
            :can-delete="canDelete"
            :has-more="flowHasMore"
            :loading-more="flowLoadingMore"
            :rows="flowList"
            :scroll-root="flowScrollBodyRef"
            :total="flowTotal"
            @copy="emit('copy', $event)"
            @delete="emit('delete', $event)"
            @load-more="loadMoreFlow"
            @open="emit('open', $event)"
          />
          <div
            v-else
            class="mobile-crud-list__loading-placeholder"
            aria-hidden="true"
          />
        </Spin>
      </div>

      <Drawer
        v-if="mobileConfig.search?.filterSchema?.length"
        v-model:open="filterDrawerOpen"
        class="mobile-crud-list__filter-drawer"
        :height="480"
        placement="bottom"
        title="筛选"
      >
        <FilterForm />
        <template #footer>
          <div class="mobile-crud-list__filter-footer">
            <Button @click="handleFlowFilterReset">重置</Button>
            <Button type="primary" @click="handleFlowFilterSearch">查询</Button>
          </div>
        </template>
      </Drawer>
    </div>
  </div>
</template>

<style scoped>
@import '#/adapter/mobile-list/mobile-crud-list.css';

.attachment-toolbar-actions__panel {
  display: inline-flex;
  align-items: center;
  flex-wrap: nowrap;
}

.attachment-view-toggle {
  display: inline-flex;
  flex-shrink: 0;
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

.attachment-view-toggle__btn.is-active {
  background: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
}

.attachment-flow-panel {
  min-height: 0;
  padding: 8px 2px 8px;
}

.mobile-crud-list__body :deep(.attachment-flow-view) {
  height: auto;
  min-height: 0;
  overflow: visible;
  border: 0;
  border-radius: 0;
  background: transparent;
}

.mobile-crud-list__body :deep(.attachment-flow-view__body--external) {
  overflow: visible;
}

.mobile-crud-list__body :deep(.attachment-flow-grid) {
  padding: 0;
}
</style>
