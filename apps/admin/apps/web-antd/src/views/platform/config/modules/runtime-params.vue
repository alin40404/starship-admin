<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ConfigRow } from '#/api/system/config';

import { ref, useTemplateRef, watch } from 'vue';

import { useAccess } from '@vben/access';
import { useVbenModal } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { useElementSize } from '@vueuse/core';
import { $t } from '@vben/locales';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteConfigs,
  deleteConfig,
  fetchConfigs,
} from '#/api/system/config';
import { CONFIG_PERMISSIONS } from '#/constants/config-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';

import { getParamTypeTagOptions } from '../data';
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

async function onDelete(row: ConfigRow) {
  await deleteConfig(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: $t('platform.config.param.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      const result = await batchDeleteConfigs(ids);
      message.success(
        $t('platform.config.param.batchDeleted', [result.deleted]),
      );
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
      { align: 'left', field: 'name', minWidth: 140, title: $t('platform.config.param.name') },
      { align: 'left', field: 'key', minWidth: 160, title: $t('platform.config.param.key') },
      {
        align: 'left',
        field: 'value',
        formatter: ({ cellValue }: { cellValue: ConfigRow['value'] }) =>
          cellValue ?? TABLE_CELL_EMPTY,
        minWidth: 180,
        title: $t('platform.config.param.value'),
      },
      {
        align: 'center',
        cellRender: { name: 'CellTag', options: [...getParamTypeTagOptions()] },
        field: 'type',
        title: $t('platform.config.param.valueType'),
        width: 96,
      },
      { field: 'sort', title: $t('platform.config.param.sort'), width: 80 },
      {
        align: 'left',
        field: 'remark',
        formatter: ({ cellValue }: { cellValue: ConfigRow['remark'] }) =>
          cellValue ?? TABLE_CELL_EMPTY,
        minWidth: 160,
        title: $t('platform.config.param.remark'),
      },
      {
        align: 'center',
        cellRender: {
          attrs: {
            nameField: 'name',
            nameTitle: $t('platform.config.param.entity'),
            onClick: onActionClick,
          },
          name: 'CellOperation',
          options: [
            {
              code: 'edit',
              show: () => props.canManage,
              text: $t('common.edit'),
              type: 'link',
            },
            {
              code: 'delete',
              danger: true,
              show: () => paramAccess.can(CONFIG_PERMISSIONS.DELETE),
              text: $t('common.delete'),
              type: 'link',
            },
          ],
        },
        fixed: 'right',
        title: $t('crud.columnActions'),
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
              {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
            </Button>
            <Button class="ml-2" type="link" @click="clearSelection">
              {{ $t('system.shared.clearSelection') }}
            </Button>
          </div>
          <Button
            v-else-if="props.canManage"
            type="primary"
            @click="onCreate"
          >
            <Plus class="size-4" />
            {{ $t('platform.config.param.createParam') }}
          </Button>
        </template>
      </Grid>
    </div>
  </div>
</template>
