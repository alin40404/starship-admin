<script lang="ts" setup>
import type { ProductRow, ProductSavePayload } from '#/features/shop/api';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createProduct,
  fetchProduct,
  updateProduct,
} from '#/features/shop/api';
import { $t } from '#/locales';

import { buildProductFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();
const { isMobile } = usePreferences();
const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildProductFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() => (editingId.value ? '编辑商品' : '新增商品'));

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

function toPayload(values: Record<string, unknown>): ProductSavePayload {
  return {
    name: String(values.name ?? ''),
    price: Math.round(Number(values.price_yuan ?? 0) * 100),
    stock: Number(values.stock ?? 0),
    cover: (values.cover as string) || null,
    description: (values.description as string) || null,
    status: Number(values.status ?? 1),
  };
}

async function handleConfirm(popupApi: PopupApi) {
  const { valid } = await formApi.validate();
  if (!valid) return;

  const values = await formApi.getValues();
  popupApi.lock();
  try {
    const payload = toPayload(values);
    if (!editingId.value) {
      await createProduct(payload);
      message.success($t('crud.created'));
    } else {
      await updateProduct(editingId.value, payload);
      message.success($t('crud.saved'));
    }
    popupApi.close();
    emits('success');
  } finally {
    popupApi.unlock();
  }
}

async function handleOpenChange(isOpen: boolean, popupApi: PopupApi) {
  if (!isOpen) return;
  formApi.resetForm();
  const data = popupApi.getData<ProductRow | Record<string, never>>();
  const id =
    data && typeof (data as ProductRow).id === 'number'
      ? (data as ProductRow).id
      : null;
  editingId.value = id;

  if (id) {
    const detail = await fetchProduct(id);
    await formApi.setValues({
      name: detail.name,
      price_yuan: detail.price / 100,
      stock: detail.stock,
      cover: detail.cover ?? '',
      description: detail.description ?? '',
      status: detail.status,
    });
  } else {
    await formApi.setValues({
      name: '',
      price_yuan: 0,
      stock: 0,
      cover: '',
      description: '',
      status: 1,
    });
  }
}

const [Modal, modalApi] = useVbenModal({
  destroyOnClose: true,
  fullscreenButton: false,
  async onConfirm() {
    await handleConfirm(modalApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, modalApi as PopupApi);
  },
});

const [Drawer, drawerApi] = useVbenDrawer({
  destroyOnClose: true,
  async onConfirm() {
    await handleConfirm(drawerApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, drawerApi as PopupApi);
  },
});

function open(data: ProductRow | Record<string, never> = {}) {
  const api = (isMobile.value ? drawerApi : modalApi) as PopupApi;
  api.setData(data);
  api.open();
}

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
