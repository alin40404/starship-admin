<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { CategoryTreeNode, CategoryTypeOption } from '#/api/system/category';

import { computed, onMounted, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { $t } from '@vben/locales';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteCategories,
  batchUpdateCategoryStatus,
  deleteCategory,
  fetchCategoryTree,
  fetchCategoryOptionsTypes,
  updateCategorySort,
  updateCategoryStatus,
} from '#/api/system/category';
import { CATEGORY_PERMISSIONS } from '#/constants/category-permissions';

import {
  DICT_TYPE_CODE,
  mapDictItemOptions,
} from '#/views/platform/dict/i18n';

import { useColumns, useGridFormSchema } from './data';
import CategoryForm from './modules/form.vue';

type CategoryFormExpose = {
  open: (
    data?: CategoryTreeNode | { parent_id?: number; type?: string },
  ) => void;
};

const ALL_TYPE_KEY = '__all__';

const { hasAccessByCodes } = useAccess();

const categoryAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const activeType = ref(ALL_TYPE_KEY);
const typeOptions = ref<CategoryTypeOption[]>([]);
const selectedRows = ref<CategoryTreeNode[]>([]);

const typeTagOptions = computed(() =>
  mapDictItemOptions(DICT_TYPE_CODE.categoryType, typeOptions.value),
);

const categoryFormRef = ref<CategoryFormExpose>();

function resolveTypeFilter() {
  if (activeType.value === ALL_TYPE_KEY) {
    return undefined;
  }
  return activeType.value;
}

