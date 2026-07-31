<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { InboxCategoryStats, InboxMessageRow } from '#/features/system/api/inbox';

import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

import { Page } from '@vben/common-ui';

import { storeToRefs } from 'pinia';
import { Icon as IconifyIcon } from '@iconify/vue';
import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteInboxMessages,
  batchMarkInboxMessagesRead,
  deleteInboxMessage,
  fetchInboxMessagePage,
  markAllInboxMessagesRead,
  markInboxMessageRead,
} from '#/features/system/api/inbox';
import {
  INBOX_ALL_CATEGORY_KEY,
  INBOX_ALL_CATEGORY_OPTION,
  getVisibleInboxCategoryOptions,
  normalizeInboxCategoryFilter,
} from '#/features/system/constants/inbox-permissions';
import { $t } from '#/locales';
import { useInboxStore } from '#/features/system/store/inbox';

import { useColumns, useGridFormSchema, buildInboxMessageMobileCrudConfig } from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const inboxStore = useInboxStore();
const { activeCategory, categoryStats, visibleCategories } =
  storeToRefs(inboxStore);

const route = useRoute();
const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();

const visibleCategoryOptions = computed(() =>
  getVisibleInboxCategoryOptions(visibleCategories.value),
);

function formatBadgeCount(count: number) {
  return count > 99 ? '99+' : String(count);
}

function getCategoryCount(key: keyof InboxCategoryStats) {
  return categoryStats.value[key] ?? 0;
}

function resolveCategoryFilter() {
  return activeCategory.value === INBOX_ALL_CATEGORY_KEY
    ? undefined
    : activeCategory.value;
}

const DETAIL_MARK_READ_DEFER_MS = 320;

async function onActionClick({ code, row }: OnActionClickParams<InboxMessageRow>) {
  if (code === 'detail') {
    void detailDrawerRef.value?.open(row.id, row);
    if (row.is_read === 0) {
      void markInboxMessageRead(row.id).then(async () => {
        message.success($t('inbox.markReadDone'));
        await inboxStore.refresh();
        window.setTimeout(() => reload(), DETAIL_MARK_READ_DEFER_MS);
      });
    }
    return;
  }
  if (code === 'delete') {
    onDelete(row);
  }
}

const columns = useColumns(onActionClick, { showCategoryColumn: true });
const mobileColumns = useColumns(onActionClick, { showCategoryColumn: false });

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
            category: resolveCategoryFilter(),
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
  mobile: buildInboxMessageMobileCrudConfig({
    columns: mobileColumns,
    query: async ({ filters, keyword, page, pageSize }) => {
      const result = await fetchInboxMessagePage({
        page,
        per_page: pageSize,
        keyword: keyword || undefined,
        category: resolveCategoryFilter(),
        type: filters?.type || undefined,
        is_read:
          filters?.is_read === 0 || filters?.is_read === 1
            ? filters.is_read
            : undefined,
      });
      return {
        data: result.data,
        total: result.total,
      };
    },
  }),
});

const { clearSelection, gridApi, reload, selectedRows } = crudApi;

function syncGridColumns() {
  const nextColumns = useColumns(onActionClick, {
    showCategoryColumn: activeCategory.value === INBOX_ALL_CATEGORY_KEY,
  });
  gridApi.setGridOptions({
    columns: nextColumns as VxeTableGridOptions<InboxMessageRow>['columns'],
  });
}

function onCategoryChange(value: string) {
  const nextCategory = normalizeInboxCategoryFilter(
    value,
    visibleCategories.value,
  );
  if (activeCategory.value === nextCategory) {
    return;
  }
  void inboxStore.setActiveCategory(nextCategory).then(() => {
    clearSelection();
    syncGridColumns();
    reload();
  });
}

async function onRefresh() {
  await Promise.all([reload(), inboxStore.refresh()]);
}

onMounted(() => {
  const queryCategory =
    typeof route.query.category === 'string' ? route.query.category : undefined;
  if (queryCategory) {
    void inboxStore.setActiveCategory(queryCategory).then(() => {
      syncGridColumns();
      reload();
    });
    return;
  }

  void inboxStore.refresh().then(() => {
    syncGridColumns();
    reload();
  });
});

