<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ConfigRow } from '#/api/system/config';

import { useTemplateRef, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { useElementSize } from '@vueuse/core';
import { Button } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { fetchConfigs } from '#/api/system/config';
import { TABLE_CELL_EMPTY } from '#/constants/table';

import { PARAM_TYPE_TAG_OPTIONS } from '../data';
import ParamForm from './param-form.vue';

const props = defineProps<{
  canManage: boolean;
}>();

const gridWrapRef = useTemplateRef<HTMLElement>('gridWrapRef');
const { height: gridWrapHeight } = useElementSize(gridWrapRef);

const [ParamFormModal, paramFormModalApi] = useVbenModal({
  connectedComponent: ParamForm,
  destroyOnClose: true,
});

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
}

const [Grid, gridApi] = useVbenVxeGrid<ConfigRow>({
  class: 'h-full min-h-0',
  gridClass: 'p-0',
  gridOptions: {
    columns: [
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
          ],
        },
        fixed: 'right',
        title: '操作',
        width: 100,
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
    rowConfig: { keyField: 'key' },
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
          <Button v-if="props.canManage" type="primary" @click="onCreate">
            <Plus class="size-4" />
            新增参数
          </Button>
        </template>
      </Grid>
    </div>
  </div>
</template>
