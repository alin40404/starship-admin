<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ConfigRow } from '#/api/system/config';

import { useVbenDrawer } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { fetchConfigs } from '#/api/system/config';
import { TABLE_CELL_EMPTY } from '#/constants/table';

import ParamForm from './param-form.vue';

const props = defineProps<{
  canManage: boolean;
}>();

const [ParamFormDrawer, paramFormDrawerApi] = useVbenDrawer({
  connectedComponent: ParamForm,
  destroyOnClose: true,
});

function onCreate() {
  paramFormDrawerApi.setData({}).open();
}

function onEdit(row: ConfigRow) {
  paramFormDrawerApi.setData(row).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<ConfigRow>) {
  if (code === 'edit') onEdit(row);
}

const [Grid, gridApi] = useVbenVxeGrid<ConfigRow>({
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
      { field: 'type', title: '类型', width: 90 },
      { field: 'sort', title: '排序', width: 80 },
      {
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
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async () => {
          const result = await fetchConfigs('param');
          const list = result.list ?? [];
          return { items: list, total: list.length };
        },
      },
    },
    rowConfig: { keyField: 'key' },
    toolbarConfig: { refresh: true },
  } as VxeTableGridOptions<ConfigRow>,
});
</script>

<template>
  <div>
    <div class="text-muted-foreground mb-4 text-sm">
      维护可动态增删的运行参数，供业务代码按键读取。
    </div>
    <ParamFormDrawer @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <Button v-if="props.canManage" type="primary" @click="onCreate">
          <Plus class="size-4" />
          新增参数
        </Button>
      </template>
    </Grid>
  </div>
</template>
