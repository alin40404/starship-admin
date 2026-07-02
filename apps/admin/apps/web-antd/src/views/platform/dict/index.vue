<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { DictTypeRow } from '#/api/system/dict';

import { useAccess } from '@vben/access';
import { Page, useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { deleteDictType, fetchDictTypePage } from '#/api/system/dict';
import { DICT_PERMISSIONS } from '#/constants/dict-permissions';

import { useDictTypeColumns, useDictTypeGridFormSchema } from './data';
import DictItemsDrawer from './modules/items-drawer.vue';
import DictTypeForm from './modules/type-form.vue';

const { hasAccessByCodes } = useAccess();
const canManage = () => hasAccessByCodes([DICT_PERMISSIONS.MANAGE]);

const [DictTypeFormModal, typeFormModalApi] = useVbenModal({
  connectedComponent: DictTypeForm,
  destroyOnClose: true,
});

const [DictItemsPanel, itemsDrawerApi] = useVbenDrawer({
  connectedComponent: DictItemsDrawer,
  destroyOnClose: true,
});

function onCreate() {
  typeFormModalApi.setData({}).open();
}

function onEdit(row: DictTypeRow) {
  typeFormModalApi.setData(row).open();
}

function onManageItems(row: DictTypeRow) {
  itemsDrawerApi.setData(row).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<DictTypeRow>) {
  if (code === 'items') onManageItems(row);
  else if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onDelete(row: DictTypeRow) {
  Modal.confirm({
    content: `确定删除字典「${row.name}」吗？其下字典项将保留但无法再按此类型筛选。`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '删除字典',
    async onOk() {
      await deleteDictType(row.id);
      message.success('已删除');
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<DictTypeRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useDictTypeGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: '查询' },
  },
  gridOptions: {
    columns: useDictTypeColumns(onActionClick),
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchDictTypePage({
            page: page.currentPage,
            per_page: page.pageSize,
            code: formValues?.code || undefined,
            name: formValues?.name || undefined,
            sort_field: sort?.field,
            sort_order:
              order === 'asc' || order === 'desc' ? order : undefined,
            status:
              formValues?.status === undefined || formValues?.status === null
                ? undefined
                : formValues.status,
          });
        },
      },
      sort: true,
    },
    rowConfig: { keyField: 'id' },
    sortConfig: { remote: true },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<DictTypeRow>,
});
</script>

<template>
  <Page auto-content-height>
    <DictTypeFormModal @success="onRefresh" />
    <DictItemsPanel @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <Button v-if="canManage()" type="primary" @click="onCreate">
          <Plus class="size-4" />
          新增字典
        </Button>
      </template>
    </Grid>
  </Page>
</template>
