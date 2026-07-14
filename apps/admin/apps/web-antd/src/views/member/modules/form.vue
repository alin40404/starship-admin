<script lang="ts" setup>
import type { MemberRow, MemberSavePayload } from '#/api/member';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createMember, fetchMember, updateMember } from '#/api/member';
import { $t } from '#/locales';

import { buildMemberFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildMemberFormSchema(false),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() =>
  editingId.value ? '编辑会员' : '新增会员',
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

  const values = (await formApi.getValues()) as MemberSavePayload;
  if (editingId.value && !values.password) {
    delete values.password;
  }

  popupApi.lock();
  try {
    if (!editingId.value) {
      await createMember(values);
      message.success($t('crud.created'));
    } else {
      await updateMember(editingId.value, values);
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

  formApi.setState({ schema: buildMemberFormSchema(false) });
  formApi.resetForm();

  const data = popupApi.getData<MemberRow | Record<string, never>>();
  const id =
    data && typeof (data as MemberRow).id === 'number'
      ? (data as MemberRow).id
      : null;
  editingId.value = id;

  formApi.setState({ schema: buildMemberFormSchema(!!id) });

  if (id) {
    const detail = await fetchMember(id);
    await formApi.setValues({
      username: detail.username,
      nickname: detail.nickname ?? '',
      email: detail.email ?? '',
      mobile: detail.mobile ?? '',
      status: detail.status,
      remark: detail.remark ?? '',
      password: '',
    });
  } else {
    await formApi.setValues({
      username: '',
      nickname: '',
      email: '',
      mobile: '',
      status: 1,
      remark: '',
      password: '',
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

function open(data: MemberRow | Record<string, never> = {}) {
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
    class="w-full max-w-full md:w-[640px] md:max-w-[640px]"
  >
    <Form />
  </Modal>
  <Drawer v-else :title="formTitle" class="w-full">
    <Form />
  </Drawer>
</template>
