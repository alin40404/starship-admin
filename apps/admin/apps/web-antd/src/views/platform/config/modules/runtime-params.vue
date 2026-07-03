<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ConfigRow } from '#/api/system/config';

import { ref, useTemplateRef, watch } from 'vue';

import { useAccess } from '@vben/access';
import { useVbenModal } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { useElementSize } from '@vueuse/core';
import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteConfigs,
  deleteConfig,
  fetchConfigs,
} from '#/api/system/config';
import { CONFIG_PERMISSIONS } from '#/constants/config-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';

import { PARAM_TYPE_TAG_OPTIONS } from '../data';
import ParamForm from './param-form.vue';

const props = defineProps<{
  canManage: boolean;
}>();

const { hasAccessByCodes } = useAccess();

const paramAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const gridWrapRef = useTemplateRef<HTMLElement>('gridWrapRef');
const { height: gridWrapHeight } = useElementSize(gridWrapRef);
const selectedRows = ref<ConfigRow[]>([]);

const [ParamFormModal, paramFormModalApi] = useVbenModal({
  connectedComponent: ParamForm,
  destroyOnClose: true,
});

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as ConfigRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onCreate() {
  paramFormModalApi.setData({}).open();
}

function onEdit(row: ConfigRow) {
  paramFormModalApi.setData(row).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<ConfigRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

function onDelete(row: ConfigRow) {
  Modal.confirm({
    content: `确定删除「${row.name}」吗？`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '删除参数',
    async onOk() {
      await deleteConfig(row.id);
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
    content: `确定删除选中的 ${ids.length} 个运行参数吗？`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      const result = await batchDeleteConfigs(ids);
      message.success(`已删除 ${result.deleted} 个参数`);
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<ConfigRow>({
  class: 'h-full min-h-0',
  gridClass: 'p-0',
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: [
      {
        align: 'center',
        fixed: 'left',
        type: 'checkbox',
        width: 48,
      },
      { align: 'left', field: 'name', minWidth: 140, title: '名称' },
      { align: 'left', field: 'key', minWidth: 160, title: '参数键' },
      {
        align: 'left',
        field: 'value',
        formatter: ({ cellValue }: { cellValue: ConfigRow['value'] }) =>
          cellValue ?? TABLE_CELL_EMPTY,
        minWidth: 180,
        title: '参数值',
      },
      {
        align: 'center',
        cellRender: { name: 'CellTag', options: [...PARAM_TYPE_TAG_OPTIONS] },
        field: 'type',
        title: '类型',
        width: 96,
      },
      { field: 'sort', title: '排序', width: 80 },
      {
        align: 'left',
        field: 'remark',
        formatter: ({ cellValue }: { cellValue: ConfigRow['remark'] }) =>
          cellValue ?? TABLE_CELL_EMPTY,
        minWidth: 160,
        title: '备注',
      },
      {
        align: 'center',
        cellRender: {
          attrs: {
            nameField: 'name',
            nameTitle: '参数',
            onClick: onActionClick,
          },
          name: 'CellOperation',
          options: [
            {
              code: 'edit',
              show: () => props.canManage,
              text: '编辑',
              type: 'link',
            },
            {
              code: 'delete',
              danger: true,
              show: () => paramAccess.can(CONFIG_PERMISSIONS.DELETE),
              text: '删除',
              type: 'link',
            },
          ],
        },
        fixed: 'right',
        title: '操作',
        width: 140,
      },
    ],
    height: 360,
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async () => {
          const result = await fetchConfigs('param');
          const list = result.list ?? [];
          return { data: list, total: list.length };
        },
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { refresh: true },
  } as VxeTableGridOptions<ConfigRow>,
});

watch(
  gridWrapHeight,
  (height) => {
    gridApi.setGridOptions({
      height: Math.max(Math.floor(height), 280),
    });
  },
  { immediate: true },
);
</script>

<template>
  <div class="flex h-full min-h-0 flex-col">
    <ParamFormModal @success="onRefresh" />
    <div ref="gridWrapRef" class="min-h-0 flex-1">
      <Grid>
        <template #toolbar-actions>
          <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
            <Button
              v-if="paramAccess.can(CONFIG_PERMISSIONS.BATCH_DELETE)"
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
            v-else-if="props.canManage"
            type="primary"
            @click="onCreate"
          >
            <Plus class="size-4" />
            新增参数
          </Button>
        </template>
      </Grid>
    </div>
  </div>
</template>
