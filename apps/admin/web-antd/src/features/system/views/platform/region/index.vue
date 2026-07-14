<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AreaTreeNode, AreaTreeQuery } from '#/features/system/api/region';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { $t } from '@vben/locales';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteAreas,
  deleteArea,
  fetchAreaChildren,
  fetchAreaTree,
} from '#/features/system/api/region';
import { REGION_PERMISSIONS } from '#/features/system/constants/region-permissions';

import { useAreaColumns, useAreaGridFormSchema } from './data';
import AreaForm from './modules/form.vue';

type AreaFormExpose = {
  open: (data?: AreaTreeNode | { pid?: number }) => void;
};

const { hasAccessByCodes } = useAccess();

const areaAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const isSearchMode = ref(false);
const selectedRows = ref<AreaTreeNode[]>([]);
const areaFormRef = ref<AreaFormExpose>();

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as AreaTreeNode[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function hasSearchFilters(formValues?: AreaTreeQuery) {
  return !!(
    formValues?.name ||
    formValues?.pinyin ||
    (formValues?.level !== undefined && formValues?.level !== null)
  );
}

function setTreeLazyMode(searchMode: boolean) {
  if (isSearchMode.value === searchMode) {
    return;
  }
  isSearchMode.value = searchMode;
  gridApi.setGridOptions({
    treeConfig: {
      childrenField: 'children',
      expandAll: searchMode,
      hasChild: 'has_child',
      lazy: !searchMode,
      loadMethod: searchMode ? undefined : loadAreaChildren,
      rowField: 'id',
      transform: false,
    },
  });
}

async function loadAreaChildren({ row }: { row: AreaTreeNode }) {
  return fetchAreaChildren(row.id);
}

function onCreate() {
  areaFormRef.value?.open({});
}

function onAppend(row: AreaTreeNode) {
  areaFormRef.value?.open({ pid: row.id });
}

function onEdit(row: AreaTreeNode) {
  areaFormRef.value?.open(row);
}

function onRefresh() {
  gridApi.grid?.clearTreeExpandLoaded?.();
  gridApi.grid?.clearTreeExpand?.();
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<AreaTreeNode>) {
  if (code === 'append') onAppend(row);
  else if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onDelete(row: AreaTreeNode) {
  await deleteArea(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: $t('platform.region.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      const result = await batchDeleteAreas(ids);
      if (result.skipped > 0) {
        message.warning(
          $t('platform.region.batchDeletePartial', [
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

const [Grid, gridApi] = useVbenVxeGrid<AreaTreeNode>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useAreaGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: useAreaColumns(onActionClick, areaAccess),
    height: 'auto',
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async (_params, formValues) => {
          const searchMode = hasSearchFilters(formValues);
          setTreeLazyMode(searchMode);

          if (searchMode) {
            return fetchAreaTree({
              level:
                formValues?.level === undefined || formValues?.level === null
                  ? undefined
                  : Number(formValues.level),
              name: formValues?.name || undefined,
              pinyin: formValues?.pinyin || undefined,
            });
          }

          return fetchAreaChildren(0);
        },
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
    treeConfig: {
      childrenField: 'children',
      hasChild: 'has_child',
      lazy: true,
      loadMethod: loadAreaChildren,
      rowField: 'id',
      transform: false,
    },
  } as VxeTableGridOptions<AreaTreeNode>,
});
</script>

<template>
  <Page auto-content-height>
    <AreaForm ref="areaFormRef" @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
          <Button
            v-if="areaAccess.can(REGION_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button
          v-else-if="areaAccess.can(REGION_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          {{ $t('platform.region.createRegion') }}
        </Button>
      </template>
    </Grid>
  </Page>
</template>
