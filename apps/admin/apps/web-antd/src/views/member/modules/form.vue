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

const [Modal, modalApi] = useVbenModal({
  onConfirm: async () => handleConfirm(modalApi),
});

const [Drawer, drawerApi] = useVbenDrawer({
  onConfirm: async () => handleConfirm(drawerApi),
});

async function open(data?: MemberRow | Record<string, never>) {
  const id = data && 'id' in data ? Number(data.id) : null;
  editingId.value = id && !Number.isNaN(id) ? id : null;

  formApi.setState({ schema: buildMemberFormSchema(!!editingId.value) });
  formApi.resetForm();

  if (editingId.value) {
    const detail = await fetchMember(editingId.value);
    await formApi.setValues({
      username: detail.username,
      nickname: detail.nickname,
      email: detail.email,
      mobile: detail.mobile,
      status: detail.status,
      remark: detail.remark,
      password: '',
    });
  } else {
    await formApi.setValues({ status: 1 });
  }

  const popup = isMobile.value ? modalApi : drawerApi;
  popup.setData(data ?? {});
  popup.open();
}

defineExpose({ open });
</script>

<template>
  <component :is="isMobile ? Modal : Drawer" :title="formTitle">
    <Form />
  </component>
</template>
