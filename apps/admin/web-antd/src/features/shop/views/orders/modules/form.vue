<script lang="ts" setup>
import type { OrderCreatePayload } from '#/features/shop/api';

import { computed, onMounted, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createOrder, fetchProductPage } from '#/features/shop/api';
import { $t } from '#/locales';

import { buildOrderFormSchema } from './data';

const emits = defineEmits<{ success: [] }>();
const { isMobile } = usePreferences();
const productOptions = ref<{ label: string; value: number }[]>([]);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildOrderFormSchema([]),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() => '创建订单');

type PopupApi = {
  close: () => void;
  lock: () => void;
  open: () => void;
  unlock: () => void;
};

async function loadProducts() {
  const page = await fetchProductPage({ page: 1, per_page: 200, status: 1 });
  productOptions.value = page.data.map((item) => ({
    label: `${item.name}（¥${(item.price / 100).toFixed(2)}）`,
    value: item.id,
  }));
  formApi.setState({ schema: buildOrderFormSchema(productOptions.value) });
}

async function handleConfirm(popupApi: PopupApi) {
  const { valid } = await formApi.validate();
  if (!valid) return;

  const values = (await formApi.getValues()) as OrderCreatePayload;
  popupApi.lock();
  try {
    await createOrder({
      product_id: Number(values.product_id),
      quantity: Number(values.quantity ?? 1),
      member_id: values.member_id ? Number(values.member_id) : undefined,
      remark: values.remark || undefined,
    });
    message.success($t('crud.created'));
    popupApi.close();
    emits('success');
  } finally {
    popupApi.unlock();
  }
}

const [Modal, modalApi] = useVbenModal({
  destroyOnClose: true,
  fullscreenButton: false,
  async onConfirm() {
    await handleConfirm(modalApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    if (!isOpen) return;
    await loadProducts();
    formApi.resetForm();
    await formApi.setValues({ quantity: 1 });
  },
});

const [Drawer, drawerApi] = useVbenDrawer({
  destroyOnClose: true,
  async onConfirm() {
    await handleConfirm(drawerApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    if (!isOpen) return;
    await loadProducts();
    formApi.resetForm();
    await formApi.setValues({ quantity: 1 });
  },
});

function open() {
  const api = (isMobile.value ? drawerApi : modalApi) as PopupApi;
  api.open();
}

onMounted(() => {
  void loadProducts();
});

defineExpose({ open });
</script>

<template>
  <Modal
    v-if="!isMobile"
    :title="formTitle"
    class="w-full max-w-full md:w-[640px] md:max-w-[640px]"
  >
    <Form />
  </Modal>
  <Drawer v-else :title="formTitle" class="w-full">
    <Form />
  </Drawer>
</template>