async function onDelete(row: InboxMessageRow) {
  await deleteInboxMessage(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

async function onMarkAllRead() {
  await markAllInboxMessagesRead();
  message.success($t('inbox.markAllReadDone'));
  onRefresh();
}

async function onMarkSelectedRead() {
  const unreadIds = selectedRows.value
    .filter((row) => row.is_read === 0)
    .map((row) => row.id);
  if (unreadIds.length === 0) {
    message.info($t('inbox.markSelectedReadEmpty'));
    return;
  }
  await batchMarkInboxMessagesRead(unreadIds);
  message.success($t('inbox.markSelectedReadDone'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: $t('inbox.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeleteInboxMessages(ids);
      message.success($t('crud.batchDeleted', [ids.length]));
      clearSelection();
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <DetailDrawer ref="detailDrawerRef" />
    <div
      class="bg-card flex h-full min-h-0 flex-col overflow-hidden rounded-lg border border-border md:min-h-[520px] md:flex-row"
    >
      <aside
        class="inbox-category-panel flex shrink-0 flex-col overflow-hidden border-b border-border md:w-[200px] md:border-b-0 md:border-r"
      >
        <div class="inbox-category-header">
          {{ $t('inbox.columns.category') }}
        </div>
        <div class="inbox-category-tabs">
          <button
            class="inbox-category-item"
            :class="{ 'is-active': activeCategory === INBOX_ALL_CATEGORY_KEY }"
            type="button"
            @click="onCategoryChange(INBOX_ALL_CATEGORY_KEY)"
          >
            <IconifyIcon
              class="inbox-category-item__icon"
              :icon="INBOX_ALL_CATEGORY_OPTION.icon"
            />
            <span class="inbox-category-item__label">
              {{ $t(INBOX_ALL_CATEGORY_OPTION.labelKey) }}
            </span>
            <span
              v-if="getCategoryCount('all') > 0"
              class="inbox-category-item__badge"
            >
              {{ formatBadgeCount(getCategoryCount('all')) }}
            </span>
          </button>
          <button
            v-for="item in visibleCategoryOptions"
            :key="item.value"
            class="inbox-category-item"
            :class="{ 'is-active': activeCategory === item.value }"
            type="button"
            @click="onCategoryChange(item.value)"
          >
            <IconifyIcon class="inbox-category-item__icon" :icon="item.icon" />
            <span class="inbox-category-item__label">{{ $t(item.labelKey) }}</span>
            <span
              v-if="getCategoryCount(item.value) > 0"
              class="inbox-category-item__badge"
            >
              {{ formatBadgeCount(getCategoryCount(item.value)) }}
            </span>
          </button>
        </div>
      </aside>

      <main class="flex min-h-0 flex-1 flex-col overflow-hidden">
        <div class="min-h-0 flex-1">
          <CrudView>
            <template #toolbar-actions>
              <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
                <Button @click="onMarkSelectedRead">
                  {{ $t('inbox.markSelectedRead') }}
                </Button>
                <Button class="ml-2" danger @click="onBatchDelete">
                  {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
                </Button>
                <Button class="ml-2" type="link" @click="clearSelection">
                  {{ $t('system.shared.clearSelection') }}
                </Button>
              </div>
              <Button v-else @click="onMarkAllRead">
                {{ $t('inbox.markAllRead') }}
              </Button>
            </template>
          </CrudView>
        </div>
      </main>
    </div>
  </Page>
</template>

<style scoped>
.inbox-category-header {
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

.inbox-category-tabs {
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

.inbox-category-tabs::-webkit-scrollbar {
  height: 4px;
}

.inbox-category-tabs::-webkit-scrollbar-thumb {
  border-radius: 9999px;
  background-color: hsl(var(--border));
}

.inbox-category-item {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  gap: 8px;
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

.inbox-category-item__icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.inbox-category-item__label {
  flex: 1;
  min-width: 0;
  text-align: left;
}

.inbox-category-item__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 27.5px;
  height: 18px;
  flex-shrink: 0;
  padding: 0 4px;
  border-radius: 9999px;
  background-color: hsl(var(--destructive));
  font-size: 11px;
  font-weight: 500;
  font-variant-numeric: tabular-nums;
  line-height: 18px;
  text-align: center;
  color: #fff;
  box-sizing: border-box;
}

.inbox-category-item:hover {
  border-color: hsl(var(--primary) / 0.35);
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

.inbox-category-item.is-active {
  border-color: hsl(var(--primary) / 0.25);
  background-color: hsl(var(--primary) / 0.08);
  color: hsl(var(--primary));
  font-weight: 500;
}

@media (min-width: 768px) {
  .inbox-category-header {
    justify-content: flex-start;
  }

  .inbox-category-tabs {
    flex-direction: column;
    gap: 0;
    overflow-x: visible;
    overflow-y: auto;
    padding: 0;
  }

  .inbox-category-item {
    width: 100%;
    border: 0;
    border-left: 2px solid transparent;
    border-radius: 0;
    padding: 10px 12px;
    text-align: left;
    color: hsl(var(--foreground));
  }

  .inbox-category-item:hover {
    border-color: transparent;
    border-left-color: hsl(var(--border));
    background-color: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
  }

  .inbox-category-item.is-active {
    border-color: transparent;
    border-left-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.08);
    color: hsl(var(--primary));
  }
}
</style>
