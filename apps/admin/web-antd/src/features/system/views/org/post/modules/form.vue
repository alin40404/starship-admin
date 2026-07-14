<script lang="ts" setup>
import type { PostRow, PostSavePayload } from '#/features/system/api/post';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createPost, updatePost } from '#/features/system/api/post';
import { $t } from '#/locales';

import { buildPostFormSchema } from '../data';

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
  schema: buildPostFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() => {
  if (readonly.value) return $t('org.post.viewTitle');
  return editingId.value
    ? $t('org.post.editTitle')
    : $t('org.post.createTitle');
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

  const values = (await formApi.getValues()) as PostSavePayload;
  popupApi.lock();
  try {
    if (!editingId.value) {
      await createPost(values);
      message.success($t('crud.created'));
    } else {
      await updatePost(editingId.value, values);
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
  const data = popupApi.getData<PostRow | Record<string, never>>();
  const id = data && typeof (data as PostRow).id === 'number' ? (data as PostRow).id : null;
  editingId.value = id;
  readonly.value = false;
  formApi.setState({ schema: buildPostFormSchema(false) });

  if (id) {
    formApi.setValues({
      code: (data as PostRow).code,
      name: (data as PostRow).name,
      remark: (data as PostRow).remark ?? '',
      sort: (data as PostRow).sort ?? 0,
      status: (data as PostRow).status,
    });
  } else {
    formApi.setValues({
      code: '',
      name: '',
      remark: '',
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

function getActivePopupApi() {
  return (isMobile.value ? drawerApi : modalApi) as PopupApi;
}

function open(data: PostRow | Record<string, never> = {}) {
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
