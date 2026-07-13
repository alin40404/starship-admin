<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ProductsRow } from '#/api/generator/products';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';
import { $t } from '@vben/locales';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteProducts,

  batchUpdateProductsStatus,

  deleteProducts,
  fetchProductsPage,
  updateProducts,
} from '#/api/generator/products';
import { GENERATOR_PRODUCTS_PERMISSIONS } from '#/constants/products-permissions';

import {
  buildProductsMobileCrudConfig,
  useColumns,
  useGridFormSchema,
} from './data';
import ProductsForm from './modules/form.vue';

const productsFormRef = ref<InstanceType<typeof ProductsForm>>();

const { hasAccessByCodes } = useAccess();

const productsAccess = {
  can: (code: string) => hasAccessByCodes([code]),
  canAny: (codes: string[]) => hasAccessByCodes(codes),
};

function onCreate() {
  productsFormRef.value?.open({});
}

function onEdit(row: ProductsRow) {
  productsFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<ProductsRow>) {
  if (code === 'edit') {
    onEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onProductsStatusToggle(nextStatus: 'normal' | 'hidden', row: ProductsRow) {
  return new Promise<boolean>((resolve) => {
    const label = nextStatus === 'normal'
      ? $t('system.shared.status.normal')
      : $t('system.shared.status.hidden');
    Modal.confirm({
      content: $t('crud.toggleStatusConfirm', [label]),
      okText: $t('common.confirm'),
      cancelText: $t('common.cancel'),
      title: $t('crud.toggleStatusTitle'),
      async onOk() {
        try {
          await updateProducts(row.id, { status: nextStatus });
          message.success($t('crud.statusUpdated'));
          resolve(true);
        } catch {
          resolve(false);
        }
      },
      onCancel() {
        resolve(false);
      },
    });
  });
}


const columns = useColumns(onActionClick, onProductsStatusToggle, productsAccess);

const [CrudView, crudApi] = useResponsiveCrud<ProductsRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<ProductsRow>['columns'],
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchProductsPage({
            page: page.currentPage,
            per_page: page.pageSize,
            name: formValues?.name || undefined,
            code: formValues?.code || undefined,
            tag: formValues?.tag || undefined,
            status: formValues?.status || undefined,
            sort_field: sort?.field,
            sort_order: order === 'desc' ? 'desc' : order === 'asc' ? 'asc' : undefined,
          });
        },
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, zoom: true },
  },
  mobile: buildProductsMobileCrudConfig({
    columns,
    query: async (params) => fetchProductsPage(params),
  }),
});

const { clearSelection, reload, selectedRows } = crudApi;

function onRefresh() {
  reload();
}

async function onDelete(row: ProductsRow) {
  await deleteProducts(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: $t('generator.products.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeleteProducts(ids);
      message.success($t('crud.batchDeleted'));
      clearSelection();
      onRefresh();
    },
  });
}


function onBatchUpdateStatus(status: 'normal' | 'hidden') {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  const label = status === 'normal'
    ? $t('system.shared.status.normal')
    : $t('system.shared.status.hidden');
  Modal.confirm({
    content: $t('generator.products.batchStatusConfirm', [ids.length, label]),
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchSet', [label]),
    async onOk() {
      await batchUpdateProductsStatus(ids, status);
      message.success($t('system.shared.batchStatusDone', [label]));
      clearSelection();
      onRefresh();
    },
  });
}


function onFormSuccess() {
  onRefresh();
}
</script>

<template>
  <Page auto-content-height>
    <ProductsForm ref="productsFormRef" @success="onFormSuccess" />
    <CrudView>
      <template #toolbar-actions>
        <div
          v-if="selectedRows.length > 0"
          class="vxe-grid-batch-toolbar"
        >
          <Button
            v-if="productsAccess.can(GENERATOR_PRODUCTS_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>

          <Button
            v-if="productsAccess.can(GENERATOR_PRODUCTS_PERMISSIONS.BATCH_STATUS)"
            class="generator-batch-btn-enable ml-2"
            @click="onBatchUpdateStatus('normal')"
          >
            {{ $t('system.shared.batchEnable') }}
          </Button>
          <Button
            v-if="productsAccess.can(GENERATOR_PRODUCTS_PERMISSIONS.BATCH_STATUS)"
            class="generator-batch-btn-disable ml-2"
            @click="onBatchUpdateStatus('hidden')"
          >
            {{ $t('system.shared.batchDisable') }}
          </Button>

          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button
          v-else-if="productsAccess.can(GENERATOR_PRODUCTS_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          {{ $t('generator.products.form.createTitle') }}
        </Button>
      </template>
    </CrudView>
  </Page>
</template>

<style lang="scss">
.vxe-grid .generator-batch-btn-enable.ant-btn,
.mobile-crud-list .generator-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .generator-batch-btn-disable.ant-btn,
.mobile-crud-list .generator-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}
</style>
