<script lang="ts" setup>
import type {
  GameProductRow,
  GameProductSavePayload,
} from '#/features/game-shop/api';

import { computed, ref, watch } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createGameProduct,
  fetchGameProduct,
  updateGameProduct,
} from '#/features/game-shop/api';
import { $t } from '#/locales';

import { buildProductFormSchema } from '../data';

const props = defineProps<{
  gameOptions: { label: string; value: number }[];
}>();

const emits = defineEmits<{ success: [] }>();
const { isMobile } = usePreferences();
const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildProductFormSchema([]),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

watch(
  () => props.gameOptions,
  (opts) => {
    formApi.setState({ schema: buildProductFormSchema(opts) });
  },
  { immediate: true },
);

const formTitle = computed(() => (editingId.value ? '编辑商品' : '新增商品'));

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

function toPayload(values: Record<string, unknown>): GameProductSavePayload {
  return {
    game_id: Number(values.game_id),
    name: String(values.name ?? ''),
    sku_code: (values.sku_code as string) || null,
    product_kind: String(values.product_kind ?? 'topup'),
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
      await createGameProduct(payload);
      message.success($t('crud.created'));
    } else {
      await updateGameProduct(editingId.value, payload);
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
  const data = popupApi.getData<GameProductRow | Record<string, never>>();
  const id =
    data && typeof (data as GameProductRow).id === 'number'
      ? (data as GameProductRow).id
      : null;
  editingId.value = id;
  if (id) {
    const detail = await fetchGameProduct(id);
    await formApi.setValues({
      game_id: detail.game_id,
      name: detail.name,
      sku_code: detail.sku_code ?? '',
      product_kind: detail.product_kind,
      price_yuan: detail.price / 100,
      stock: detail.stock,
      cover: detail.cover ?? '',
      description: detail.description ?? '',
      status: detail.status,
    });
  } else {
    await formApi.setValues({
      game_id: undefined,
      name: '',
      sku_code: '',
      product_kind: 'topup',
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

function open(data: GameProductRow | Record<string, never> = {}) {
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
