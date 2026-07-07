<script lang="ts" setup>
import type { DemoArticleRow, DemoArticleSavePayload } from '#/api/system/demo-article';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createDemoArticle, updateDemoArticle } from '#/api/system/demo-article';

import { buildDemoArticleFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

const editingId = ref<number | null>(null);
const readonly = ref(false);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildDemoArticleFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() => {
  if (readonly.value) return '查看演示文章';
  return editingId.value ? '编辑演示文章' : '新增演示文章';
});

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

async function handleConfirm(popupApi: PopupApi) {
  if (readonly.value) {
    popupApi.close();
    return;
  }
  const { valid } = await formApi.validate();
  if (!valid) return;

  const values = (await formApi.getValues()) as DemoArticleSavePayload;
  popupApi.lock();
  try {
    if (!editingId.value) {
      await createDemoArticle(values);
      message.success('创建成功');
    } else {
      await updateDemoArticle(editingId.value, values);
      message.success('已保存');
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
  const data = popupApi.getData<DemoArticleRow | Record<string, never>>();
  const id = data && typeof (data as DemoArticleRow).id === 'number' ? (data as DemoArticleRow).id : null;
  editingId.value = id;
  readonly.value = false;
  formApi.setState({ schema: buildDemoArticleFormSchema(false) });
  if (id) {
    formApi.setValues(data as DemoArticleRow);
  }
}

const [ModalView, modalApi] = useVbenModal({
  onConfirm: () => handleConfirm(modalApi),
  onOpenChange: (isOpen) => handleOpenChange(isOpen, modalApi),
});

const [DrawerView, drawerApi] = useVbenDrawer({
  onConfirm: () => handleConfirm(drawerApi),
  onOpenChange: (isOpen) => handleOpenChange(isOpen, drawerApi),
});

function open(data: DemoArticleRow | Record<string, never> = {}) {
  const api = isMobile.value ? drawerApi : modalApi;
  api.setData(data);
  api.open();
}

defineExpose({ open });
</script>

<template>
  <ModalView v-if="!isMobile" :title="formTitle" class="w-[640px]">
    <Form />
  </ModalView>
  <DrawerView v-else :title="formTitle" class="w-full">
    <Form />
  </DrawerView>
</template>
