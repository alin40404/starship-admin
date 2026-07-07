<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AreaTreeNode, AreaTreeQuery } from '#/api/system/region';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteAreas,
  deleteArea,
  fetchAreaChildren,
  fetchAreaTree,
} from '#/api/system/region';
import { REGION_PERMISSIONS } from '#/constants/region-permissions';

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

function onDelete(row: AreaTreeNode) {
  Modal.confirm({
    content: `确定删除「${row.name}」吗？存在下级区域时将无法删除。`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '删除区域',
    async onOk() {
      await deleteArea(row.id);
      message.success('已删除');
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: `确定删除选中的 ${ids.length} 个区域吗？存在下级区域时将无法删除。`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      const result = await batchDeleteAreas(ids);
      if (result.skipped > 0) {
        message.warning(
          `已删除 ${result.deleted} 个，${result.skipped} 个因存在下级未删除`,
        );
      } else {
        message.success('已批量删除');
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
    submitButtonOptions: { content: '查询' },
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
            批量删除（{{ selectedRows.length }}）
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            取消选择
          </Button>
        </div>
        <Button
          v-else-if="areaAccess.can(REGION_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增区域
        </Button>
      </template>
    </Grid>
  </Page>
</template>
