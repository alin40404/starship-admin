<script lang="ts" setup>
import type { CategoryRow, CategorySavePayload } from '#/features/cms/api';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createCategory,
  fetchCategory,
  updateCategory,
} from '#/features/cms/api';
import { $t } from '#/locales';

import { buildCategoryFormSchema } from './data';

const emits = defineEmits<{ success: [] }>();
const { isMobile } = usePreferences();
const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildCategoryFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() => (editingId.value ? '编辑栏目' : '新增栏目'));

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

  const values = (await formApi.getValues()) as CategorySavePayload;
  popupApi.lock();
  try {
    if (!editingId.value) {
      await createCategory(values);
      message.success($t('crud.created'));
    } else {
      await updateCategory(editingId.value, values);
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
  const data = popupApi.getData<CategoryRow | Record<string, never>>();
  const id =
    data && typeof (data as CategoryRow).id === 'number'
      ? (data as CategoryRow).id
      : null;
  editingId.value = id;

  if (id) {
    const detail = await fetchCategory(id);
    await formApi.setValues(detail);
  } else {
    await formApi.setValues({
      name: '',
      slug: '',
      parent_id: 0,
      sort: 0,
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

function open(data: CategoryRow | Record<string, never> = {}) {
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
