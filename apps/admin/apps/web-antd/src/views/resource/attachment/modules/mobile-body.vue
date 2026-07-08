<script lang="ts" setup>
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { AttachmentRow } from '#/api/system/attachment';

import { computed, onMounted, provide, reactive, ref, watch } from 'vue';

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
  Pagination,
  Space,
  Spin,
  Tooltip,
} from 'ant-design-vue';

import { buildAttachmentMobileCrudConfig } from '../data';
import AttachmentFlowView from './flow-view.vue';

type AttachmentViewMode = 'flow' | 'list';

const props = defineProps<{
  activeCategory: string;
  canBatchDelete: boolean;
  canDelete: boolean;
  canUpload: boolean;
  columns: CrudGridColumn<AttachmentRow>[];
  resolveCategoryFilter: () => string | undefined;
  viewMode: AttachmentViewMode;
}>();

const emit = defineEmits<{
  batchDelete: [];
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
  const base = buildAttachmentMobileCrudConfig({
    columns: props.columns,
    query: async ({ filters, keyword, page, pageSize, sortField, sortOrder }) => {
      const result = await fetchAttachmentPage({
        page,
        per_page: pageSize,
        keyword: keyword || undefined,
        file_name: filters?.file_name || undefined,
        file_ext: filters?.file_ext || undefined,
        storage: filters?.storage || undefined,
        category: props.resolveCategoryFilter(),
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
  };
});

const {
  activeFilterCount,
  clearSelection: clearFlowSelection,
  currentPage,
  handleFilterSubmit,
  handleKeywordSearch,
  handlePageChange,
  keyword,
  list: flowList,
  loadData,
  loading,
  pageSize,
  reload: reloadFlow,
  selectedRows: flowSelectedRows,
  toggleSelectAll,
  total,
} = useMobileCrudList(mobileConfig);

const selectedFlowIds = computed({
  get: () => selectedRows.value.map((row: AttachmentRow) => row.id),
  set: (ids: number[]) => {
    selectedRows.value = flowList.value.filter((row: AttachmentRow) =>
      ids.includes(row.id),
    );
  },
});

const searchPlaceholder = computed(
  () => mobileConfig.value.search?.placeholder ?? $t('common.search'),
);

const showFlowSearchBar = computed(
  () =>
    !!mobileConfig.value.search?.keywordFields?.length ||
    !!mobileConfig.value.search?.filterSchema?.length,
);

const [FilterForm, filterFormApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: { class: 'w-full' },
      labelClass: 'justify-start',
    },
    layout: 'vertical',
    schema: computed(() => mobileConfig.value.search?.filterSchema ?? []),
    showCollapseButton: false,
    showDefaultActions: false,
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

watch(
  () => props.activeCategory,
  () => {
    clearSelection();
    reload();
  },
);

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
    class="attachment-mobile-body flex min-h-0 flex-1 flex-col overflow-hidden"
    :class="{ 'mobile-crud-list--maximize': isMaximized }"
  >
    <MobileCrudList
      v-if="!isFlowMode"
      ref="mobileListRef"
      v-model:selected-rows="selectedRows"
      class="min-h-0 flex-1"
      :config="mobileConfig"
      :toolbar-config="listToolbarConfig"
    >
      <template #toolbar>
        <div
          v-if="selectedRows.length > 0"
          class="attachment-toolbar-actions__panel vxe-grid-batch-toolbar"
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
          class="attachment-view-toggle ml-2"
          :class="{ 'attachment-view-toggle--hidden': selectedRows.length > 0 }"
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
      class="attachment-mobile-flow flex min-h-0 flex-1 flex-col overflow-hidden"
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
            v-if="selectedRows.length > 0"
            class="attachment-toolbar-actions__panel vxe-grid-batch-toolbar"
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
            class="attachment-view-toggle ml-2"
            :class="{ 'attachment-view-toggle--hidden': selectedRows.length > 0 }"
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
            全选
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
            :offset="[-4, 2]"
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

      <div class="mobile-crud-list__body overflow-y-auto">
        <Spin :spinning="loading" wrapper-class-name="mobile-crud-list__spin">
          <AttachmentFlowView
            v-if="flowList.length > 0 || !loading"
            v-model:selected-ids="selectedFlowIds"
            class="attachment-flow-panel"
            :can-delete="canDelete"
            :rows="flowList"
            @copy="emit('copy', $event)"
            @delete="emit('delete', $event)"
            @open="emit('open', $event)"
          />
          <div
            v-else
            class="mobile-crud-list__loading-placeholder"
            aria-hidden="true"
          />
        </Spin>
      </div>

      <div class="mobile-crud-list__pager shrink-0">
        <span class="mobile-crud-list__pager-total">共 {{ total }} 条</span>
        <Pagination
          :current="currentPage"
          :page-size="pageSize"
          :show-size-changer="false"
          :total="total"
          size="small"
          @change="handlePageChange"
        />
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

.attachment-mobile-body {
  padding: 8px;
}

.attachment-mobile-body :deep(.mobile-crud-list__toolbar .ant-btn) {
  display: inline-flex;
  align-items: center;
  font-size: var(--vben-button-font-size, var(--text-sm, 0.875rem)) !important;
}

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

.attachment-view-toggle--hidden {
  visibility: hidden;
  pointer-events: none;
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
  min-height: 240px;
  padding: 0;
}

.mobile-crud-list__body :deep(.attachment-flow-view) {
  height: auto;
  min-height: 240px;
  overflow: visible;
}
</style>