function syncGridColumns() {
  gridApi.setGridOptions({
    columns: useColumns(
      onActionClick,
      onCategoryStatusToggle,
      categoryAccess,
      {
        showTypeColumn: activeType.value === ALL_TYPE_KEY,
        typeTagOptions: typeTagOptions.value,
      },
    ),
  } as VxeTableGridOptions<CategoryTreeNode>);
}

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as CategoryTreeNode[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onRefresh() {
  gridApi.query();
}

function onTypeChange(value: string) {
  if (activeType.value === value) {
    return;
  }
  activeType.value = value;
  clearSelection();
  syncGridColumns();
  onRefresh();
}

function onCreate() {
  categoryFormRef.value?.open({
    parent_id: 0,
    type:
      activeType.value === ALL_TYPE_KEY
        ? typeOptions.value[0]?.value
        : activeType.value,
  });
}

function onAppend(row: CategoryTreeNode) {
  categoryFormRef.value?.open({
    parent_id: row.id,
    type: row.type,
  });
}

function onEdit(row: CategoryTreeNode) {
  categoryFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<CategoryTreeNode>) {
  if (code === 'append') onAppend(row);
  else if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onCategoryStatusToggle(
  nextStatus: CategoryTreeNode['status'],
  row: CategoryTreeNode,
) {
  return new Promise<boolean>((resolve) => {
    const label =
      nextStatus === 'normal'
        ? $t('system.shared.status.normal')
        : $t('system.shared.status.hidden');
    Modal.confirm({
      content: $t('platform.category.toggleStatusConfirm', [
        row.name,
        label,
      ]),
      okText: $t('common.confirm'),
      cancelText: $t('common.cancel'),
      title: $t('system.shared.toggleStatusTitle'),
      async onOk() {
        try {
          await updateCategoryStatus(row.id, nextStatus);
          message.success($t('crud.statusUpdated'));
          onRefresh();
          resolve(true);
        } catch {
          resolve(false);
        }
      },
      onCancel() {
        resolve(false);
      },
    });
  });
}

async function onDelete(row: CategoryTreeNode) {
  await deleteCategory(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: $t('platform.category.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      const result = await batchDeleteCategories(ids);
      if (result.skipped > 0) {
        message.warning(
          $t('platform.category.batchDeletePartial', [
            result.deleted,
            result.skipped,
          ]),
        );
      } else {
        message.success($t('crud.batchDeleted'));
      }
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: CategoryTreeNode['status']) {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  const label =
    status === 'normal'
      ? $t('system.shared.status.normal')
      : $t('system.shared.status.hidden');
  Modal.confirm({
    content: $t('platform.category.batchStatusConfirmCategory', [
      ids.length,
      label,
    ]),
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchSet', [label]),
    async onOk() {
      await batchUpdateCategoryStatus(ids, status);
      message.success($t('system.shared.batchSetDone', [label]));
      clearSelection();
      onRefresh();
    },
  });
}

async function loadTypeOptions() {
  typeOptions.value = await fetchCategoryOptionsTypes();
  syncGridColumns();
}

onMounted(() => {
  loadTypeOptions();
});

const [Grid, gridApi] = useVbenVxeGrid<CategoryTreeNode>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
    async editClosed({
      column,
      row,
    }: {
      column: { field: string };
      row: CategoryTreeNode;
    }) {
      if (column.field !== 'sort') {
        return;
      }
      const grid = gridApi.grid;
      if (!grid?.isUpdateByRow?.(row)) {
        return;
      }

      const sort = Number(row.sort);
      if (!Number.isFinite(sort) || sort < 0 || !Number.isInteger(sort)) {
        message.warning($t('platform.category.sortInvalid'));
        await gridApi.reload();
        return;
      }

      gridApi.setLoading(true);
      try {
        await updateCategorySort(row.id, sort);
        message.success($t('platform.category.sortUpdated'));
        onRefresh();
      } catch {
        await gridApi.reload();
      } finally {
        gridApi.setLoading(false);
      }
    },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: useColumns(
      onActionClick,
      onCategoryStatusToggle,
      categoryAccess,
      { typeTagOptions: [] },
    ),
    editConfig: {
      beforeEditMethod({ column }) {
        return (
          column.field === 'sort' &&
          categoryAccess.can(CATEGORY_PERMISSIONS.SORT)
        );
      },
      mode: 'cell',
      trigger: 'dblclick',
    },
    height: 'auto',
    keepSource: true,
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async (_ctx, formValues) =>
          fetchCategoryTree({
            search: formValues?.search || undefined,
            type: resolveTypeFilter(),
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
    treeConfig: {
      childrenField: 'children',
      expandAll: true,
      rowField: 'id',
      transform: false,
    },
  } as VxeTableGridOptions<CategoryTreeNode>,
});
</script>

<template>
  <Page auto-content-height>
    <CategoryForm ref="categoryFormRef" @success="onRefresh" />
    <div
      class="bg-card flex h-full min-h-0 flex-col overflow-hidden rounded-lg border border-border md:min-h-[520px] md:flex-row"
    >
      <aside
        class="category-type-panel flex shrink-0 flex-col overflow-hidden border-b border-border md:w-[168px] md:border-b-0 md:border-r"
      >
        <div class="category-type-header">
          {{ $t('platform.category.typeHeader') }}
        </div>
        <div class="category-type-tabs">
          <button
            class="category-type-item"
            :class="{ 'is-active': activeType === ALL_TYPE_KEY }"
            type="button"
            @click="onTypeChange(ALL_TYPE_KEY)"
          >
            {{ $t('system.shared.all') }}
          </button>
          <button
            v-for="item in typeTagOptions"
            :key="item.value"
            class="category-type-item"
            :class="{ 'is-active': activeType === item.value }"
            type="button"
            @click="onTypeChange(item.value)"
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
                <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
                  <Button
                    v-if="categoryAccess.can(CATEGORY_PERMISSIONS.BATCH_DELETE)"
                    danger
                    @click="onBatchDelete"
                  >
                    {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
                  </Button>
                  <Button
                    v-if="categoryAccess.can(CATEGORY_PERMISSIONS.BATCH_STATUS)"
                    class="ml-2"
                    @click="onBatchUpdateStatus('normal')"
                  >
                    {{ $t('system.shared.batchNormal') }}
                  </Button>
                  <Button
                    v-if="categoryAccess.can(CATEGORY_PERMISSIONS.BATCH_STATUS)"
                    class="ml-2"
                    @click="onBatchUpdateStatus('hidden')"
                  >
                    {{ $t('system.shared.batchHidden') }}
                  </Button>
                  <Button class="ml-2" type="link" @click="clearSelection">
                    {{ $t('system.shared.clearSelection') }}
                  </Button>
                </div>
                <Button
                  v-else-if="categoryAccess.can(CATEGORY_PERMISSIONS.CREATE)"
                  type="primary"
                  @click="onCreate"
                >
                  <Plus class="size-4" />
                  {{ $t('platform.category.createCategory') }}
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
.category-type-header {
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

.category-type-tabs {
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

.category-type-tabs::-webkit-scrollbar {
  height: 4px;
}

.category-type-tabs::-webkit-scrollbar-thumb {
  border-radius: 9999px;
  background-color: hsl(var(--border));
}

.category-type-item {
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

.category-type-item:hover {
  border-color: hsl(var(--primary) / 0.35);
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

.category-type-item.is-active {
  border-color: hsl(var(--primary) / 0.25);
  background-color: hsl(var(--primary) / 0.08);
  color: hsl(var(--primary));
  font-weight: 500;
}

@media (min-width: 768px) {
  .category-type-header {
    justify-content: flex-start;
  }

  .category-type-tabs {
    flex-direction: column;
    gap: 0;
    overflow-x: visible;
    overflow-y: auto;
    padding: 0;
  }

  .category-type-item {
    width: 100%;
    border: 0;
    border-left: 2px solid transparent;
    border-radius: 0;
    padding: 10px 12px;
    text-align: left;
    color: hsl(var(--foreground));
  }

  .category-type-item:hover {
    border-color: transparent;
    border-left-color: hsl(var(--border));
    background-color: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
  }

  .category-type-item.is-active {
    border-color: transparent;
    border-left-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.08);
    color: hsl(var(--primary));
  }
}
</style>

<style lang="scss">
.vxe-grid .category-sort-editable-col {
  cursor: pointer;
}
</style>
