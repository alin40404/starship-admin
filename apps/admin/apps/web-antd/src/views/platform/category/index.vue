<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { CategoryRow } from '#/api/system/category';

import { useAccess } from '@vben/access';
import { Page, useVbenModal } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { deleteCategory, fetchCategoryPage } from '#/api/system/category';
import { CATEGORY_PERMISSIONS } from '#/constants/category-permissions';

import { useColumns, useGridFormSchema } from './data';
import CategoryForm from './modules/form.vue';

const { hasAccessByCodes } = useAccess();

const categoryAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const [CategoryFormModal, categoryFormModalApi] = useVbenModal({
  connectedComponent: CategoryForm,
  destroyOnClose: true,
});

function onCreate() {
  categoryFormModalApi.setData({}).open();
}

function onEdit(row: CategoryRow) {
  categoryFormModalApi.setData(row).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<CategoryRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onDelete(row: CategoryRow) {
  Modal.confirm({
    content: `确定删除分类「${row.label}」吗？`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '删除分类',
    async onOk() {
      await deleteCategory(row.id);
      message.success('已删除');
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<CategoryRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: '查询' },
  },
  gridOptions: {
    columns: useColumns(onActionClick, categoryAccess),
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchCategoryPage({
            page: page.currentPage,
            per_page: page.pageSize,
            label: formValues?.label || undefined,
            value: formValues?.value || undefined,
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
  } as VxeTableGridOptions<CategoryRow>,
});
</script>

<template>
  <Page auto-content-height>
    <CategoryFormModal @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <Button
          v-if="categoryAccess.can(CATEGORY_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增分类
        </Button>
      </template>
    </Grid>
  </Page>
</template>
