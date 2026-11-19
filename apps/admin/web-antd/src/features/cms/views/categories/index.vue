<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { CmsCategoryTreeNode } from '#/features/cms/api';

import { computed, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';
import { $t } from '@vben/locales';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteCmsCategories,
  batchUpdateCmsCategoryStatus,
  deleteCmsCategory,
  fetchCmsCategoryTree,
  updateCmsCategorySort,
  updateCmsCategoryStatus,
} from '#/features/cms/api';
import { CMS_PERMISSIONS } from '#/features/cms/constants/cms-permissions';
import { filterRowsForStatusChange, formatBatchActionLabel } from '#/utils/batch-status';

import { useColumns, useGridFormSchema } from './data';
import CategoryForm from './modules/form.vue';

type CategoryFormExpose = {
  open: (data?: CmsCategoryTreeNode | { parent_id?: number }) => void;
};

const { hasAccessByCodes } = useAccess();
const categoryAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const selectedRows = ref<CmsCategoryTreeNode[]>([]);
const categoryFormRef = ref<CategoryFormExpose>();

const normalTargetRows = computed(() =>
  filterRowsForStatusChange(selectedRows.value, 'normal'),
);
const hiddenTargetRows = computed(() =>
  filterRowsForStatusChange(selectedRows.value, 'hidden'),
);

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as CmsCategoryTreeNode[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onRefresh() {
  gridApi.query();
}

function onCreate() {
  categoryFormRef.value?.open({ parent_id: 0 });
}

function onAppend(row: CmsCategoryTreeNode) {
  categoryFormRef.value?.open({ parent_id: row.id });
}

function onEdit(row: CmsCategoryTreeNode) {
  categoryFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<CmsCategoryTreeNode>) {
  if (code === 'append') onAppend(row);
  else if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onCategoryStatusToggle(
  nextStatus: CmsCategoryTreeNode['status'],
  row: CmsCategoryTreeNode,
) {
  return new Promise<boolean>((resolve) => {
    const label =
      nextStatus === 'normal'
        ? $t('system.shared.status.normal')
        : $t('system.shared.status.hidden');
    Modal.confirm({
      content: $t('platform.category.toggleStatusConfirm', [row.name, label]),
      okText: $t('common.confirm'),
      cancelText: $t('common.cancel'),
      title: $t('system.shared.toggleStatusTitle'),
      async onOk() {
        try {
          await updateCmsCategoryStatus(row.id, nextStatus);
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

async function onDelete(row: CmsCategoryTreeNode) {
  await deleteCmsCategory(row.id);
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
      const result = await batchDeleteCmsCategories(ids);
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

function onBatchUpdateStatus(status: CmsCategoryTreeNode['status']) {
  const targets = filterRowsForStatusChange(selectedRows.value, status);
  const ids = targets.map((row) => row.id);
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
      await batchUpdateCmsCategoryStatus(ids, status);
      message.success($t('system.shared.batchSetDone', [label]));
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<CmsCategoryTreeNode>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
  },
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
    async editClosed({
      column,
      row,
    }: {
      column: { field: string };
      row: CmsCategoryTreeNode;
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
        await updateCmsCategorySort(row.id, sort);
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
    columns: useColumns(onActionClick, onCategoryStatusToggle, categoryAccess),
    editConfig: {
      beforeEditMethod({ column }) {
        return (
          column.field === 'sort' &&
          categoryAccess.can(CMS_PERMISSIONS.CATEGORIES_SORT)
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
          fetchCmsCategoryTree({
            search: formValues?.search || undefined,
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
  } as VxeTableGridOptions<CmsCategoryTreeNode>,
});
</script>

<template>
  <Page auto-content-height>
    <CategoryForm ref="categoryFormRef" @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <div class="flex flex-wrap items-center gap-3">
          <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
            <Button
              v-if="categoryAccess.can(CMS_PERMISSIONS.CATEGORIES_BATCH_DELETE)"
              danger
              @click="onBatchDelete"
            >
              {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
            </Button>
            <Button
              v-if="categoryAccess.can(CMS_PERMISSIONS.CATEGORIES_BATCH_STATUS)"
              class="batch-btn-enable ml-2"
              :disabled="normalTargetRows.length === 0"
              @click="onBatchUpdateStatus('normal')"
            >
              {{ formatBatchActionLabel('system.shared.batchNormal', 'system.shared.batchNormalWithCount', normalTargetRows.length) }}
            </Button>
            <Button
              v-if="categoryAccess.can(CMS_PERMISSIONS.CATEGORIES_BATCH_STATUS)"
              class="batch-btn-disable ml-2"
              :disabled="hiddenTargetRows.length === 0"
              @click="onBatchUpdateStatus('hidden')"
            >
              {{ formatBatchActionLabel('system.shared.batchHidden', 'system.shared.batchHiddenWithCount', hiddenTargetRows.length) }}
            </Button>
            <Button class="ml-2" type="link" @click="clearSelection">
              {{ $t('system.shared.clearSelection') }}
            </Button>
          </div>
          <Button
            v-else-if="categoryAccess.can(CMS_PERMISSIONS.CATEGORIES_CREATE)"
            type="primary"
            @click="onCreate"
          >
            <Plus class="size-4" />
            {{ $t('platform.category.createCategory') }}
          </Button>
        </div>
      </template>
    </Grid>
  </Page>
</template>
