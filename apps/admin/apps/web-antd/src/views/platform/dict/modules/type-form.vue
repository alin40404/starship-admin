<script lang="ts" setup>
import type { DictTypeRow, DictTypeSavePayload } from '#/api/system/dict';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { $t } from '@vben/locales';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createDictType, updateDictType } from '#/api/system/dict';

import { buildDictTypeFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildDictTypeFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() =>
  editingId.value
    ? $t('platform.dict.editDict')
    : $t('platform.dict.createDictTitle'),
);

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

  const values = (await formApi.getValues()) as DictTypeSavePayload;
  popupApi.lock();
  try {
    if (!editingId.value) {
      await createDictType(values);
      message.success($t('crud.created'));
    } else {
      await updateDictType(editingId.value, values);
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
  const data = popupApi.getData<DictTypeRow | Record<string, never>>();
  const id =
    data && typeof (data as DictTypeRow).id === 'number'
      ? (data as DictTypeRow).id
      : null;
  editingId.value = id;
  formApi.setState({ schema: buildDictTypeFormSchema(!!id) });

  if (id) {
    formApi.setValues({
      code: (data as DictTypeRow).code,
      name: (data as DictTypeRow).name,
      remark: (data as DictTypeRow).remark ?? '',
      status: (data as DictTypeRow).status,
    });
  } else {
    formApi.setValues({
      code: '',
      name: '',
      remark: '',
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

function getActivePopupApi() {
  return (isMobile.value ? drawerApi : modalApi) as PopupApi;
}

function open(data: DictTypeRow | Record<string, never> = {}) {
  const api = getActivePopupApi();
  api.setData(data);
  api.open();
}

defineExpose({ open });
</script>

<template>
  <Modal
    v-if="!isMobile"
    :title="formTitle"
    class="w-full max-w-full md:w-[560px] md:max-w-[560px]"
  >
    <Form />
  </Modal>
  <Drawer v-else :title="formTitle" class="w-[560px]">
    <Form />
  </Drawer>
</template>
