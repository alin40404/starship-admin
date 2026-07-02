<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AreaTreeNode } from '#/api/system/region';

import { useAccess } from '@vben/access';
import { Page, useVbenModal } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { deleteArea, fetchAreaTree } from '#/api/system/region';
import { REGION_PERMISSIONS } from '#/constants/region-permissions';

import { useAreaColumns, useAreaGridFormSchema } from './data';
import AreaForm from './modules/form.vue';

const { hasAccessByCodes } = useAccess();
const canManage = () => hasAccessByCodes([REGION_PERMISSIONS.MANAGE]);

const [AreaFormModal, areaFormModalApi] = useVbenModal({
  connectedComponent: AreaForm,
  destroyOnClose: true,
});

function onCreate() {
  areaFormModalApi.setData({}).open();
}

function onAppend(row: AreaTreeNode) {
  areaFormModalApi.setData({ pid: row.id }).open();
}

function onEdit(row: AreaTreeNode) {
  areaFormModalApi.setData(row).open();
}

function onRefresh() {
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
  gridOptions: {
    columns: useAreaColumns(onActionClick, canManage),
    height: 'auto',
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async (_params, formValues) =>
          fetchAreaTree({
            level:
              formValues?.level === undefined || formValues?.level === null
                ? undefined
                : Number(formValues.level),
            name: formValues?.name || undefined,
            pinyin: formValues?.pinyin || undefined,
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
  } as VxeTableGridOptions<AreaTreeNode>,
});
</script>

<template>
  <Page auto-content-height>
    <AreaFormModal @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <Button v-if="canManage()" type="primary" @click="onCreate">
          <Plus class="size-4" />
          新增区域
        </Button>
      </template>
    </Grid>
  </Page>
</template>
