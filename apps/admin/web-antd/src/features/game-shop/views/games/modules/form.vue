<script lang="ts" setup>
import type { GameRow, GameSavePayload } from '#/features/game-shop/api';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createGame, fetchGame, updateGame } from '#/features/game-shop/api';
import { $t } from '#/locales';

import { buildGameFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();
const { isMobile } = usePreferences();
const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildGameFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() => (editingId.value ? '编辑游戏' : '新增游戏'));

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

async function handleConfirm(popupApi: PopupApi) {
  const { valid } = await formApi.validate();
  if (!valid) return;
  const values = (await formApi.getValues()) as GameSavePayload;
  popupApi.lock();
  try {
    if (!editingId.value) {
      await createGame(values);
      message.success($t('crud.created'));
    } else {
      await updateGame(editingId.value, values);
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
  const data = popupApi.getData<GameRow | Record<string, never>>();
  const id =
    data && typeof (data as GameRow).id === 'number'
      ? (data as GameRow).id
      : null;
  editingId.value = id;
  if (id) {
    const detail = await fetchGame(id);
    await formApi.setValues({
      name: detail.name,
      code: detail.code ?? '',
      platform: detail.platform ?? '',
      publisher: detail.publisher ?? '',
      cover: detail.cover ?? '',
      sort: detail.sort,
      description: detail.description ?? '',
      status: detail.status,
    });
  } else {
    await formApi.setValues({
      name: '',
      code: '',
      platform: '',
      publisher: '',
      cover: '',
      sort: 0,
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

function open(data: GameRow | Record<string, never> = {}) {
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
