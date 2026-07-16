<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { OrderRow } from '#/features/shop/api';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  cancelOrder,
  deleteOrder,
  fetchOrderPage,
  prepayOrder,
  syncOrderStatus,
} from '#/features/shop/api';
import { SHOP_PERMISSIONS } from '#/features/shop/constants/shop-permissions';
import { $t } from '#/locales';

import { useColumns, useGridFormSchema } from './data';
import OrderForm from './modules/form.vue';

type OrderFormExpose = { open: () => void };

const orderFormRef = ref<OrderFormExpose>();
const { hasAccessByCodes } = useAccess();
const access = { can: (code: string) => hasAccessByCodes([code]) };

function onCreate() {
  orderFormRef.value?.open();
}

function onActionClick({ code, row }: OnActionClickParams<OrderRow>) {
  if (code === 'prepay') void onPrepay(row);
  else if (code === 'sync') void onSync(row);
  else if (code === 'cancel') onCancel(row);
  else if (code === 'delete') onDelete(row);
}

const columns = useColumns(onActionClick, access);

const [CrudView, crudApi] = useResponsiveCrud<OrderRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    columns: columns as VxeTableGridOptions<OrderRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          fetchOrderPage({
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
  } as VxeTableGridOptions<OrderRow>,
});

const { reload } = crudApi;

function onRefresh() {
  reload();
}

async function onPrepay(row: OrderRow) {
  try {
    const result = await prepayOrder(row.id);
    Modal.info({
      content: JSON.stringify(result.payment, null, 2),
      title: '预支付结果',
      width: 640,
    });
    onRefresh();
  } catch (error: unknown) {
    const msg = error instanceof Error ? error.message : '发起支付失败';
    message.error(msg);
  }
}

async function onSync(row: OrderRow) {
  await syncOrderStatus(row.id);
  message.success('支付状态已同步');
  onRefresh();
}

function onCancel(row: OrderRow) {
  Modal.confirm({
    content: `确认取消订单「${row.order_no}」？`,
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    title: $t('common.prompt'),
    async onOk() {
      await cancelOrder(row.id);
      message.success('订单已取消');
      onRefresh();
    },
  });
}

function onDelete(row: OrderRow) {
  Modal.confirm({
    content: `确认删除订单「${row.order_no}」？`,
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('common.prompt'),
    async onOk() {
      await deleteOrder(row.id);
      message.success($t('crud.deleted'));
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <OrderForm ref="orderFormRef" @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <Button
          v-if="access.can(SHOP_PERMISSIONS.ORDERS_CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          创建订单
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
