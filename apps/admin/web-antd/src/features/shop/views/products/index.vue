<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ProductRow } from '#/features/shop/api';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  deleteProduct,
  fetchProductPage,
  updateProductStatus,
} from '#/features/shop/api';
import { SHOP_PERMISSIONS } from '#/features/shop/constants/shop-permissions';
import { $t } from '#/locales';

import { useColumns, useGridFormSchema } from './data';
import ProductForm from './modules/form.vue';

type ProductFormExpose = { open: (data?: ProductRow | Record<string, never>) => void };

const productFormRef = ref<ProductFormExpose>();
const { hasAccessByCodes } = useAccess();
const access = { can: (code: string) => hasAccessByCodes([code]) };

function onCreate() {
  productFormRef.value?.open({});
}

function onEdit(row: ProductRow) {
  productFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<ProductRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onStatusBeforeChange(newVal: number, row: ProductRow) {
  try {
    await updateProductStatus(row.id, newVal);
    message.success($t('crud.saved'));
    return true;
  } catch {
    return false;
  }
}

const columns = useColumns(onActionClick, access, onStatusBeforeChange);

const [CrudView, crudApi] = useResponsiveCrud<ProductRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    columns: columns as VxeTableGridOptions<ProductRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          fetchProductPage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
            status:
              formValues?.status === undefined || formValues?.status === null
                ? undefined
                : formValues.status,
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<ProductRow>,
});

const { reload } = crudApi;

function onRefresh() {
  reload();
}

function onDelete(row: ProductRow) {
  Modal.confirm({
    content: `确认删除商品「${row.name}」？`,
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('common.prompt'),
    async onOk() {
      await deleteProduct(row.id);
      message.success($t('crud.deleted'));
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <ProductForm ref="productFormRef" @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <Button
          v-if="access.can(SHOP_PERMISSIONS.PRODUCTS_CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增商品
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
